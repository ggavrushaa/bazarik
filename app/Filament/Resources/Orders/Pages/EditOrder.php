<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\OrderWorkflow;
use App\Models\Order;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/** Сторінка замовлення: дані покупця, доставка, оплата, товари й кнопки обробки */
class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Замовлення '.$this->getRecord()->number;
    }

    public function getSubheading(): ?string
    {
        /** @var Order $order */
        $order = $this->getRecord();

        return $order->status->getLabel().' · '.$order->created_at->format('d.m.Y H:i').' · '.money($order->total);
    }

    protected function getHeaderActions(): array
    {
        return OrderActions::all();
    }

    /** Ручна зміна статусу в формі проходить через ті самі правила, що й кнопки */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Order $record */
        $from = $record->status;
        $to = OrderResource::status($data['status'] ?? null) ?? $from;
        unset($data['status']);

        OrderWorkflow::transition($record, $to, $from, $data);

        return $record;
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
