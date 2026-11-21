{{-- «Головна / Одяг / Худі та світшоти» --}}
<nav class="crumbs pp-crumbs" aria-label="Ви тут">
    <a href="{{ route('home') }}">Головна</a>
    @foreach (collect([$category?->parent, $category])->filter() as $crumb)
        <span aria-hidden="true">/</span>
        <a href="{{ route('catalog', $crumb) }}">{{ $crumb->name }}</a>
    @endforeach
</nav>
