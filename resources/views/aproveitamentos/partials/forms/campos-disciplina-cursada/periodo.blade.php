{{-- Renderiza os campos de ano e semestre em que a disciplina foi cursada. --}}
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
