<fieldset class="border rounded p-3 mb-3">
  <legend class="h5">Dados da reunião</legend>

  <div class="form-row">
    <div class="form-group col-md-6">
      <label for="{{ $formId }}-numero-reuniao">Número da reunião</label>
      <input type="number" class="form-control" id="{{ $formId }}-numero-reuniao" name="numero_reuniao"
        value="{{ $formState['administrative']['numero_reuniao'] }}" step="1">
    </div>
    <div class="form-group col-md-6">
      <label for="{{ $formId }}-data-reuniao">Data da reunião</label>
      <input type="date" class="form-control" id="{{ $formId }}-data-reuniao" name="data_reuniao"
        value="{{ $formState['administrative']['data_reuniao'] }}">
    </div>
  </div>

  <div class="form-group mb-0">
    <label for="{{ $formId }}-observacoes">Observações</label>
    <textarea class="form-control" id="{{ $formId }}-observacoes" name="observacoes"
      rows="3">{{ $formState['administrative']['observacoes'] }}</textarea>
  </div>
</fieldset>
