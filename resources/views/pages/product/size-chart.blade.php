{{-- Таблиця розмірів; рядок обраного розміру підсвічений (is-match) --}}
@php($chart = $product->size_chart)
<div class="bz-table-wrap">
    <table class="bz-table">
        @if (! empty($chart['title']))
            <caption style="caption-side: top; text-align: left; padding: 12px 12px 8px; font-size: 16px; font-weight: 800">{{ $chart['title'] }}</caption>
        @endif
        <thead>
            <tr>
                @foreach ($chart['columns'] ?? [] as $col)<th scope="col">{{ $col }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($chart['rows'] ?? [] as $row)
                <tr :class="{ 'is-match': size === @js($row[0] ?? null) }">
                    @foreach ($row as $i => $cell)
                        @if ($i === 0)<th scope="row">{{ $cell }}</th>@else<td>{{ $cell }}</td>@endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
