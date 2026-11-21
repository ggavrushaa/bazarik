<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Поле фото для моделей, де шлях може бути як на диску public (завантажене в адмінці),
 * так і файлом з public/ («/images/demo/…» — демо-фото з сидера) або зовнішнім URL.
 */
class ImageField
{
    public static function isExternal(?string $path): bool
    {
        return Str::startsWith((string) $path, ['/', 'http://', 'https://']);
    }

    /** Повний URL фото для прев'ю в таблицях і формах */
    public static function url(?string $path): ?string
    {
        return match (true) {
            blank($path) => null,
            Str::startsWith($path, ['http://', 'https://']) => $path,
            Str::startsWith($path, '/') => asset(ltrim($path, '/')),
            default => Storage::disk('public')->url($path),
        };
    }

    /** @return array{0: ImageEntry, 1: FileUpload} */
    public static function make(string $name, string $directory, string $label = 'Фото'): array
    {
        $external = fn (?Model $record): bool => $record && self::isExternal($record->getAttribute($name));

        return [
            ImageEntry::make($name.'_preview')
                ->label($label)
                ->state(fn (?Model $record): ?string => self::url($record?->getAttribute($name)))
                ->imageHeight(120)
                ->helperText('Демо-фото. Завантажте нове, щоб замінити.')
                ->visible($external),
            FileUpload::make($name)
                ->label(fn (?Model $record): string => $external($record) ? 'Замінити фото' : $label)
                ->image()
                ->disk('public')
                ->directory($directory)
                ->visibility('public')
                ->imageEditor()
                ->maxSize(5120)
                ->formatStateUsing(fn (?string $state): ?string => self::isExternal($state) ? null : $state)
                // Не затираємо демо-фото, якщо нового не завантажили
                ->dehydrated(fn (?Model $record, $state): bool => filled($state) || ! $external($record)),
        ];
    }
}
