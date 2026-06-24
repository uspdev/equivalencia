@php
  $modalId = $modalId ?? 'modalDadosDisciplina' . $disciplina->id;
  $modalLabelId = $modalLabelId ?? $modalId . 'Label';
  $titulo = $titulo ?? 'Dados da disciplina';
  $equivalencia = $equivalencia ?? null;
  $exibirDadosDaEquivalencia = $equivalencia !== null;
  $vigenciaVersao = $vigenciaVersao ?? null;
  $situacao = $disciplina->disciplina_ativa === null ? null : ($disciplina->disciplina_ativa ? 'Ativa' : 'Inativa');
@endphp

@push('modals')
  <div class="modal fade disciplina-dados-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalLabelId }}"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="{{ $modalLabelId }}">{{ $titulo }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <h6 class="font-weight-bold mb-3">
            {{ $disciplina->coddis }} -
            {{ $disciplina->nome_disciplina ?: 'Nome não informado' }}
          </h6>

          <div class="row">
            <div class="col-md-3 mb-3">
              @include('aproveitamentos.partials.display.show-info-item', [
                  'label' => 'Código',
                  'value' => $disciplina->coddis,
              ])
            </div>
            <div class="col-md-4 mb-3">
              @include('aproveitamentos.partials.display.show-info-item', [
                  'label' => 'Instituição',
                  'value' => $disciplina->ies,
              ])
            </div>
            <div class="col-md-4 mb-3">
              @include('aproveitamentos.partials.display.show-info-item', [
                  'label' => 'Unidade',
                  'value' => $disciplina->sglund,
              ])
            </div>
            <div class="col-md-3 mb-3">
              @include('aproveitamentos.partials.display.show-info-item', [
                  'label' => 'Créditos',
                  'value' => $disciplina->creditos,
              ])
            </div>
            <div class="col-md-4 mb-3">
              @include('aproveitamentos.partials.display.show-info-item', [
                  'label' => 'Carga horária',
                  'value' => $disciplina->carga_horaria ? $disciplina->carga_horaria . ' horas' : null,
              ])
            </div>
            <div class="col-md-4 mb-3">
              @include('aproveitamentos.partials.display.show-info-item', [
                  'label' => 'Versão',
                  'value' => filled($disciplina->verdis)
                      ? $disciplina->verdis . ($vigenciaVersao ? ' — ' . $vigenciaVersao : '')
                      : null,
              ])
            </div>
          </div>

          @if ($exibirDadosDaEquivalencia)
            <hr>
            <h6 class="font-weight-bold mb-3">Dados da equivalência</h6>
            <div class="row">
              <div class="col-md-4 mb-3">
                @include('aproveitamentos.partials.display.show-info-item', [
                    'label' => 'Número da reunião',
                    'value' => $equivalencia->numero_reuniao,
                ])
              </div>
              <div class="col-md-4 mb-3">
                @include('aproveitamentos.partials.display.show-info-item', [
                    'label' => 'Data da reunião',
                    'value' => $equivalencia->data_reuniao?->format('d/m/Y'),
                ])
              </div>
            </div>
            <div class="mb-3">
              @include('aproveitamentos.partials.display.show-info-item', [
                  'label' => 'Observações',
                  'value' => $equivalencia->observacoes,
              ])
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endpush
