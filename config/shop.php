<?php

/*
 * Умови магазину. Значення, що в прототипі стоять у [дужках], ще на погодженні —
 * тому вони в .env, а не в коді.
 */
return [
    'currency' => 'UAH',
    'currency_symbol' => '₴',

    // Поріг безкоштовної доставки, грн
    'free_delivery_from' => (int) env('SHOP_FREE_DELIVERY_FROM', 1500),

    // До котрої години відправляємо сьогодні
    'same_day_cutoff' => env('SHOP_SAME_DAY_CUTOFF', '15:00'),

    'warranty_months' => (int) env('SHOP_WARRANTY_MONTHS', 12),
    'return_days' => (int) env('SHOP_RETURN_DAYS', 14),

    'phone' => env('SHOP_PHONE'),

    'otp' => [
        'length' => 4,
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_after_seconds' => 60,
    ],
];
