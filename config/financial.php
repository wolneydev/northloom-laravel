<?php

declare(strict_types=1);

$currencies = explode(',', (string) env('FINANCIAL_CURRENCIES', 'BRL,USD,EUR'));

return [
    'currencies' => array_values(array_unique(array_filter(array_map(
        static fn (string $currency): string => strtoupper(trim($currency)),
        $currencies,
    )))),
];
