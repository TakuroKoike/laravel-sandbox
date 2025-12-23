<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Exports\SampleExport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;

    /**
     * 新しいジョブインスタンスを作成します。
     */
    public function __construct($jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * ジョブを実行します。
     */
    public function handle(): void
    {
        Log::info("PDF生成を開始します。Job ID: {$this->jobId}");

        try {
            // ステータスを処理中に更新
            Cache::put("pdf_status_{$this->jobId}", 'processing', 3600);

            $export = new SampleExport();
            $pdfPath = $export->generate();

            // 結果をキャッシュに保存
            Cache::put("pdf_file_{$this->jobId}", $pdfPath, 3600);
            Cache::put("pdf_status_{$this->jobId}", 'completed', 3600);

            Log::info("PDF生成が完了しました。Job ID: {$this->jobId}");

        } catch (\Exception $e) {
            Log::error("PDF生成に失敗しました。Job ID: {$this->jobId}. Error: " . $e->getMessage());
            Cache::put("pdf_status_{$this->jobId}", 'failed', 3600);
            throw $e;
        }
    }
}
