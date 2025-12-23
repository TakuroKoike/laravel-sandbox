<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GeneratePdfJob;
use App\Models\JobHistory;

class AsyncPdfTest extends TestCase
{
    // Need refresh database for testing DB logging
    use RefreshDatabase;

    public function test_job_is_dispatched()
    {
        Queue::fake();

        $response = $this->get('/generate-pdf');

        $response->assertStatus(302);
        $response->assertRedirectContains('/pdf-status/');

        $location = $response->headers->get('Location');
        $parts = explode('/', $location);
        $jobId = end($parts);

        Queue::assertPushed(GeneratePdfJob::class);

        // Assert initialqueued status is logged
        $this->assertDatabaseHas('job_histories', [
            'job_uuid' => $jobId,
            'status' => 'queued',
        ]);
    }

    public function test_status_endpoint_returns_processing_initially()
    {
        $jobId = 'test-uuid';

        // Create a history record
        JobHistory::create([
            'job_uuid' => $jobId,
            'status' => 'processing',
        ]);

        $response = $this->get("/pdf-check-status/{$jobId}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'processing']);
    }

    public function test_status_endpoint_returns_completed_when_job_done()
    {
        $jobId = 'test-uuid-completed';

        JobHistory::create([
            'job_uuid' => $jobId,
            'status' => 'completed',
        ]);

        $response = $this->get("/pdf-check-status/{$jobId}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'completed']);
    }

    public function test_download_endpoint()
    {
        $jobId = 'test-uuid-download';

        $tempFile = tempnam(sys_get_temp_dir(), 'pdf');
        $pdfFile = $tempFile . '.pdf';
        rename($tempFile, $pdfFile);

        file_put_contents($pdfFile, '%PDF-1.4 dummy content');

        JobHistory::create([
            'job_uuid' => $jobId,
            'status' => 'completed',
            'details' => $pdfFile,
        ]);

        $response = $this->get("/pdf-download/{$jobId}");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
    }
}
