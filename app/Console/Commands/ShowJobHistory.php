<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowJobHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:history {--limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display job history with details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $histories = \App\Models\JobHistory::latest()->take($limit)->get();

        if ($histories->isEmpty()) {
            $this->info('No job history found.');
            return;
        }

        foreach ($histories as $history) {
            $this->line(str_repeat('-', 50));
            $this->info("ID: {$history->id}");
            $this->line("UUID: {$history->job_uuid}");
            $this->line("Status: <fg={$this->getStatusColor($history->status)}>{$history->status}</>");
            $this->line("Time: {$history->created_at}");

            if ($history->job_db_id) {
                $this->line("Job DB ID: {$history->job_db_id} | Queue: {$history->queue} | Attempts: {$history->attempts}");
            }

            if ($history->payload) {
                $this->comment('Payload:');
                $payload = json_decode($history->payload, true);
                $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            if ($history->details) {
                $this->comment('Details:');
                $this->line($history->details);
            }
        }
        $this->line(str_repeat('-', 50));
    }

    private function getStatusColor($status)
    {
        return match ($status) {
            'completed' => 'green',
            'processing' => 'yellow',
            'failed' => 'red',
            'queued' => 'blue',
            default => 'white',
        };
    }
}
