<?php

namespace App\Exports;

use App\Contracts\Export\ExportStrategyInterface;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Export data as PDF content using DomPDF.
 */
class PdfExportStrategy implements ExportStrategyInterface
{
    /**
     * Generate PDF content from tabular data.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @param  array<int, string>  $headers
     */
    public function export(array $data, array $headers): string
    {
        $pdf = Pdf::loadView('pdf.export-table', [
            'columns' => $headers,
            'rows' => $data,
        ])
            ->setPaper(config('pdf.paper_size', 'a4'), 'landscape');

        return $pdf->output();
    }

    public function getMimeType(): string
    {
        return 'application/pdf';
    }

    public function getExtension(): string
    {
        return 'pdf';
    }
}
