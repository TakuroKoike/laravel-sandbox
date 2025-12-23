<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PdfController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-pdf', [PdfController::class, 'generate'])->name('generate-pdf');
Route::get('/pdf-status/{id}', [PdfController::class, 'status'])->name('pdf.status');
Route::get('/pdf-check-status/{id}', [PdfController::class, 'checkStatus'])->name('pdf.check-status');
Route::get('/pdf-download/{id}', [PdfController::class, 'download'])->name('pdf.download');
