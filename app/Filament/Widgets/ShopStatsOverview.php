<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ShopStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $revenue = fn (Builder $query): float => (float) $query
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
            ->sum('total');

        $newToday = Order::query()->where('created_at', '>=', $today)->count();
        $newPending = Order::query()->where('status', OrderStatus::New)->count();
        $awaiting = Order::query()->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Packing])->count();

        $last7 = collect(range(6, 0))->map(fn (int $daysAgo): float => $revenue(
            Order::query()->whereBetween('created_at', [now()->subDays($daysAgo)->startOfDay(), now()->subDays($daysAgo)->endOfDay()]),
        ));

        return [
            Stat::make('Нових замовлень сьогодні', $newToday)
                ->description($newPending > 0 ? "Чекають на підтвердження: {$newPending}" : 'Усі нові підтверджені')
                ->descriptionIcon(Heroicon::OutlinedPhone)
                ->color($newPending > 0 ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index', ['tab' => 'new'])),
            Stat::make('Виручка сьогодні', money($revenue(Order::query()->where('created_at', '>=', $today))))
                ->description('Без скасованих і повернень')
                ->color('primary'),
            Stat::make('Виручка за 7 днів', money($last7->sum()))
                ->description('Включно з сьогодні')
                ->chart($last7->all())
                ->color('primary'),
            Stat::make('Чекають на відправку', $awaiting)
                ->description('Підтверджені й ті, що збираємо')
                ->descriptionIcon(Heroicon::OutlinedTruck)
                ->color($awaiting > 0 ? 'info' : 'gray')
                ->url(OrderResource::getUrl('index', ['tab' => 'in_work'])),
        ];
    }
}
