<?php

namespace App\Contracts\Export;

interface ExportStrategyInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $data
     * @param  array<int, string>  $headers
     */
    public function export(array $data, array $headers): string;

    public function getMimeType(): string;

    public function getExtension(): string;
}
