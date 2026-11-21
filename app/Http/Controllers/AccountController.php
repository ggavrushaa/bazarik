<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/** Кабінет покупця: AccountHome / AccountOrders / AccountOrder / AccountFavorites / AccountAddresses; десктоп — DeskAccount / DeskAddresses */
class AccountController extends Controller
{
    /** Статуси «в роботі» — для картки активного замовлення й чипа «Активні» */
    public const ACTIVE = [OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Packing, OrderStatus::Shipped, OrderStatus::Arrived];

    public function home(): View
    {
        $customer = $this->customer();

        return view('pages.account.home', [
            'customer' => $customer,
            'activeOrder' => $customer->orders()->whereIn('status', self::ACTIVE)->with('items.product.mainImage')->first(),
            'favorites' => $customer->favorites()->active()->with(['mainImage', 'variants'])->latest('favorites.created_at')->limit(4)->get(),
            'favoritesCount' => $customer->favorites()->count(),
            'addressesCount' => $customer->addresses()->count(),
        ]);
    }

    public function orders(): View
    {
        return view('pages.account.orders', ['customer' => $this->customer()]);
    }

    public function order(Order $order): View
    {
        $customer = $this->customer();
        abort_unless($order->customer_id === $customer->id, 404);

        $order->load('items.product.mainImage');

        return view('pages.account.order', ['customer' => $customer, 'order' => $order]);
    }

    public function addresses(): View
    {
        return view('pages.account.addresses', ['customer' => $this->customer()]);
    }

    /** Обране: гостям — з пристрою (localStorage) з плашкою «увійдіть», покупцям — з кабінету */
    public function favorites(): View
    {
        return view('pages.account.favorites', ['customer' => auth('customer')->user()]);
    }

    private function customer(): Customer
    {
        return auth('customer')->user();
    }

    /** Звертання у кличному відмінку для найчастіших імен: Олена → Олено, Марія → Маріє; інші — як є */
    public static function vocative(?string $name): string
    {
        $name = trim((string) $name);

        return match (true) {
            $name === '' => '',
            (bool) preg_match('/ія$/u', $name) => mb_substr($name, 0, -1).'є',
            (bool) preg_match('/[^ія]а$/u', $name) => mb_substr($name, 0, -1).'о',
            default => $name,
        };
    }

    /** «Відділення № 25, Київ» / «Поштомат № 3412, Київ» / «вул. Хрещатик, 12, кв. 3, Київ» */
    public static function place(Order|CustomerAddress $model): string
    {
        $point = trim((string) $model->warehouse);

        $title = match ($model->delivery_method) {
            DeliveryMethod::NovaPoshtaCourier => collect([$model->street, $model->building, $model->apartment ? 'кв. '.$model->apartment : null])->filter()->implode(', '),
            DeliveryMethod::NovaPoshtaLocker => ctype_digit($point) ? 'Поштомат № '.$point : $point,
            DeliveryMethod::Pickup => 'Самовивіз',
            default => ctype_digit($point) ? 'Відділення № '.$point : $point,
        };

        return collect([$title, $model->city])->filter()->implode(', ');
    }

    /** Клас бейджа статусу: у дорозі — жовтий, отримано — зелений, решта — сірий */
    public static function badge(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Completed => 'bz-badge bz-badge--stock',
            OrderStatus::Returned, OrderStatus::Cancelled => 'bz-badge',
            default => 'bz-badge bz-badge--low',
        };
    }

    /** Фільтр списку замовлень: all | active | returns */
    public static function filter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'active' => $query->whereIn('status', self::ACTIVE),
            'returns' => $query->where('status', OrderStatus::Returned),
            default => $query,
        };
    }
}
