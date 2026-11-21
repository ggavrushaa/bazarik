<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\StockNotification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * «Повідомити мене» на сторінці товару, якого немає в наявності (StateOutOfStock / DeskOutOfStock).
 * Зберігаємо телефон; SMS, коли товар повернеться на склад, — окремою задачею.
 */
class ProductNotify extends Component
{
    #[Locked]
    public int $productId;

    #[Locked]
    public ?int $variantId = null;

    /** 9 цифр після +380 */
    public string $phone = '';

    #[Locked]
    public ?string $saved = null;

    public function mount(int $productId, ?int $variantId = null): void
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
    }

    public function save(): void
    {
        $digits = self::normalize($this->phone);

        if ($digits === null) {
            $this->addError('phone', 'Перевірте номер: 9 цифр після +380, наприклад 67 123 45 67');

            return;
        }

        $key = 'stock-notify:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('phone', 'Забагато спроб. Спробуйте за хвилину.');

            return;
        }
        RateLimiter::hit($key, 60);

        $product = Product::query()->active()->findOrFail($this->productId);

        StockNotification::query()->firstOrCreate([
            'product_id' => $product->id,
            'product_variant_id' => $this->variantId,
            'phone' => '+380'.$digits,
            'notified_at' => null,
        ]);

        $this->resetErrorBag();
        $this->saved = sprintf('+380 %s %s %s %s', substr($digits, 0, 2), substr($digits, 2, 3), substr($digits, 5, 2), substr($digits, 7, 2));
    }

    /** «067 123-45-67», «+380671234567», «67 123 45 67» → «671234567»; інакше null */
    public static function normalize(string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '380')) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[1-9]\d{8}$/', $digits) ? $digits : null;
    }

    public function render(): View
    {
        return view('livewire.product-notify');
    }
}
