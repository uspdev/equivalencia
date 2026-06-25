<div class="disciplina-requerida d-flex align-items-center">
  <div>
    <p class="mb-0">
      <button type="button" class="btn p-0 text-left align-baseline disciplina-dados-trigger" data-toggle="modal"
        data-target="#modalDadosDisciplina" data-modal-key="required-{{ $disciplina->id }}">
        {{ $disciplina->coddis }}
        - {{ $disciplina->nome_disciplina ?: '-' }}
        ({{ $disciplina->sglund }})
      </button>
    </p>
  </div>

  @can(\App\Enums\Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value)
    <div class="disciplina-requerida-acoes js-edit-only ml-3 d-inline-flex align-items-center">
      <div>@include('aproveitamentos_automaticos.partials.buttons.btn-adicionar-equivalencia')</div>
      <div>@include('aproveitamentos_automaticos.partials.buttons.btn-editar-disciplina-requerida')</div>

      <form action="{{ route('equivalencias.destroy', [$codcur, $codhab, $disciplina]) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover disciplina requerida"
          onclick="return confirm('Tem certeza que deseja remover esta disciplina e suas equivalências?')">
          <i class="fas fa-trash"></i>
        </button>
      </form>
    </div>
  @endcan
</div>
@pushOnce('styles')
  <style>
    .disciplina-dados-trigger:hover,
    .disciplina-dados-trigger:focus {
      color: #495057 !important;
      text-decoration: underline;
    }
  </style>
@endpushOnce
