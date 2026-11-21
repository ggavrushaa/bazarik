{{-- Смужка прогресу до безкоштовної доставки (0–100) --}}
@props(['value' => 0])
<span role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $value }}" aria-label="До безкоштовної доставки"
      {{ $attributes->merge(['style' => 'display: block; height: 6px; border-radius: 3px; background: #ffd9bd; overflow: hidden']) }}>
    <span style="display: block; width: {{ $value }}%; height: 100%; background: var(--brand)"></span>
</span>
