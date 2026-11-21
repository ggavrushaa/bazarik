<?php

namespace App\Support\Sms;

use Illuminate\Support\Facades\Log;

/** Драйвер для розробки: SMS не йдуть людям, а пишуться в storage/logs/sms.log */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $text): void
    {
        Log::build(['driver' => 'single', 'path' => storage_path('logs/sms.log')])
            ->info("SMS → {$phone}: {$text}");
    }
}
