<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ProductResource::normalizeFormData($data);
    }

    /** Товар без кольорів і розмірів = рівно один варіант (див. CLAUDE.md) */
    protected function afterCreate(): void
    {
        /** @var Product $product */
        $product = $this->getRecord();

        if ($product->is_apparel || $product->variants()->exists()) {
            return;
        }

        $product->variants()->create([
            'sku' => self::defaultSku($product),
            'stock' => max(0, (int) ($this->data['initial_stock'] ?? 0)),
            'is_active' => true,
        ]);
    }

    public static function defaultSku(Product $product): string
    {
        $sku = 'BZ-P'.$product->id;
        $suffix = 1;
        while (ProductVariant::query()->where('sku', $sku)->exists()) {
            $sku = 'BZ-P'.$product->id.'-'.$suffix++;
        }

        return $sku;
    }

    protected function getRedirectUrl(): string
    {
        return ProductResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
