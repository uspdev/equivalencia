@php
  $equivalenciasPorGrupo = $disciplina->equivalentes->groupBy('grupo');
@endphp
@forelse ($equivalenciasPorGrupo as $grupo => $equivalenciasDoGrupo)
  @php
    $equivalenciaRepresentante = $equivalenciasDoGrupo->first();
  @endphp

  <div class="disciplina-equivalente d-flex align-items-center flex-nowrap mb-2">
    <p class="mb-0 text-truncate">
      @foreach ($equivalenciasDoGrupo as $e)
        @php
          $modalDadosDisciplinaId = 'modalDadosDisciplinaCursada' . $e->id;
        @endphp

        <span title="{{ $e->cursada->nome_disciplina }}">
          <button type="button" class="btn btn-link p-0 text-left align-baseline disciplina-dados-trigger"
            data-toggle="modal" data-target="#{{ $modalDadosDisciplinaId }}">
            {{ $e->cursada->coddis }} -
            @limitarTexto($e->cursada->nome_disciplina)
          </button>
        </span>

        @include('aproveitamentos_automaticos.partials.modals.modal-disciplina-dados', [
            'disciplina' => $e->cursada,
            'equivalencia' => $e,
            'modalId' => $modalDadosDisciplinaId,
            'titulo' => 'Dados da disciplina cursada',
        ])

        @notLast('|')
      @endforeach
    </p>
    @can(\App\Enums\Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value)
      @if ($equivalenciaRepresentante)
        <div class="js-edit-only d-inline-flex align-items-center">
          @include('aproveitamentos_automaticos.partials.modals.modal-edit-equivalencia', [
              'equivalencia' => $equivalenciaRepresentante,
          ])
          @include('aproveitamentos_automaticos.partials.buttons.form-remove-equivalencia')
        </div>
      @endif
    @endcan
  </div>
@empty
  -
@endforelse
