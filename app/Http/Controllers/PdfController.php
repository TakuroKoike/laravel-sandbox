<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\SampleExport;

class PdfController extends Controller
{
    public function generate()
    {
        $export = new SampleExport();
        $pdfPath = $export->generate();

        return response()->download($pdfPath, 'generated_report.pdf')->deleteFileAfterSend(true);
    }
}
