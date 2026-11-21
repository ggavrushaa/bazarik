{{-- Текстовий лист: виводимо без HTML-екранування --}}
Нове повідомлення з форми «Напишіть нам» на {!! config('app.url') !!}

Ім’я: {!! $contactMessage->name !!}
Телефон: {!! \App\Support\Phone::format($contactMessage->phone) !!}
Надіслано: {!! $contactMessage->created_at->tz(config('app.timezone'))->format('d.m.Y H:i') !!}

{!! $contactMessage->message !!}
