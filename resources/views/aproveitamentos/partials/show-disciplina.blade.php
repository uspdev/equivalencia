<div class="card mb-4">
  <div class="card-header">
    <strong>Disciplina {{ $position }}: {{ $cursada['coddis'] }}- {{ $cursada['nomdis'] }}</strong>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="text-muted small">Instituição</div>
        <strong>{{ $cursada['ies'] ?: 'Não informada' }}</strong>
      </div>
      <div class="col-md-2 mb-3">
        <div class="text-muted small">Período</div>
        <strong>{{ $cursada['codtur'] ?? 'Não informada' }}</strong>
      </div>
      <div class="col-md-2 mb-3">
        <div class="text-muted small">Ano</div>
        <strong>{{ $cursada['ano'] ?: 'Não informado' }}</strong>
      </div>
      <div class="col-md-2 mb-3">
        <div class="text-muted small">Semestre</div>
        <strong>{{ $cursada['semestre'] ? $cursada['semestre'] . 'º' : 'Não informado' }}</strong>
      </div>
      <div class="col-md-2 mb-3">
        <div class="text-muted small">Frequência</div>
        <strong>{{ $cursada['freq'] !== null ? $cursada['freq'] . '%' : 'Não informada' }}</strong>
      </div>
      <div class="col-md-2 mb-3">
        <div class="text-muted small">Nota</div>
        <strong>{{ $cursada['nota'] ?? 'Não informada' }}</strong>
      </div>
      <div class="col-md-2 mb-3 mb-md-0">
        <div class="text-muted small">Crédito aula</div>
        <strong>{{ $cursada['credito_aula'] ?? 'Não informado' }}</strong>
      </div>
      <div class="col-md-2 mb-3 mb-md-0">
        <div class="text-muted small">Crédito trabalho</div>
        <strong>{{ $cursada['credito_trabalho'] ?? 'Não informado' }}</strong>
      </div>
      <div class="col-md-3 mb-3 mb-md-0">
        <div class="text-muted small">Carga horária</div>
        <strong>{{ $cursada['carga_hr'] !== null ? $cursada['carga_hr'] . ' horas' : 'Não informada' }}</strong>
      </div>
      <div class="col-md-6">
        <div class="text-muted small">Ementa</div>
        @if ($cursada['ementa_file'])
          @include('aproveitamentos.partials.show-arquivo', [
              'arquivo' => $cursada['ementa_file'],
              'group' => $group,
          ])
        @else
          <span class="text-muted">Nenhuma ementa enviada.</span>
        @endif
      </div>
    </div>

    @if ($cursada['sglund'] || $cursada['disciplina_ativa'] !== null)
      <div class="row mt-3">
        <div class="col-md-3 mb-3 mb-md-0">
          <div class="text-muted small">Unidade USP</div>
          <strong>{{ $cursada['sglund'] ?: 'Não informada' }}</strong>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Situação da disciplina</div>
          <strong>{{ $cursada['disciplina_ativa'] === null ? 'Não informada' : ($cursada['disciplina_ativa'] ? 'Ativa' : 'Inativa') }}</strong>
        </div>
      </div>
    @endif
  </div>
</div>
