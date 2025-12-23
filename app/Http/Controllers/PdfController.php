<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\SampleExport;

class PdfController extends Controller
{
    /**
     * PDF生成ジョブをディスパッチします。
     */
    public function generate()
    {
        $jobId = (string) \Illuminate\Support\Str::uuid();
        \App\Jobs\GeneratePdfJob::dispatch($jobId);

        return redirect()->route('pdf.status', ['id' => $jobId]);
    }

    /**
     * ステータス確認画面を表示します。
     */
    public function status($id)
    {
        return view('pdf_status', ['jobId' => $id]);
    }

    /**
     * 現在のジョブステータスを確認します。
     */
    public function checkStatus($id)
    {
        $status = \Illuminate\Support\Facades\Cache::get("pdf_status_{$id}");

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json(['status' => $status]);
    }

    /**
     * 生成されたPDFをダウンロードします。
     */
    public function download($id)
    {
        $path = \Illuminate\Support\Facades\Cache::get("pdf_file_{$id}");

        if (!$path || !file_exists($path)) {
            abort(404, 'ファイルが見つからないか、有効期限切れです。');
        }

        return response()->download($path, 'generated_report.pdf');
    }
}
