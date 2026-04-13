<div class="disciplina-requerida d-flex align-items-center">
  <div>
    <p>
      {{ $disciplina->coddis }}
    - {{ $disciplina->nome_disciplina ?: '-' }} ({{ $disciplina->verdis ?: '-' }})
    </p>
  </div>

  <div class="disciplina-requerida-acoes ml-3 d-inline-flex align-items-center">
    <div>@include("equivalencias.partials.modal-edit")</div>

    <form action="{{ route('equivalencias.destroy', [$codcur, $codhab, $disciplina]) }}" method="POST" class="d-inline">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover disciplina requerida"
        onclick="return confirm('Tem certeza que deseja remover esta disciplina e suas equivalências?')">
        <i class="fas fa-trash"></i>
      </button>
    </form>
  </div>
</div>

@section('styles')
  @parent
  <style>
    .disciplina-requerida .disciplina-requerida-acoes {
      opacity: 0;
      transition: opacity 0.2s;
    }

    .disciplina-requerida:hover .disciplina-requerida-acoes {
      opacity: 1;
    }

    body.modal-open .disciplina-requerida .disciplina-requerida-acoes {
      opacity: 1;
    }
  </style>
@endsection
