<?php

/**
 * Money configuration.
 *
 * All monetary values are stored as integers in the smallest currency unit.
 */
return [
    'default_currency' => env('FACTORY_CURRENCY', 'SYP'),

    'currencies' => [
        'SYP' => ['symbol' => 'ل.س', 'name' => 'الليرة السورية', 'precision' => 0],
        'USD' => ['symbol' => '$', 'name' => 'الدولار الأمريكي', 'precision' => 2],
    ],

    'storage_precision' => 0,
];
