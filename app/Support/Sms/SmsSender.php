<?php

namespace App\Support\Sms;

/** Відправка SMS / Viber. Реального провайдера ще не обрано — локально пишемо в storage/logs/sms.log. */
interface SmsSender
{
    /** @param  string  $phone  +380XXXXXXXXX */
    public function send(string $phone, string $text): void;
}
