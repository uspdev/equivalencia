{{-- Renderiza os campos de upload dos históricos escolares exigidos. --}}
@if ($transcriptGroups->isNotEmpty())
  <div class="card mb-4">
    <div class="card-header">
      <strong>Histórico Escolar</strong>
    </div>
    <div class="card-body">
      <p>
        Envie um PDF para cada unidade ou instituição externa. Disciplinas da mesma unidade utilizam o mesmo
        histórico. Os arquivos serão salvos somente ao enviar o requerimento.
      </p>
      @foreach ($transcriptGroups as $group)
        <div class="form-group">
          <label for="historico_{{ $group['key'] }}">
            {{ $group['unit_name'] }}
            <span class="text-danger">*</span>
          </label>
          <div class="small text-muted mb-1">
            Disciplinas:
            {{ $group['disciplines']->map(fn($discipline) => $discipline['coddis'] . ' - ' . $discipline['nomdis'])->join('; ') }}
          </div>
          <input type="file" class="form-control-file" id="historico_{{ $group['key'] }}"
            name="historicos[{{ $group['key'] }}]" accept=".pdf,application/pdf" required>
        </div>
      @endforeach
      <div class="form-group">
        <label for="historico_adicional">
          Histórico escolar adicional
          <span class="text-muted">(opcional)</span>
        </label>
        <input type="file" class="form-control-file" id="historico_adicional" name="historico_adicional"
          accept=".pdf,application/pdf">
      </div>
    </div>
  </div>
@endif
