{{-- Каталог категорії, «Знижки» й результати пошуку: MobileCatalog / DeskCatalog, StateSearch / DeskSearchResults --}}
<x-layouts.app :title="$title" :category="$category">
    <livewire:catalog :mode="$mode" :category-id="$category?->id" :q="$q ?? ''" />
</x-layouts.app>
