@forelse ($disciplina->equivalentes as $e)
  <div class="disciplina-equivalente d-flex align-items-center">
    {{ $e->coddis ?: '-' }} - {{ $e->nome_disciplina ?: '-' }} ({{ $e->ies }})
    <div class="mr-2">@include('equivalencias.partials.remover-equivalente-btn')</div>
    <div>@include('equivalencias.partials.modal-edit-equivalencia', ['equivalencia' => $e])</div>
  </div>
@empty
  -
@endforelse
@section('styles')
  @parent
  <style>
    .disciplina-equivalente .btn-remover,
    .disciplina-equivalente .btn-editar {
      opacity: 0;
      transition: opacity 0.2s;
    }

    .disciplina-equivalente:hover .btn-remover,
    .disciplina-equivalente:hover .btn-editar {
      opacity: 1;
    }
  </style>
@endsection
