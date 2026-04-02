@forelse ($disciplina->equivalentes as $e)
  <div class="disciplina-equivalente">
    {{ $e->coddis ?: '-' }} - {{ $e->nome_disciplina ?: '-' }} ({{ $e->ies }})
    @include('equivalencias.partials.remover-equivalente-btn')
    @include('equivalencias.partials.modal-edit-equivalencia', ['equivalencia' => $e])
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
