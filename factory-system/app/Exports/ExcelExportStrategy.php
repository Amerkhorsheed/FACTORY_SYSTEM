<?php

namespace App\Exports;

use App\Contracts\Export\ExportStrategyInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Export data as Excel (XLSX) content.
 */
class ExcelExportStrategy implements ExportStrategyInterface
{
    /**
     * Generate Excel content from tabular data.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @param  array<int, string>  $headers
     */
    public function export(array $data, array $headers): string
    {
        $collection = collect($data);

        $exportable = new class($collection, $headers) implements FromCollection, WithHeadings
        {
            public function __construct(
                private readonly Collection $data,
                private readonly array $columns,
            ) {}

            public function collection(): Collection
            {
                return $this->data->map(fn ($row) => array_values((array) $row));
            }

            /** @return array<int, string> */
            public function headings(): array
            {
                return $this->columns;
            }
        };

        return Excel::raw($exportable, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function getExtension(): string
    {
        return 'xlsx';
    }
}
