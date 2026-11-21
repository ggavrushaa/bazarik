{{-- «Політика конфіденційності»: макета немає — проста текстова сторінка в каркасі інфосторінок. Текст надасть юрист. --}}
@component('pages.info.shell', ['title' => 'Політика конфіденційності', 'active' => 'info.privacy', 'maxWidth' => '760px', 'description' => 'Як Bazarik збирає, зберігає й використовує персональні дані покупців.'])
    @slot('mobile')
        @include('pages.info.legal-text', ['placeholder' => '[Текст політики конфіденційності — надасть юрист]'])
    @endslot

    <h1 class="h1" style="font-size: 40px; line-height: 46px">Політика конфіденційності</h1>
    @include('pages.info.legal-text', ['placeholder' => '[Текст політики конфіденційності — надасть юрист]'])
@endcomponent
