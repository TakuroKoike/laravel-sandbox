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

        // 初期ステータス（queued）をDBに記録
        \App\Models\JobHistory::create([
            'job_uuid' => $jobId,
            'status' => 'queued',
            'job_created_at' => now()->timestamp,
        ]);

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
        // 最新のステータスを取得
        $history = \App\Models\JobHistory::where('job_uuid', $id)
            ->latest()
            ->first();

        if (!$history) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json(['status' => $history->status]);
    }

    /**
     * 生成されたPDFをダウンロードします。
     */
    public function download($id)
    {
        // 完了ステータスのレコードを取得
        $history = \App\Models\JobHistory::where('job_uuid', $id)
            ->where('status', 'completed')
            ->first();

        if (!$history || !$history->details || !file_exists($history->details)) {
            abort(404, 'ファイルが見つからないか、有効期限切れです。');
        }

        return response()->download($history->details, 'generated_report.pdf');
    }
}
