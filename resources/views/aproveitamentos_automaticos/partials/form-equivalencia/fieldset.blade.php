@php
  $number = $block['number'];
  $suffix = $block['suffix'];
  $visible = $block['visible'];
  $isUsp = $block['isUsp'];
  $coddisField = 'coddis' . $suffix;
  $nomeField = 'nome_disciplina' . $suffix;
  $iesField = 'ies' . $suffix;
  $isUspField = 'is_usp' . $suffix;
  $numeroReuniaoField = 'numero_reuniao' . $suffix;
  $dataReuniaoField = 'data_reuniao' . $suffix;
  $observacoesField = 'observacoes' . $suffix;
  $baseId = $formId . '-disciplina-' . $number;
@endphp

<fieldset class="equivalencia-disciplina border rounded p-3 mb-3 {{ $visible ? '' : 'd-none' }}" data-equivalencia-block
  data-index="{{ $number }}">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <legend class="h5 mb-0">Disciplina equivalente {{ $number }}</legend>
    @if ($number > 1)
      <button type="button" class="btn btn-sm btn-outline-danger js-remove-equivalencia" title="Remover disciplina">
        <i class="fas fa-trash"></i>
      </button>
    @endif
  </div>

  <input type="hidden" name="{{ $isUspField }}" value="0">
  <div class="custom-control custom-switch mb-3">
    <input type="checkbox" class="custom-control-input js-equivalencia-is-usp" id="{{ $baseId }}-is-usp"
      name="{{ $isUspField }}" value="1" @checked($isUsp)>
    <label class="custom-control-label" for="{{ $baseId }}-is-usp">Disciplina da USP?</label>
  </div>

  <div class="js-equivalencia-usp-fields {{ $isUsp ? '' : 'd-none' }}">
    @include('aproveitamentos_automaticos.partials.form-equivalencia.usp-fields', [
        'coddis' => $block['coddis'],
        'nome' => $block['nome'],
    ])
  </div>

  <div class="js-equivalencia-outra-fields {{ $isUsp ? 'd-none' : '' }}">
    @include('aproveitamentos_automaticos.partials.form-equivalencia.outra-fields', [
        'coddis' => $block['coddis'],
        'nome' => $block['nome'],
        'ies' => $block['ies'],
    ])
  </div>

  <div class="js-equivalencia-admin-fields">
    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="{{ $baseId }}-numero-reuniao">Número da reunião</label>
        <input type="number" class="form-control" id="{{ $baseId }}-numero-reuniao"
          name="{{ $numeroReuniaoField }}" value="{{ $block['numero_reuniao'] }}" step="1"
          @disabled(!$visible)>
      </div>
      <div class="form-group col-md-6">
        <label for="{{ $baseId }}-data-reuniao">Data da reunião</label>
        <input type="date" class="form-control" id="{{ $baseId }}-data-reuniao"
          name="{{ $dataReuniaoField }}" value="{{ $block['data_reuniao'] }}" @disabled(!$visible)>
      </div>
    </div>

    <div class="form-group">
      <label for="{{ $baseId }}-observacoes">Observações</label>
      <textarea class="form-control" id="{{ $baseId }}-observacoes" name="{{ $observacoesField }}" rows="3"
        @disabled(!$visible)>{{ $block['observacoes'] }}</textarea>
    </div>
  </div>
</fieldset>
