<?php

namespace App\Actions\Auth;

use App\Models\PhoneOtp;
use App\Support\Phone;
use App\Support\Sms\SmsSender;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Крок 1 входу: перевіряємо номер, генеруємо одноразовий код, зберігаємо лише хеш і шлемо SMS.
 * Спільне для сторінок /login… (телефон) і модалки AuthModal (десктоп).
 */
class SendLoginCode
{
    public function __construct(private readonly SmsSender $sms) {}

    /**
     * @param  string  $field  поле форми, до якого чіпляємо помилку (phone на кроці 1, code — при «Надіслати ще раз»)
     * @return string нормалізований номер +380XXXXXXXXX
     *
     * @throws ValidationException
     */
    public function handle(?string $rawPhone, ?string $ip, string $field = 'phone', bool $resend = false): string
    {
        $phone = Phone::normalize($rawPhone);
        if ($phone === null) {
            throw ValidationException::withMessages([$field => 'Перевірте номер: 9 цифр після +380, наприклад 67 123 45 67']);
        }

        // Код ще свіжий — не шлемо новий (напр., повернулися «Змінити номер» і ввели той самий)
        $cooldown = self::secondsUntilResend($phone);
        if ($cooldown > 0) {
            if ($resend) {
                throw ValidationException::withMessages([$field => 'Новий код можна отримати через '.self::clock($cooldown)]);
            }

            return $phone;
        }

        $this->throttle('otp-send:phone:'.$phone, (int) config('shop.otp.max_sends_per_phone', 5), $field);
        if ($ip) {
            $this->throttle('otp-send:ip:'.$ip, (int) config('shop.otp.max_sends_per_ip', 20), $field);
        }

        $length = (int) config('shop.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        // Попередні коди цього номера більше не діють
        PhoneOtp::query()->where('phone', $phone)->whereNull('used_at')->update(['used_at' => now()]);

        PhoneOtp::query()->create([
            'phone' => $phone,
            'code_hash' => self::hash($phone, $code),
            'expires_at' => now()->addMinutes((int) config('shop.otp.ttl_minutes')),
            'ip' => $ip,
        ]);

        $this->sms->send($phone, "Код для входу в Bazarik: {$code}");

        return $phone;
    }

    public static function hash(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone.'|'.$code, (string) config('app.key'));
    }

    /** Скільки секунд лишилося до «Надіслати ще раз» (0 — можна) */
    public static function secondsUntilResend(string $phone): int
    {
        $last = PhoneOtp::query()->where('phone', $phone)->latest('id')->first();
        if (! $last || ! $last->isUsable()) {
            return 0;
        }

        $at = $last->created_at->copy()->addSeconds((int) config('shop.otp.resend_after_seconds'));

        return max(0, (int) ceil(now()->diffInSeconds($at, false)));
    }

    /** 42 → «0:42» */
    public static function clock(int $seconds): string
    {
        return intdiv($seconds, 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
    }

    private function throttle(string $key, int $max, string $field): void
    {
        $decay = (int) config('shop.otp.throttle_decay_seconds', 3600);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));
            throw ValidationException::withMessages([$field => "Забагато запитів коду. Спробуйте за {$minutes} хв або напишіть нам у Viber."]);
        }

        RateLimiter::hit($key, $decay);
    }
}
