{{-- Exibe os dados de uma disciplina cursada informada no requerimento. --}}
<div class="card border-left border-primary mb-3">
  <div class="card-header d-flex flex-column flex-md-row justify-content-between">
    <strong>Disciplina {{ $position }}: {{ $cursada['coddis'] }} - {{ $cursada['nomdis'] }}</strong>
    <span class="text-muted small mt-1 mt-md-0">{{ $cursada['ies'] ?: 'Instituição não informada' }}</span>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-4 mb-3">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Instituição',
            'value' => $cursada['ies'],
        ])
      </div>
      <div class="col-md-2 col-6 mb-3">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Período',
            'value' => $cursada['codtur'] ?? null,
        ])
      </div>
      <div class="col-md-2 col-6 mb-3">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Ano',
            'value' => $cursada['ano'],
        ])
      </div>
      <div class="col-md-2 col-6 mb-3">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Semestre',
            'value' => $cursada['semestre'] ? $cursada['semestre'] . 'º' : null,
        ])
      </div>
      <div class="col-md-2 col-6 mb-3">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Frequência',
            'value' => $cursada['freq'] !== null ? $cursada['freq'] . '%' : null,
        ])
      </div>
      <div class="col-md-2 col-6 mb-3">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Nota',
            'value' => $cursada['nota'],
        ])
      </div>
      <div class="col-md-3 mb-3 mb-md-0">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Créditos',
            'value' => $cursada['creditos'],
        ])
      </div>
      <div class="col-md-3 mb-3 mb-md-0">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Carga horária',
            'value' => $cursada['carga_hr'] !== null ? $cursada['carga_hr'] . ' horas' : null,
        ])
      </div>
      <div class="col-md-6">
        <span class="d-block text-muted small mb-1">Ementa</span>
        @if ($cursada['ementa_file'])
          @include('aproveitamentos.partials.show-arquivo', [
              'arquivo' => $cursada['ementa_file'],
              'aproveitamentoId' => $aproveitamentoId,
          ])
        @else
          <span class="text-muted">Nenhuma ementa enviada.</span>
        @endif
      </div>
    </div>

    @if ($cursada['sglund'] || $cursada['disciplina_ativa'] !== null)
      <div class="row mt-3">
        <div class="col-md-3 mb-3 mb-md-0">
          @include('aproveitamentos.partials.display.show-info-item', [
              'label' => 'Unidade USP',
              'value' => $cursada['sglund'] ?: 'Não informada',
          ])
        </div>
        <div class="col-md-3">
          @include('aproveitamentos.partials.display.show-info-item', [
              'label' => 'Situação da disciplina',
              'value' =>
                  $cursada['disciplina_ativa'] === null
                      ? 'Não informada'
                      : ($cursada['disciplina_ativa']
                          ? 'Ativa'
                          : 'Inativa'),
          ])
        </div>
      </div>
    @endif
  </div>
</div>
