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

        // ジョブ情報の取得
        $jobDbId = $this->job ? $this->job->getJobId() : null;
        $queue = $this->job ? $this->job->getQueue() : null;
        $payload = $this->job ? $this->job->getRawBody() : null;
        $attempts = $this->job ? $this->job->attempts() : null;

        try {
            // ステータスを処理中に更新 (DB記録)
            \App\Models\JobHistory::create([
                'job_uuid' => $this->jobId,
                'status' => 'processing',
                'job_db_id' => $jobDbId,
                'queue' => $queue,
                'payload' => $payload,
                'attempts' => $attempts,
                'job_created_at' => now()->timestamp, // jobsテーブルのcreated_atは簡単には取れない場合があるため現在時刻で代用
            ]);

            $export = new SampleExport();
            $pdfPath = $export->generate();

            // 完了ステータスをDBに記録
            \App\Models\JobHistory::create([
                'job_uuid' => $this->jobId,
                'status' => 'completed',
                'job_db_id' => $jobDbId,
                'queue' => $queue,
                'payload' => $payload,
                'attempts' => $attempts,
                'details' => $pdfPath,
            ]);

            Log::info("PDF生成が完了しました。Job ID: {$this->jobId}");

        } catch (\Exception $e) {
            Log::error("PDF生成に失敗しました。Job ID: {$this->jobId}. Error: " . $e->getMessage());

            // 失敗ステータスをDBに記録
            \App\Models\JobHistory::create([
                'job_uuid' => $this->jobId,
                'status' => 'failed',
                'job_db_id' => $jobDbId,
                'queue' => $queue,
                'payload' => $payload,
                'attempts' => $attempts,
                'details' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
