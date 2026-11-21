<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $count = fn (array $statuses): int => Order::query()->whereIn('status', $statuses)->count();
        $new = [OrderStatus::New];
        $inWork = [OrderStatus::Confirmed, OrderStatus::Packing];
        $onTheWay = [OrderStatus::Shipped, OrderStatus::Arrived];

        return [
            'all' => Tab::make('Усі'),
            'new' => Tab::make('Нові')
                ->badge($count($new) ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $new)),
            'in_work' => Tab::make('До відправки')
                ->badge($count($inWork) ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $inWork)),
            'on_the_way' => Tab::make('В дорозі')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $onTheWay)),
            'done' => Tab::make('Завершені')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [OrderStatus::Completed, OrderStatus::Returned, OrderStatus::Cancelled])),
        ];
    }
}
