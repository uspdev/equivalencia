{{-- Exibe o seletor da disciplina USP desejada para o aproveitamento. --}}
<div class="card mb-4">
  <div class="card-header">
    <strong>Disciplina desejada</strong>
  </div>
  <div class="card-body">
    @include('aproveitamentos.partials.forms.campo-disciplina-usp', [
        'name' => 'requerida_coddis_selecionada',
        'id' => 'requerida_coddis',
        'label' => ' ',
        'selected' => $selectedRequiredCode,
        'selectedName' => $requiredDisciplineName,
        'required' => true,
    ])
  </div>
</div>
