<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi API RajaOngkir (Komerce V2)
    |--------------------------------------------------------------------------
    */

    'api_key' => env('RAJAONGKIR_API_KEY', ''),
    
    'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),

    'origin_city_id' => env('RAJAONGKIR_ORIGIN_CITY_ID', 160),

];