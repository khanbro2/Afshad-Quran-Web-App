<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    */
    'equranlibrary_base_url' => env('TAFSEER_IMPORT_BASE_URL', 'https://equranlibrary.com'),

    /*
    |--------------------------------------------------------------------------
    | Request settings
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('TAFSEER_IMPORT_TIMEOUT', 20),
    'retry_times' => (int) env('TAFSEER_IMPORT_RETRY_TIMES', 3),
    'sleep_milliseconds' => (int) env('TAFSEER_IMPORT_SLEEP_MS', 300),

    /*
    |--------------------------------------------------------------------------
    | Import behavior
    |--------------------------------------------------------------------------
    */
    'update_existing' => env('TAFSEER_IMPORT_UPDATE_EXISTING', false),

    /*
    |--------------------------------------------------------------------------
    | Tafseer slug mapping
    |--------------------------------------------------------------------------
    */
    'supported_tafaseer' => [
        'ibnekaseer' => [
            'title_urdu' => 'تفسیر ابن کثیر',
        ],
        'tafheemulquran' => [
            'title_urdu' => 'تفہیم القرآن',
        ],
        'maarifulquran' => [
            'title_urdu' => 'معارف القرآن',
        ],
        'tafseerusmani' => [
            'title_urdu' => 'تفسیر عثمانی',
        ],
        'bayanulquran' => [
            'title_urdu' => 'بیان القرآن',
        ],
        'bayanulquran_scrape' => [
            'title_urdu' => 'بیان القرآن',
        ],
        'arabic_jalalayn' => [
            'title_urdu' => 'تفسير الجلالين',
        ],
        'al_baydawi' => [
            'title_urdu' => 'تفسير البيضاوي',
        ],
        'maarifulquran_english' => [
            'title_urdu' => 'Maarif-ul-Quran (English)',
        ],
    ],

    'local_datasets' => [
        'arabic_jalalayn' => [
            'driver' => 'sql_insert',
            'path' => storage_path('datasets/quran/arabic_jalalayn.sql'),
            'title_urdu' => 'تفسير الجلالين',
            'title_english' => 'Tafsir al-Jalalayn',
            'author' => 'Jalal al-Din al-Mahalli / Jalal al-Din al-Suyuti',
            'language' => 'ar',
            'source_name' => 'Local Dataset',
            'source_url' => null,
            'sort_order' => 7,
        ],
        'bayanulquran' => [
            'driver' => 'json_tafseer_map',
            'path' => storage_path('datasets/quran/tafsir-bayan-ul-quran.json'),
            'title_urdu' => 'بیان القرآن',
            'title_english' => 'Bayan-ul-Quran',
            'author' => 'Dr. Israr Ahmed',
            'language' => 'ur',
            'source_name' => 'Local Dataset',
            'source_url' => null,
            'sort_order' => 5,
        ],
        'al_baydawi' => [
            'driver' => 'json_tafseer_map',
            'path' => storage_path('datasets/quran/tafsir-al-baydawi.json'),
            'title_urdu' => 'تفسير البيضاوي',
            'title_english' => 'Tafsir al-Baydawi',
            'author' => 'Nasir al-Din al-Baydawi',
            'language' => 'ar',
            'source_name' => 'Local Dataset',
            'source_url' => null,
            'sort_order' => 8,
        ],
        'maarifulquran_english' => [
            'driver' => 'json_tafseer_map',
            'path' => storage_path('datasets/quran/en-tafsir-maarif-ul-quran.json'),
            'title_urdu' => 'Maarif-ul-Quran (English)',
            'title_english' => 'Maarif-ul-Quran',
            'author' => 'Mufti Muhammad Shafi',
            'language' => 'en',
            'source_name' => 'Local Dataset',
            'source_url' => null,
            'sort_order' => 9,
        ],
    ],
];
