<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supabase Project
    |--------------------------------------------------------------------------
    |
    | URL proyek dan kredensial untuk Supabase Storage. Service key hanya
    | boleh hidup di env (Vercel / .env lokal) — jangan pernah masuk git.
    |
    */

    'url' => env('SUPABASE_URL', ''),

    'service_key' => env('SUPABASE_SERVICE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Storage Bucket
    |--------------------------------------------------------------------------
    |
    | Bucket publik untuk unggahan pengguna (avatar, sampul, foto resep).
    | File publik diakses tanpa autentikasi lewat URL object/public.
    |
    */

    'bucket' => env('SUPABASE_STORAGE_BUCKET', 'uploads'),

];
