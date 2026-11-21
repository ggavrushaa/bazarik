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

    // Контакти й реквізити. Поки не погоджені — показуємо заглушки в [дужках], як у прототипі
    'phone' => env('SHOP_PHONE', '[+380 XX XXX XX XX]'),
    'email' => env('SHOP_EMAIL', '[email]@bazarik.com.ua'),
    'hours' => env('SHOP_HOURS', 'Пн–Пт 9:00–19:00 · Сб 10:00–16:00'),
    'hours_short' => env('SHOP_HOURS_SHORT', 'Пн–Пт 9:00–19:00'),
    'warehouse_city' => env('SHOP_WAREHOUSE_CITY', '[місто]'),
    'company' => env('SHOP_COMPANY', '[ФОП / ТОВ — реквізити]'),
    'viber' => env('SHOP_VIBER_URL', '#viber'),
    'telegram' => env('SHOP_TELEGRAM_URL', '#tg'),

    // Доставка Новою Поштою: орієнтовна ціна до відділення, якщо не дотягли до безкоштовної
    'delivery_cost' => (int) env('SHOP_DELIVERY_COST', 70),
    // Комісія НП за накладений платіж: 2% + 20 ₴
    'cod_fee_percent' => (float) env('SHOP_COD_FEE_PERCENT', 2),
    'cod_fee_fixed' => (int) env('SHOP_COD_FEE_FIXED', 20),
    // Скільки тримаємо замовлення, якщо оплата карткою не пройшла
    'payment_hold_hours' => (int) env('SHOP_PAYMENT_HOLD_HOURS', 2),

    'otp' => [
        'length' => 4,
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_after_seconds' => 60,
    ],
];
