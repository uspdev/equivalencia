@php
  $number = $block['number'];
  $suffix = $block['suffix'];
  $visible = $block['visible'];
  $isUsp = $block['isUsp'];
  $coddisField = 'coddis' . $suffix;
  $verdisField = 'verdis' . $suffix;
  $nomeField = 'nome_disciplina' . $suffix;
  $iesField = 'ies' . $suffix;
  $creditoAulaField = 'credito_aula' . $suffix;
  $creditoTrabalhoField = 'credito_trabalho' . $suffix;
  $isUspField = 'is_usp' . $suffix;
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
        'verdis' => $block['verdis'],
        'nome' => $block['nome'],
    ])
  </div>

  <div class="js-equivalencia-outra-fields {{ $isUsp ? 'd-none' : '' }}">
    @include('aproveitamentos_automaticos.partials.form-equivalencia.outra-fields', [
        'coddis' => $block['coddis'],
        'nome' => $block['nome'],
        'ies' => $block['ies'],
        'creditoAula' => $block['credito_aula'],
        'creditoTrabalho' => $block['credito_trabalho'],
    ])
  </div>
</fieldset>
