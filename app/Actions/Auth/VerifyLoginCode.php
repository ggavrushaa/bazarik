<?php

namespace App\Actions\Auth;

use App\Models\Customer;
use App\Models\PhoneOtp;
use Illuminate\Validation\ValidationException;

/**
 * Крок 2 входу: перевіряємо код з SMS. Вхід = реєстрація: покупця з таким номером
 * знаходимо або створюємо. Неправильний код збільшує attempts; після max_attempts код згорає.
 */
class VerifyLoginCode
{
    /** @throws ValidationException */
    public function handle(string $phone, ?string $code, string $field = 'code'): Customer
    {
        $code = preg_replace('/\D+/', '', (string) $code);
        $length = (int) config('shop.otp.length');
        if (strlen($code) !== $length) {
            throw ValidationException::withMessages([$field => "Введіть {$length} цифри з SMS"]);
        }

        $otp = PhoneOtp::query()->where('phone', $phone)->whereNull('used_at')->latest('id')->first();
        $max = (int) config('shop.otp.max_attempts');

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages([$field => 'Код застарів. Надішліть новий.']);
        }
        if ($otp->attempts >= $max) {
            throw ValidationException::withMessages([$field => 'Забагато спроб. Надішліть новий код.']);
        }

        if (! hash_equals($otp->code_hash, SendLoginCode::hash($phone, $code))) {
            $otp->increment('attempts');
            $left = $max - $otp->attempts;

            throw ValidationException::withMessages([$field => $left > 0
                ? 'Неправильний код. Залишилось спроб: '.$left
                : 'Забагато спроб. Надішліть новий код.']);
        }

        $otp->forceFill(['used_at' => now()])->save();

        $customer = Customer::query()->firstOrCreate(['phone' => $phone]);
        $customer->forceFill(['phone_verified_at' => now()])->save();

        return $customer;
    }
}
