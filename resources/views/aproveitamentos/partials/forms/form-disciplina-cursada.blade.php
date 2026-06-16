{{-- Renderiza o formulário standalone para adicionar ou editar disciplina cursada. --}}
<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
  @csrf
  @if ($formMethod !== 'POST')
    @method($formMethod)
  @endif
  <input type="hidden" name="requerida_coddis" value="{{ $requiredDisciplineCode }}">

  @include('aproveitamentos.partials.forms.campos-disciplina-cursada')

  <div class="d-flex justify-content-between">
    <a href="{{ route('equivalencias.newreq-create') }}" class="btn btn-outline-secondary">
      Voltar ao resumo
    </a>
    <button type="submit" class="btn btn-primary">Salvar disciplina</button>
  </div>
</form>
