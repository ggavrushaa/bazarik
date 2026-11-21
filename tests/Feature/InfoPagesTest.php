<?php

namespace Tests\Feature;

use App\Livewire\ContactForm;
use App\Mail\ContactMessage as ContactMessageMail;
use App\Models\ContactMessage;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class InfoPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_delivery_page_takes_conditions_from_config(): void
    {
        config([
            'shop.same_day_cutoff' => '14:30',
            'shop.free_delivery_from' => 1700,
            'shop.delivery_cost' => 75,
            'shop.courier_cost' => 130,
            'shop.cod_fee_percent' => 2.5,
            'shop.cod_fee_fixed' => 25,
            'shop.hours_short' => 'Пн–Сб 8:00–20:00',
        ]);

        $this->get(route('info.delivery'))
            ->assertOk()
            ->assertSee('Доставка Новою Поштою')
            ->assertSee('Замовлення до 14:30 відправляємо того ж дня')
            ->assertSee('Від <b>'.money(1700).'</b> доставка безкоштовна', false)
            ->assertSee('≈ '.money(75), false)
            ->assertSee('≈ '.money(130), false)
            ->assertSee('Комісія Нової Пошти 2,5% + '.money(25), false)
            ->assertSee('Безкоштовно — [N] днів')
            ->assertSee('Пишіть — відповідаємо до 10 хвилин, Пн–Сб 8:00–20:00')
            ->assertSee('Публічна оферта');
    }

    public function test_returns_page_uses_return_days_and_warranty(): void
    {
        config(['shop.return_days' => 30, 'shop.warranty_months' => 24, 'shop.viber' => 'viber://chat?number=380000000000']);

        $this->get(route('info.returns'))
            ->assertOk()
            ->assertSee('Як повернути')
            ->assertSee('<b>30 днів</b> після отримання', false)
            ->assertSee('гарантія 24 місяці')
            ->assertSee('Оформити повернення у Viber')
            ->assertSee('viber://chat?number=380000000000', false)
            ->assertSee('id="warranty"', false);
    }

    public function test_contacts_page_shows_messengers_phone_hours_and_company(): void
    {
        config([
            'shop.phone' => '+380 44 000 00 00',
            'shop.hours' => 'Пн–Пт 9:00–19:00 · Сб 10:00–16:00',
            'shop.company' => 'ФОП Тестовий',
            'shop.email' => 'shop@example.com',
            'shop.telegram' => 'https://t.me/bazarik_test',
        ]);

        $this->get(route('info.contacts'))
            ->assertOk()
            ->assertSee('Найшвидше — в месенджерах')
            ->assertSee('tel:+380440000000', false)
            ->assertSee('Пн–Пт 9:00–19:00 · Сб 10:00–16:00 · Нд — вихідний')
            ->assertSee('ФОП Тестовий · [юридична адреса] · shop@example.com')
            ->assertSee('https://t.me/bazarik_test', false)
            ->assertSeeLivewire(ContactForm::class);
    }

    public function test_offer_and_privacy_are_placeholders(): void
    {
        $this->get(route('info.offer'))->assertOk()->assertSee('[Текст публічної оферти — надасть юрист]');
        $this->get(route('info.privacy'))->assertOk()->assertSee('[Текст політики конфіденційності — надасть юрист]');
    }

    public function test_contact_form_stores_message_and_emails_shop(): void
    {
        Mail::fake();
        config(['shop.email' => 'shop@example.com']);

        Livewire::test(ContactForm::class)
            ->set('name', 'Олена')
            ->set('phone', '067 123-45-67')
            ->set('message', 'Чи є термос у синьому кольорі?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('Дякуємо, повідомлення надіслано');

        $message = ContactMessage::sole();
        $this->assertSame(['Олена', '+380671234567', 'Чи є термос у синьому кольорі?'], [$message->name, $message->phone, $message->message]);

        Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo('shop@example.com') && $mail->contactMessage->is($message));
    }

    public function test_contact_form_falls_back_to_mail_from_while_shop_email_is_placeholder(): void
    {
        Mail::fake();
        config(['shop.email' => '[email]@bazarik.com.ua', 'mail.from.address' => 'hello@bazarik.com.ua']);

        Livewire::test(ContactForm::class)
            ->set('name', 'Олена')->set('phone', '671234567')->set('message', 'Добрий день, питання')
            ->call('send')->assertHasNoErrors();

        Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo('hello@bazarik.com.ua'));
    }

    public function test_contact_form_validates(): void
    {
        Mail::fake();

        Livewire::test(ContactForm::class)
            ->call('send')
            ->assertHasErrors(['name', 'phone', 'message']);

        Livewire::test(ContactForm::class)
            ->set('name', 'Олена')->set('phone', '12345')->set('message', 'Добрий день')
            ->call('send')
            ->assertHasErrors('phone')
            ->assertSee('Перевірте номер');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_contact_form_is_rate_limited(): void
    {
        Mail::fake();
        RateLimiter::clear('contact-form:127.0.0.1');

        for ($i = 0; $i < ContactForm::MAX_PER_WINDOW; $i++) {
            Livewire::test(ContactForm::class)
                ->set('name', 'Олена')->set('phone', '671234567')->set('message', 'Повідомлення '.$i)
                ->call('send')->assertHasNoErrors();
        }

        Livewire::test(ContactForm::class)
            ->set('name', 'Олена')->set('phone', '671234567')->set('message', 'Ще одне')
            ->call('send')
            ->assertHasErrors('message')
            ->assertSet('sent', false);

        $this->assertSame(ContactForm::MAX_PER_WINDOW, ContactMessage::count());
    }

    public function test_honeypot_drops_bots_silently(): void
    {
        Mail::fake();

        Livewire::test(ContactForm::class)
            ->set('name', 'Bot')->set('phone', '671234567')->set('message', 'Buy cheap stuff')->set('website', 'http://spam')
            ->call('send')
            ->assertSet('sent', true);

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }
}
