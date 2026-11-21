{{-- «Вийти»: POST /logout; заодно забуваємо обране на пристрої, щоб наступний на цьому пристрої його не бачив --}}
<form method="POST" action="{{ route('logout') }}" style="display: contents"
      x-data="{ forget() { try { localStorage.removeItem('bz:favorites'); localStorage.removeItem('bz:favorites-owner') } catch (e) {} } }" x-on:submit="forget()">
    @csrf
    @if ($side ?? false)
        <button type="submit" style="display: flex; align-items: center; gap: 12px; min-height: 44px; padding: 0 14px; border: 0; border-radius: var(--radius-m); background: none; color: var(--ink-muted); font: 700 15px var(--font-sans); cursor: pointer; text-align: left"><x-bz.icon name="logout" size="20" />Вийти</button>
    @else
        <button class="bz-btn bz-btn--ghost" type="submit" style="align-self: flex-start; color: var(--ink-muted)">Вийти</button>
    @endif
</form>
