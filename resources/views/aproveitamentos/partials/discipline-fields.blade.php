@php
  $fieldPrefix = $fieldPrefix ?? 'discipline';
  $useOldInput = $useOldInput ?? true;
  $fieldId = fn(string $name) => $fieldPrefix . '_' . $name;
  $value = fn(string $name, mixed $default = null) => $useOldInput ? old($name, $default) : $default;
  $unitType = $value('unidade_tipo', $discipline['unidade_tipo'] ?? 'USP');
  $selectedCode = $value('coddis', $discipline['coddis'] ?? null);
  $selectedName = $value('nomdis', $discipline['nomdis'] ?? null);
@endphp

<div class="js-discipline-fields" data-has-syllabus="@json(isset($discipline['ementa']))">
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
    <input type="text" class="form-control js-external-field" id="{{ $fieldId('unidade_nome') }}" name="unidade_nome"
      maxlength="255" value="{{ $value('unidade_nome', $discipline['unidade_nome'] ?? '') }}">
  </div>

  <div class="js-usp-code-group">
    @include('aproveitamentos.partials.disciplina-usp-field', [
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

  <div class="form-row">
    <div class="form-group col-md-6">
      <label for="{{ $fieldId('ano') }}">Ano em que cursou <span class="text-danger">*</span></label>
      <input type="number" class="form-control" id="{{ $fieldId('ano') }}" name="ano" min="1900"
        max="{{ date('Y') }}" value="{{ $value('ano', $discipline['ano'] ?? '') }}" required>
    </div>
    <div class="form-group col-md-6">
      <label for="{{ $fieldId('semestre') }}">Semestre em que cursou <span class="text-danger">*</span></label>
      <select class="form-control" id="{{ $fieldId('semestre') }}" name="semestre" required>
        <option value="">Selecione...</option>
        <option value="1" @selected((string) $value('semestre', $discipline['semestre'] ?? '') === '1')>1º semestre</option>
        <option value="2" @selected((string) $value('semestre', $discipline['semestre'] ?? '') === '2')>2º semestre</option>
      </select>
    </div>
    <small class="form-text text-muted">
      Informe o ano e o semestre do calendário, por exemplo: "2025 / 1º Semestre", e não ano e semestre do curso.
    </small>
  </div>

  <div class="js-external-fields">
    <div class="form-group">
      <label for="{{ $fieldId('ementa') }}">Ementa da disciplina <span class="text-danger">*</span></label>
      @if (isset($discipline['ementa']))
        <div class="small text-success mb-1">Arquivo atual: {{ $discipline['ementa']['name'] }}</div>
      @endif
      <input type="file" class="form-control-file js-external-field js-syllabus-field" id="{{ $fieldId('ementa') }}"
        name="ementa" accept=".pdf,application/pdf">
    </div>

    <div class="form-row">
      <div class="form-group col-md-3">
        <label for="{{ $fieldId('frequencia') }}">Frequência (%) <span class="text-danger">*</span></label>
        <input type="number" class="form-control js-external-field" id="{{ $fieldId('frequencia') }}"
          name="frequencia" min="0" max="100" step="0.01"
          value="{{ $value('frequencia', $discipline['frequencia'] ?? '') }}">
      </div>
      <div class="form-group col-md-3">
        <label for="{{ $fieldId('nota') }}">Nota <span class="text-danger">*</span></label>
        <input type="number" class="form-control js-external-field" id="{{ $fieldId('nota') }}" name="nota"
          min="0" max="10" step="0.01" value="{{ $value('nota', $discipline['nota'] ?? '') }}">
      </div>
      <div class="form-group col-md-3">
        <label for="{{ $fieldId('creditos') }}">Créditos <span class="text-danger">*</span></label>
        <input type="number" class="form-control js-external-field" id="{{ $fieldId('creditos') }}" name="creditos"
          min="1" step="1" value="{{ $value('creditos', $discipline['creditos'] ?? '') }}">
      </div>
      <div class="form-group col-md-3">
        <label for="{{ $fieldId('carga_horaria') }}">Carga horária <span class="text-danger">*</span></label>
        <input type="number" class="form-control js-external-field" id="{{ $fieldId('carga_horaria') }}"
          name="carga_horaria" min="1" step="1"
          value="{{ $value('carga_horaria', $discipline['carga_horaria'] ?? '') }}">
      </div>
    </div>
  </div>
</div>

@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.js-discipline-fields').forEach(function(container) {
          var unit = container.querySelector('.js-unit-type');
          var uspCode = container.querySelector('.disciplina-usp-select');
          var externalCode = container.querySelector('.js-external-code');
          var code = container.querySelector('.js-discipline-code');
          var hasSyllabus = container.dataset.hasSyllabus === 'true';

          function syncCode() {
            code.value = unit.value === 'USP' ? uspCode.value : externalCode.value;
          }

          function toggleFields() {
            var isExternal = unit.value === 'OUTRA';

            container.querySelector('.js-external-unit-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-usp-code-group').style.display = isExternal ? 'none' : '';
            container.querySelector('.js-external-code-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-external-name-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-external-fields').style.display = isExternal ? '' : 'none';

            uspCode.required = !isExternal;
            uspCode.disabled = isExternal;
            externalCode.required = isExternal;
            externalCode.disabled = !isExternal;
            container.querySelectorAll('.js-external-field').forEach(function(field) {
              field.disabled = !isExternal;
              field.required = isExternal && (!field.classList.contains('js-syllabus-field') || !hasSyllabus);
            });
            syncCode();
          }

          unit.addEventListener('change', toggleFields);
          uspCode.addEventListener('change', syncCode);
          externalCode.addEventListener('input', syncCode);

          if (window.jQuery) {
            window.jQuery(uspCode)
              .off('.draftCodeSync')
              .on('change.draftCodeSync select2:select.draftCodeSync select2:clear.draftCodeSync', syncCode);
          }

          toggleFields();
        });
      });
    </script>
  @endpush
@endonce
