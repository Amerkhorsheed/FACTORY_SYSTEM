<?php

/**
 * PDF generation configuration.
 */
return [
    'paper_size' => 'a4',
    'orientation' => 'portrait',
    'default_font' => env('DOMPDF_DEFAULT_FONT', 'dejavu sans'),
    'dpi' => 150,
    'unicode' => true,
    'font_subsetting' => true,
    'remote_enabled' => true,
    'storage_path' => 'private/pdfs',
    'fonts_dir' => resource_path('fonts/'),
];
