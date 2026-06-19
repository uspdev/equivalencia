<div class="disciplina-requerida d-flex align-items-center">
  <div>
    @php
      $modalDadosDisciplinaId = 'modalDadosDisciplinaRequerida' . $disciplina->id;
    @endphp

    <p class="mb-0">
      <button type="button" class="btn btn-link p-0 text-left align-baseline disciplina-dados-trigger"
        data-toggle="modal" data-target="#{{ $modalDadosDisciplinaId }}">
        {{ $disciplina->coddis }}
        - {{ $disciplina->nome_disciplina ?: '-' }}
      </button>
    </p>

    @include('aproveitamentos_automaticos.partials.modals.modal-disciplina-dados', [
        'disciplina' => $disciplina,
        'modalId' => $modalDadosDisciplinaId,
        'titulo' => 'Dados da disciplina requerida',
    ])
  </div>

  @can('svgrad')
    <div class="disciplina-requerida-acoes js-edit-only ml-3 d-inline-flex align-items-center">
      <div> @include('aproveitamentos_automaticos.partials.modals.modal-equivalencia', [
          'modalId' => "modalAdicionarEquivalencia{$disciplina->id}",
          'modalLabelId' => "modalAdicionarEquivalenciaLabel{$disciplina->id}",
      ])</div>
      <div>@include('aproveitamentos_automaticos.partials.modals.modal-edit')</div>

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
