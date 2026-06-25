{{-- Formulário compartilhado para criar ou editar uma disciplina requerida automática. --}}
<div class="modal fade" id="modalFormularioDisciplinaRequerida" tabindex="-1"
  aria-labelledby="modalFormularioDisciplinaRequeridaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFormularioDisciplinaRequeridaLabel">Disciplina requerida</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        @include('aproveitamentos_automaticos.partials.forms.form-disciplina-requerida', [
            'action' => '#',
            'method' => 'POST',
            'formId' => 'formDisciplinaRequeridaCompartilhado',
            'id' => 'modalFormularioDisciplinaRequerida-coddis',
            'useOldInput' => false,
            'dynamic' => true,
        ])
      </div>
    </div>
  </div>
</div>
