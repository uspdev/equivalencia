{{-- Renderiza o formulário final de envio do requerimento de aproveitamento. --}}
<form method="POST" action="{{ route('equivalencias.newreq-store') }}" enctype="multipart/form-data">
  @csrf

  @include('aproveitamentos.partials.forms.historicos')
  @include('aproveitamentos.partials.forms.revisao-envio')
</form>
