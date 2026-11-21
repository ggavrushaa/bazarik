{{-- «Публічна оферта»: макета немає — проста текстова сторінка в каркасі інфосторінок. Текст надасть юрист. --}}
@component('pages.info.shell', ['title' => 'Публічна оферта', 'active' => 'info.offer', 'maxWidth' => '760px', 'description' => 'Умови продажу товарів в інтернет-магазині Bazarik.'])
    @slot('mobile')
        @include('pages.info.legal-text', ['placeholder' => '[Текст публічної оферти — надасть юрист]'])
    @endslot

    <h1 class="h1" style="font-size: 40px; line-height: 46px">Публічна оферта</h1>
    @include('pages.info.legal-text', ['placeholder' => '[Текст публічної оферти — надасть юрист]'])
@endcomponent
