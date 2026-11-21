<?php

namespace App\Actions\Auth;

use App\Models\Customer;
use App\Models\PhoneOtp;
use Illuminate\Support\Facades\RateLimiter;
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

        // Ліміт перевірок з однієї IP — проти перебору кодів по різних номерах
        $ipKey = 'otp-verify:'.request()->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 30)) {
            throw ValidationException::withMessages([$field => 'Забагато спроб. Спробуйте за кілька хвилин.']);
        }
        RateLimiter::hit($ipKey, 600);

        $otp = PhoneOtp::query()->where('phone', $phone)->whereNull('used_at')->latest('id')->first();
        $max = (int) config('shop.otp.max_attempts');

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages([$field => 'Код застарів. Надішліть новий.']);
        }
        // Спробу «витрачаємо» атомарно ДО порівняння: паралельні запити не обійдуть ліміт
        $spent = PhoneOtp::query()->whereKey($otp->id)
            ->whereNull('used_at')
            ->where('attempts', '<', $max)
            ->increment('attempts');
        if ($spent === 0) {
            throw ValidationException::withMessages([$field => 'Забагато спроб. Надішліть новий код.']);
        }

        if (! hash_equals($otp->code_hash, SendLoginCode::hash($phone, $code))) {
            $left = $max - $otp->refresh()->attempts;

            throw ValidationException::withMessages([$field => $left > 0
                ? 'Неправильний код. Залишилось спроб: '.$left
                : 'Забагато спроб. Надішліть новий код.']);
        }

        // Код одноразовий: використати його може лише один запит
        $used = PhoneOtp::query()->whereKey($otp->id)->whereNull('used_at')->update(['used_at' => now()]);
        if ($used === 0) {
            throw ValidationException::withMessages([$field => 'Код уже використано. Надішліть новий.']);
        }

        $customer = Customer::query()->firstOrCreate(['phone' => $phone]);
        $customer->forceFill(['phone_verified_at' => now()])->save();

        return $customer;
    }
}
