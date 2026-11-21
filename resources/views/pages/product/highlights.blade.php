{{-- «Простими словами»: великі цифри з поясненням --}}
<div class="pp-highlights">
    @foreach ($highlights as $h)
        <div>
            <b>{{ $h['value'] }}</b>
            <span>{{ $h['text'] ?? '' }}</span>
        </div>
    @endforeach
</div>
