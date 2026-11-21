<?php

namespace Tests\Feature;

use App\Livewire\AuthModal;
use App\Livewire\AuthPage;
use App\Models\Customer;
use App\Models\PhoneOtp;
use App\Models\Product;
use App\Support\Cart;
use App\Support\Sms\SmsSender;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private object $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);

        $this->sms = new class implements SmsSender
        {
            /** @var list<array{0: string, 1: string}> */
            public array $sent = [];

            public function send(string $phone, string $text): void
            {
                $this->sent[] = [$phone, $text];
            }

            public function lastCode(): ?string
            {
                $text = end($this->sent)[1] ?? '';

                return preg_match('/(\d{4})$/', $text, $m) ? $m[1] : null;
            }
        };
        $this->app->instance(SmsSender::class, $this->sms);
        RateLimiter::clear('otp-send:ip:127.0.0.1');
    }

    private function wrongCode(): string
    {
        return $this->sms->lastCode() === '0000' ? '1111' : '0000';
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Вхід або реєстрація')->assertSee('Отримати код');
    }

    public function test_code_page_without_phone_redirects_to_login(): void
    {
        $this->get('/login/code')->assertRedirect(route('login'));
    }

    public function test_invalid_phone_is_rejected(): void
    {
        Livewire::test(AuthPage::class, ['step' => 'phone'])
            ->set('phone', '12345')
            ->call('sendCode')
            ->assertHasErrors('phone');

        $this->assertSame([], $this->sms->sent);
    }

    public function test_full_flow_for_new_customer_via_pages(): void
    {
        Livewire::test(AuthPage::class, ['step' => 'phone'])
            ->set('phone', '67 123 45 67')
            ->call('sendCode')
            ->assertHasNoErrors()
            ->assertRedirect(route('login.code'));

        $this->assertCount(1, $this->sms->sent);
        [$phone, $text] = $this->sms->sent[0];
        $this->assertSame('+380671234567', $phone);
        $this->assertMatchesRegularExpression('/^Код для входу в Bazarik: \d{4}$/', $text);

        $otp = PhoneOtp::query()->sole();
        $this->assertNotSame($this->sms->lastCode(), $otp->code_hash); // зберігаємо лише хеш
        $this->assertSame('+380671234567', session('auth.phone'));

        $this->get('/login/code')->assertOk()->assertSee('Надіслали SMS на +380 67 123 45 67');

        Livewire::test(AuthPage::class, ['step' => 'code'])
            ->set('code', $this->sms->lastCode())
            ->call('verify')
            ->assertHasNoErrors()
            ->assertRedirect(route('login.name'));

        $customer = Customer::query()->where('phone', '+380671234567')->sole();
        $this->assertNotNull($customer->phone_verified_at);
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertNotNull(PhoneOtp::query()->sole()->used_at);

        $this->get('/login/name')->assertOk()->assertSee('Як до вас звертатися?');

        Livewire::test(AuthPage::class, ['step' => 'name'])
            ->set('fullName', 'Олена Коваль')
            ->call('saveName')
            ->assertRedirect(route('account'));

        $customer->refresh();
        $this->assertSame('Олена', $customer->first_name);
        $this->assertSame('Коваль', $customer->last_name);
    }

    public function test_existing_customer_skips_name_and_goes_to_intended_url(): void
    {
        Customer::query()->create(['phone' => '+380671234567', 'first_name' => 'Олена']);

        $this->get('/account/addresses')->assertRedirect(route('login'));

        Livewire::test(AuthPage::class, ['step' => 'phone'])->set('phone', '0671234567')->call('sendCode');

        Livewire::test(AuthPage::class, ['step' => 'code'])
            ->set('code', $this->sms->lastCode())
            ->call('verify')
            ->assertRedirect(route('account.addresses'));

        $this->assertSame(1, Customer::query()->count());
        $this->assertAuthenticated('customer');
    }

    public function test_wrong_code_counts_attempts_and_locks_after_max(): void
    {
        Livewire::test(AuthPage::class, ['step' => 'phone'])->set('phone', '671234567')->call('sendCode');
        $wrong = $this->wrongCode();
        $page = Livewire::test(AuthPage::class, ['step' => 'code']);

        $page->set('code', $wrong)->call('verify')->assertHasErrors('code')->assertSee('Залишилось спроб: 4');
        $this->assertSame(1, PhoneOtp::query()->sole()->attempts);

        for ($i = 0; $i < 4; $i++) {
            $page->set('code', $wrong)->call('verify');
        }
        $page->assertSee('Забагато спроб. Надішліть новий код.');

        // Навіть правильний код уже не діє
        $page->set('code', $this->sms->lastCode())->call('verify')->assertHasErrors('code');
        $this->assertGuest('customer');
    }

    public function test_expired_code_is_rejected(): void
    {
        Livewire::test(AuthPage::class, ['step' => 'phone'])->set('phone', '671234567')->call('sendCode');
        $code = $this->sms->lastCode();

        $this->travel(6)->minutes();

        Livewire::test(AuthPage::class, ['step' => 'code'])
            ->set('code', $code)
            ->call('verify')
            ->assertHasErrors('code')
            ->assertSee('Код застарів');

        $this->assertGuest('customer');
    }

    public function test_resend_respects_cooldown_and_invalidates_previous_code(): void
    {
        Livewire::test(AuthPage::class, ['step' => 'phone'])->set('phone', '671234567')->call('sendCode');
        $first = $this->sms->lastCode();

        $page = Livewire::test(AuthPage::class, ['step' => 'code']);
        $page->call('resend')->assertHasErrors('code')->assertSee('Новий код можна отримати через');
        $this->assertCount(1, $this->sms->sent);

        $this->travel(61)->seconds();
        $page->call('resend')->assertHasNoErrors()->assertDispatched('code-resent');
        $this->assertCount(2, $this->sms->sent);

        if ($first !== $this->sms->lastCode()) {
            $page->set('code', $first)->call('verify')->assertHasErrors('code');
        }
        $page->set('code', $this->sms->lastCode())->call('verify')->assertHasNoErrors();
        $this->assertAuthenticated('customer');
    }

    public function test_same_number_within_cooldown_does_not_send_again(): void
    {
        Livewire::test(AuthPage::class, ['step' => 'phone'])->set('phone', '671234567')->call('sendCode');
        Livewire::test(AuthPage::class, ['step' => 'phone'])->set('phone', '671234567')->call('sendCode')->assertRedirect(route('login.code'));

        $this->assertCount(1, $this->sms->sent);
    }

    public function test_sending_is_throttled_per_phone(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(AuthPage::class, ['step' => 'phone'])->set('phone', '671234567')->call('sendCode')->assertHasNoErrors();
            $this->travel(61)->seconds();
        }

        Livewire::test(AuthPage::class, ['step' => 'phone'])
            ->set('phone', '671234567')
            ->call('sendCode')
            ->assertHasErrors('phone')
            ->assertSee('Забагато запитів коду');

        $this->assertCount(5, $this->sms->sent);
    }

    public function test_sending_is_throttled_per_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            Livewire::test(AuthPage::class, ['step' => 'phone'])->set('phone', '67'.str_pad((string) $i, 7, '0', STR_PAD_LEFT))->call('sendCode')->assertHasNoErrors();
        }

        Livewire::test(AuthPage::class, ['step' => 'phone'])
            ->set('phone', '991234567')
            ->call('sendCode')
            ->assertHasErrors('phone');

        $this->assertCount(20, $this->sms->sent);
    }

    public function test_modal_flow_merges_guest_cart_and_favorites(): void
    {
        $products = Product::query()->active()->inStock()->with('variants')->take(4)->get();
        app(Cart::class)->add($products[0]->variants->firstWhere('stock', '>', 0));
        app(Cart::class)->add($products[1]->variants->firstWhere('stock', '>', 0));

        $modal = Livewire::test(AuthModal::class)
            ->dispatch('open-auth')
            ->assertSet('open', true)
            ->assertSee('Вхід або реєстрація')
            ->set('phone', '671234567')
            ->call('sendCode')
            ->assertSet('step', 'code')
            ->assertSee('Надіслали на +380 67 123 45 67');

        $modal->set('code', $this->sms->lastCode())
            ->call('verify', [$products[1]->id, $products[2]->id, $products[3]->id, 999999, 'x'])
            ->assertHasNoErrors()
            ->assertSet('step', 'name')
            ->assertSee('Перенесли в кабінет 2 товари з кошика й 3 з обраного на цьому пристрої');

        $customer = Customer::query()->sole();
        $this->assertEqualsCanonicalizing(
            [$products[1]->id, $products[2]->id, $products[3]->id],
            $customer->favorites()->pluck('products.id')->all(),
        );
        // Кошик гостя лишився в сесії
        $this->assertCount(2, app(Cart::class)->raw());

        $modal->set('fullName', 'Олена')->call('saveName')->assertRedirect();
        $this->assertSame('Олена', $customer->refresh()->first_name);
        $this->assertNull($customer->last_name);
    }

    public function test_modal_logs_in_existing_customer_and_returns_to_page(): void
    {
        $customer = Customer::query()->create(['phone' => '+380671234567', 'first_name' => 'Олена']);
        $product = Product::query()->active()->first();
        $customer->favorites()->attach($product->id, ['created_at' => now()]);

        Livewire::test(AuthModal::class)
            ->dispatch('open-auth')
            ->set('phone', '671234567')
            ->call('sendCode')
            ->set('code', $this->sms->lastCode())
            ->call('verify', [$product->id])
            ->assertRedirect();

        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertSame(1, $customer->favorites()->count()); // без дублів
    }

    public function test_signed_in_heart_changes_sync_to_account(): void
    {
        $customer = Customer::query()->create(['phone' => '+380671234567']);
        [$a, $b] = Product::query()->active()->take(2)->pluck('id')->all();
        $customer->favorites()->attach($a, ['created_at' => now()]);

        $this->actingAs($customer, 'customer');
        Livewire::test(AuthModal::class)
            ->call('syncFavorites', [$b], [$a])
            ->assertReturned([$b]);

        $this->assertSame([$b], $customer->favorites()->pluck('products.id')->all());
    }

    public function test_logout(): void
    {
        $customer = Customer::query()->create(['phone' => '+380671234567']);

        $this->actingAs($customer, 'customer')->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest('customer');
    }

    public function test_signed_in_customer_is_redirected_from_login(): void
    {
        $customer = Customer::query()->create(['phone' => '+380671234567']);

        $this->actingAs($customer, 'customer')->get('/login')->assertRedirect(route('account'));
    }
}
