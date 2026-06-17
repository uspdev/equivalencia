{{-- Exibe os metadados principais do requerimento enviado. --}}
<div class="card mb-4 bg-light">
  <div class="card-body">
    <div class="row">
      <div class="col-md-6 mb-3 mb-md-0">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Data de criação',
            'value' => $show_data['created_at']->format('d/m/Y H:i'),
        ])
      </div>
      <div class="col-md-6">
        <span class="d-block text-muted small mb-1">Status</span>
        <span class="badge badge-warning">{{ $show_data['estado'] ?: 'Enviado' }}</span>
      </div>
    </div>
  </div>
</div>
