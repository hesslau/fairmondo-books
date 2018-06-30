<?php
return [
    'default' => [
        'ProductLanguage' => 'ger'
    ],
    'whitelist' => [
        'CurrencyCode' => 'EUR',
        'CountryCode' => 'DE',
        'ProductForm' => ['BA', 'BB', 'BC', 'BG', 'BH', 'BI', 'BP', 'BZ', 'AC', 'AI', 'VI', 'VO', 'ZE', 'DA', 'DG', 'PC'],
    ],
    'invalidAuthors' => ['%Anonym%','%ohne Autor%','%nknown%','%ohne autor%','%anonym%'],
    'invalidAudience' => [16,17,18]
];