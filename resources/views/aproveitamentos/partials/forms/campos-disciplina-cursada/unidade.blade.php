{{-- Renderiza os campos de unidade e código da disciplina cursada. --}}
<div class="form-group">
  <label for="{{ $fieldId('unidade_tipo') }}">Unidade onde a disciplina foi cursada <span
      class="text-danger">*</span></label>
  <select class="form-control js-unit-type" id="{{ $fieldId('unidade_tipo') }}" name="unidade_tipo" required>
    <option value="USP" @selected($unitType === 'USP')>USP</option>
    <option value="OUTRA" @selected($unitType === 'OUTRA')>Outra</option>
  </select>
</div>

<div class="form-group js-external-unit-group">
  <label for="{{ $fieldId('unidade_nome') }}">Nome da unidade ou instituição <span
      class="text-danger">*</span></label>
  <input type="text" class="form-control js-external-field" id="{{ $fieldId('unidade_nome') }}"
    name="unidade_nome" maxlength="255" value="{{ $value('unidade_nome', $discipline['unidade_nome'] ?? '') }}">
</div>

<div class="js-usp-code-group">
  @include('aproveitamentos.partials.forms.campo-disciplina-usp', [
      'name' => 'coddis_usp',
      'id' => $fieldId('coddis_usp'),
      'label' => 'Código da disciplina',
      'selected' => $unitType === 'USP' ? $selectedCode : null,
      'selectedName' => $unitType === 'USP' ? $selectedName : null,
      'required' => false,
  ])
</div>

<div class="form-group js-external-code-group">
  <label for="{{ $fieldId('coddis_externo') }}">Código da disciplina <span class="text-danger">*</span></label>
  <input type="text" class="form-control js-external-code" id="{{ $fieldId('coddis_externo') }}" maxlength="15"
    value="{{ $unitType === 'OUTRA' ? $selectedCode : '' }}">
</div>

<input type="hidden" class="js-discipline-code" name="coddis" value="{{ $selectedCode }}">

<div class="form-group js-external-name-group">
  <label for="{{ $fieldId('nomdis') }}">Nome da disciplina <span class="text-danger">*</span></label>
  <input type="text" class="form-control js-external-field" id="{{ $fieldId('nomdis') }}" name="nomdis"
    maxlength="240" value="{{ $value('nomdis', $discipline['nomdis'] ?? '') }}">
</div>
