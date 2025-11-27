<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base de la API de Ecuatracker / GPSWOX
    |--------------------------------------------------------------------------
    */
    'base_url' => env('ECUATRACKER_BASE_URL', 'https://www.ecuatracker.com/api'),

    /*
    |--------------------------------------------------------------------------
    | user_api_hash por defecto
    |--------------------------------------------------------------------------
    |
    | Este hash es el que ya usas en Postman. Si en el futuro manejas
    | un hash por cliente, esto se podrá sobreescribir en runtime.
    |
    */
    'user_api_hash' => env('ECUATRACKER_USER_API_HASH', ''),

    /*
    |--------------------------------------------------------------------------
    | Timeout en segundos para llamadas HTTP
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('ECUATRACKER_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Mapeo de tipos de reporte
    |--------------------------------------------------------------------------
    |
    | En la API real, type=1 puede ser "General information", pero aquí
    | lo exponemos como "analisis_recorrido_km".
    |
    */
    'report_types' => [
        'analisis_recorrido_km' => (int) env('ECUATRACKER_REPORT_TYPE_ANALISIS_RECORRIDO', 1),
    ],
];
