<?php

namespace App\Livewire;

use App\Mail\ContactMessage as ContactMessageMail;
use App\Models\ContactMessage;
use App\Support\Phone;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * «Напишіть нам» на сторінці «Контакти» (MobileContacts / DeskContacts).
 * Зберігаємо повідомлення й надсилаємо лист на config('shop.email').
 */
class ContactForm extends Component
{
    /** Скільки повідомлень з однієї IP за 10 хвилин */
    public const MAX_PER_WINDOW = 3;

    public string $name = '';

    /** 9 цифр після +380 (приймаємо й повний номер) */
    public string $phone = '';

    public string $message = '';

    /** Пастка для ботів: поле приховане, людина його не заповнить */
    public string $website = '';

    #[Locked]
    public bool $sent = false;

    /** Префікс id полів: на сторінці дві копії форми (мобільна й десктопна розмітка) */
    #[Locked]
    public string $prefix = 'contact';

    public function mount(string $prefix = 'contact'): void
    {
        $this->prefix = $prefix;

        $customer = auth('customer')->user();
        if ($customer) {
            $this->name = (string) ($customer->first_name ?? '');
            $this->phone = Phone::format($customer->phone ?? null, national: true);
        }
    }

    public function send(): void
    {
        $this->name = trim($this->name);
        $this->message = trim($this->message);

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'name.required' => 'Як до вас звертатися?',
            'name.max' => 'Ім’я задовге — до 100 символів',
            'phone.required' => 'Вкажіть телефон — передзвонимо або напишемо у Viber',
            'phone.max' => 'Перевірте номер: 9 цифр після +380, наприклад 67 123 45 67',
            'message.required' => 'Напишіть, чим допомогти',
            'message.min' => 'Напишіть трохи більше — хоча б кілька слів',
            'message.max' => 'Повідомлення задовге — до 2000 символів',
        ]);

        $phone = Phone::normalize($this->phone);
        if ($phone === null) {
            $this->addError('phone', 'Перевірте номер: 9 цифр після +380, наприклад 67 123 45 67');

            return;
        }

        // Бот заповнив приховане поле — вдаємо, що все добре
        if ($this->website !== '') {
            $this->sent = true;

            return;
        }

        $key = 'contact-form:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_WINDOW)) {
            $this->addError('message', 'Забагато повідомлень поспіль. Спробуйте за кілька хвилин або напишіть нам у Viber.');

            return;
        }
        RateLimiter::hit($key, 600);

        $contactMessage = ContactMessage::query()->create([
            'customer_id' => auth('customer')->id(),
            'name' => $this->name,
            'phone' => $phone,
            'message' => $this->message,
            'ip' => request()->ip(),
        ]);

        // Повідомлення вже збережене — якщо пошта недоступна, менеджер побачить його в адмінці
        try {
            Mail::to(self::recipient())->send(new ContactMessageMail($contactMessage));
        } catch (Throwable $e) {
            report($e);
        }

        $this->reset('message');
        $this->resetErrorBag();
        $this->sent = true;
    }

    /** config('shop.email'); поки там заглушка «[email]@…» — шлемо на адресу відправника з config('mail.from') */
    public static function recipient(): string
    {
        $email = (string) config('shop.email');

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : (string) config('mail.from.address');
    }

    public function render(): View
    {
        return view('livewire.contact-form');
    }
}
