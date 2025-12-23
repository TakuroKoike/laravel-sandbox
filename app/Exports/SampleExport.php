<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SampleExport
{
    /**
     * Excelを生成し、PDFに変換する
     *
     * @return string 生成されたPDFファイルのパス
     */
    public function generate(): string
    {
        // 1. スプレッドシートの作成
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // サンプルデータの追加
        $sheet->setCellValue('A1', 'PDF Generation via Excel & LibreOffice');
        $sheet->setCellValue('A3', 'Data 1');
        $sheet->setCellValue('B3', 'Value 1');
        $sheet->setCellValue('A4', 'Data 2');
        $sheet->setCellValue('B4', 'Value 2');
        $sheet->setCellValue('A5', 'Date');
        $sheet->setCellValue('B5', now()->toDateTimeString());

        // ヘッダーのスタイル設定
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // 2. 一時的なExcelファイルとして保存
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $timestamp = now()->timestamp;
        $excelFileName = "temp_{$timestamp}.xlsx";
        $excelPath = $tempDir . '/' . $excelFileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($excelPath);

        // 3. LibreOfficeを使用してPDFに変換
        // 出力ディレクトリは絶対パスである必要があります
        // 並列実行時の競合を防ぐため、一意の設定ディレクトリを指定します
        $userInstallDir = "file://" . $tempDir . "/libo_user_" . $timestamp . "_" . uniqid();

        $command = [
            'libreoffice',
            '-env:UserInstallation=' . $userInstallDir,
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $tempDir,
            $excelPath
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            // 失敗した場合はExcelファイルを削除
            if (file_exists($excelPath)) {
                unlink($excelPath);
            }
            throw new ProcessFailedException($process);
        }

        // 4. PDFパスの生成
        $pdfFileName = "temp_{$timestamp}.pdf";
        $pdfPath = $tempDir . '/' . $pdfFileName;

        if (!file_exists($pdfPath)) {
            // 失敗した場合はExcelファイルを削除
            if (file_exists($excelPath)) {
                unlink($excelPath);
            }
            abort(500, 'PDF generation failed.');
        }

        // Excelファイルを削除
        if (file_exists($excelPath)) {
            unlink($excelPath);
        }

        return $pdfPath;
    }
}
