<?php

namespace App\Exports;

use App\Contracts\Export\ExportStrategyInterface;

/**
 * Export data as CSV string.
 */
class CsvExportStrategy implements ExportStrategyInterface
{
    /**
     * Generate CSV content from tabular data.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @param  array<int, string>  $headers
     */
    public function export(array $data, array $headers): string
    {
        $handle = fopen('php://temp', 'r+');

        // BOM for Arabic Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, $headers);

        foreach ($data as $row) {
            fputcsv($handle, array_values($row));
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    public function getMimeType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    public function getExtension(): string
    {
        return 'csv';
    }
}
