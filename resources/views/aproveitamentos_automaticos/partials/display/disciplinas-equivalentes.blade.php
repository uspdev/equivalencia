@forelse ($disciplina->equivalentes as $equivalenciaRepresentante)
  <div class="disciplina-equivalente d-flex align-items-center flex-nowrap mb-2">
    <p class="mb-0 text-truncate">
      @foreach ($equivalenciaRepresentante->cursadas as $e)
        @php
          $modalDadosDisciplinaId = 'modalDadosDisciplinaCursada' . $e->id;
        @endphp

        <span title="{{ $e->nome_disciplina }}">
          <button type="button" class="btn p-0 text-left align-baseline disciplina-dados-trigger"
            data-toggle="modal" data-target="#{{ $modalDadosDisciplinaId }}">
            {{ $e->coddis }} -
            @limitarTexto($e->nome_disciplina)
            @if ($e->sglund)
              ({{ $e->sglund }})
            @endif
          </button>
        </span>

        @include('aproveitamentos_automaticos.partials.modals.modal-disciplina-dados', [
            'disciplina' => $e,
            'equivalencia' => $equivalenciaRepresentante,
            'modalId' => $modalDadosDisciplinaId,
            'titulo' => 'Dados da disciplina cursada',
            'vigenciaVersao' => $vigenciasVersoesCursadas[$e->id] ?? null,
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

@pushOnce('styles')
  <style>
    .disciplina-dados-trigger:hover,
    .disciplina-dados-trigger:focus {
      color: #495057 !important;
      text-decoration: underline;
    }
  </style>
@endpushOnce
