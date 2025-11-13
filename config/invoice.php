<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | These values are used in invoice generation
    |
    */

    'company' => [
        'name' => env('COMPANY_NAME', 'Pourier Photo'),
        'address' => env('COMPANY_ADDRESS', '123 Rue de la Photo'),
        'city' => env('COMPANY_CITY', 'Paris'),
        'postal_code' => env('COMPANY_POSTAL_CODE', '75001'),
        'country' => env('COMPANY_COUNTRY', 'France'),
        'siret' => env('COMPANY_SIRET', ''),
        'vat_number' => env('COMPANY_VAT_NUMBER', ''),
        'email' => env('COMPANY_EMAIL', 'contact@pourier.com'),
        'phone' => env('COMPANY_PHONE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        'tax_rate' => env('INVOICE_TAX_RATE', 0.20), // 20% VAT
        'currency' => env('INVOICE_CURRENCY', 'EUR'),
        'currency_symbol' => env('INVOICE_CURRENCY_SYMBOL', '€'),
    ],
];
