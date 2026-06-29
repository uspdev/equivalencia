<div class="form-row">
  <div class="form-group col-md-3">
    <label for="{{ $baseId }}-coddis">Código <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="{{ $baseId }}-coddis" name="{{ $coddisField }}"
      value="{{ $isUsp ? '' : $coddis }}" maxlength="15" @disabled(!($visible && !$isUsp)) @required($visible && !$isUsp)>
  </div>
  <div class="form-group col-md-9">
    <label for="{{ $baseId }}-nome">Nome da Disciplina <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="{{ $baseId }}-nome" name="{{ $nomeField }}"
      value="{{ $isUsp ? '' : $nome }}" maxlength="240" @disabled(!($visible && !$isUsp)) @required($visible && !$isUsp)>
  </div>
</div>

<div class="form-group">
  <label for="{{ $baseId }}-ies">IES <span class="text-danger">*</span></label>
  <input type="text" class="form-control" id="{{ $baseId }}-ies" name="{{ $iesField }}"
    value="{{ $isUsp ? '' : $ies }}" maxlength="255" @disabled(!($visible && !$isUsp)) @required($visible && !$isUsp)>
</div>

<div class="form-row">
  <div class="form-group col-md-6">
    <label for="{{ $baseId }}-credito-aula">Crédito aula</label>
    <input type="number" class="form-control" id="{{ $baseId }}-credito-aula" name="{{ $creditoAulaField }}"
      value="{{ $isUsp ? '' : $creditoAula }}" min="0" step="1" data-optional="true"
      @disabled(!($visible && !$isUsp))>
  </div>
  <div class="form-group col-md-6">
    <label for="{{ $baseId }}-credito-trabalho">Crédito trabalho</label>
    <input type="number" class="form-control" id="{{ $baseId }}-credito-trabalho"
      name="{{ $creditoTrabalhoField }}" value="{{ $isUsp ? '' : $creditoTrabalho }}" min="0" step="1"
      data-optional="true" @disabled(!($visible && !$isUsp))>
  </div>
</div>
