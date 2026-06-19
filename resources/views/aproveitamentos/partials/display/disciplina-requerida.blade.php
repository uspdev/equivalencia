{{-- Exibe a disciplina USP desejada no requerimento. --}}
<div class="card mb-4">
  <div class="card-header">
    <strong>Disciplina desejada</strong>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-12 mb-3">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Código',
            'value' => $show_data['requerida']['coddis'] . (!empty($show_data['requerida']['verdis']) ? ' v' . $show_data['requerida']['verdis'] : ''),
        ])
      </div>
      <div class="col-12 mb-3">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Nome',
            'value' => $show_data['requerida']['nomdis'],
        ])
      </div>
      <div class="col-12">
        @include('aproveitamentos.partials.display.show-info-item', [
            'label' => 'Unidade',
            'value' => $show_data['requerida']['sglund'] ?: 'Não informada',
        ])
      </div>
    </div>
  </div>
</div>
