{{-- Formulário compartilhado para criar ou editar uma equivalência automática. --}}
<div class="modal fade" id="modalFormularioEquivalencia" tabindex="-1"
  aria-labelledby="modalFormularioEquivalenciaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFormularioEquivalenciaLabel">Equivalência</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        @if ($errors->any() && old('_modal_type') === 'equivalence')
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @include('aproveitamentos_automaticos.partials.form-equivalencia', [
            'action' => '#',
            'method' => 'POST',
            'formId' => 'formEquivalenciaCompartilhado',
            'values' => [],
            'useOldInput' => false,
            'dynamic' => true,
        ])
      </div>
    </div>
  </div>
</div>
