{{-- Renderiza os campos exigidos quando a disciplina cursada é externa à USP. --}}
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
