{{-- «▶ Відео 0:24» поверх фото --}}
@php($play = '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4l13 8-13 8z"/></svg>')
@if ($product->video_url)
    <a class="pp-video" href="{{ $product->video_url }}" target="_blank" rel="noopener" style="{{ $style }}">{!! $play !!}{{ $video }}</a>
@else
    <span class="pp-video" style="{{ $style }}">{!! $play !!}{{ $video }}</span>
@endif
