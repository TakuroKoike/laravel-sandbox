<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GeneratePdfJob;

class AsyncPdfTest extends TestCase
{
    // use RefreshDatabase; // Not using DB for status in this implementation, but cache. 
    // If cache is database store, we might need it, but usually cache array or file is used in tests.
    // Given .env has QUEUE_CONNECTION=database, we might need to migrate.
    // And if CACHE_STORE is database (default is file or database depending on laravel version/env), we need migration.
    // The default testing phpunit.xml usually sets CACHE_STORE to array/file and QUEUE_CONNECTION to sync,
    // BUT we want to test the async behavior.

    // We will mocking Queue facade to assert pushed.

    public function test_job_is_dispatched()
    {
        Queue::fake();

        $response = $this->get('/generate-pdf');

        $response->assertStatus(302);
        $response->assertRedirectContains('/pdf-status/');

        // Extract Job ID from redirect header or URL ??
        // The URL is /pdf-status/{id}.
        $location = $response->headers->get('Location');
        $parts = explode('/', $location);
        $jobId = end($parts);

        Queue::assertPushed(GeneratePdfJob::class);
    }

    public function test_status_endpoint_returns_processing_initially()
    {
        // We need to manually put something in cache or simulate the job status
        // But in real flow, the Controller puts nothing initially?
        // Wait, generate() dispatches the job. The JOB puts 'processing' in cache?
        // No, the Job handle() puts 'processing'.
        // So immediately after dispatch, if the queue hasn't run, the status might be null?
        // Let's check PdfController logic.
        // $status = Cache::get(...); if (!$status) returns 404 not found.

        // So there is a race condition or design gap: 
        // If verify checkStatus is called before Job starts processing, it returns 404/not_found.
        // The View handles this? 
        // In view JS: if status is not 'completed' or 'failed', it polls again.
        // If it returns 404, it might be fine, but maybe we should set 'queued' status in Controller before dispatch?

        // Let's update Controller to set initial status to 'queued' or 'processing'.
        // Ideally Controller should set it to 'queued' just in case.
        // But let's test current implementation.

        $jobId = 'test-uuid';
        Cache::put("pdf_status_{$jobId}", 'processing', 60);

        $response = $this->get("/pdf-check-status/{$jobId}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'processing']);
    }

    public function test_status_endpoint_returns_completed_when_job_done()
    {
        $jobId = 'test-uuid-completed';
        Cache::put("pdf_status_{$jobId}", 'completed', 60);

        $response = $this->get("/pdf-check-status/{$jobId}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'completed']);
    }

    public function test_download_endpoint()
    {
        $jobId = 'test-uuid-download';
        // Mock file existence?
        // Storage::fake('local'); ? 
        // The implementation uses direct file paths in storage/app/temp.
        // It bypasses Storage facade slightly or uses storage_path.

        // For test, we can just skip file check or mock it if we can.
        // Or we use Cache to point to a reliable file.
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf');
        $pdfFile = $tempFile . '.pdf';
        // Rename or just use the new path, tempnam created the file, we need to handle it.
        // On linux tempnam creates the file. 
        rename($tempFile, $pdfFile);

        // Add PDF magic header to assume mime type detection works
        file_put_contents($pdfFile, '%PDF-1.4 dummy content');

        Cache::put("pdf_file_{$jobId}", $pdfFile, 60);

        $response = $this->get("/pdf-download/{$jobId}");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
    }
}
