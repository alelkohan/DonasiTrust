<?php

return [
    /*
    | Secret untuk kode verifikasi kuitansi (HMAC-SHA256).
    | Jangan pernah dipakai default-nya di produksi.
    */
    'receipt_secret' => env('DONASI_RECEIPT_SECRET', ''),

    /* Gateway aktif: "mock" atau "midtrans". */
    'gateway' => env('PAYMENT_GATEWAY', 'mock'),

    'mock_webhook_token' => env('MOCK_WEBHOOK_TOKEN', 'demo-webhook-token'),

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY', ''),
        'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    ],

    /* Nominal donasi cepat yang ditawarkan di form (rupiah). */
    'quick_amounts' => [20000, 50000, 100000, 250000, 500000],

    /*
    | Aturan distribusi tahap pencairan (anti "bertahap di atas kertas").
    | Batas 70% sengaja longgar: ada proyek yang memang berat di depan.
    | Di atas 40% kampanye tetap boleh tayang tapi ditandai ke publik.
    */
    'milestone_first_max_share' => 0.70,
    'milestone_min_two_above' => 10_000_000,

    'min_donation' => 10000,
    'max_donation' => 500000000,

    /* Batas ukuran unggahan (KB). */
    'max_upload_kb' => 4096,
];
