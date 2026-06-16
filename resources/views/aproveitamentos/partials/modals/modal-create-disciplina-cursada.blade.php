{{-- Registra o modal para adicionar uma disciplina cursada ao rascunho. --}}
@if ($disciplines->count() < 3)
  @push('modals')
    <div class="modal fade" id="create-discipline-modal" tabindex="-1" aria-labelledby="create-discipline-modal-label"
      aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="POST" action="{{ route('equivalencias.newreq-discipline-store') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" class="js-required-discipline-code" name="requerida_coddis"
              value="{{ $selectedRequiredCode }}">
            <div class="modal-header">
              <h5 class="modal-title" id="create-discipline-modal-label">Adicionar disciplina cursada</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              @include('aproveitamentos.partials.forms.errors', [
                  'title' => 'Revise os dados da disciplina.',
                  'show' => $openDisciplineModal === 'create' && $errors->any(),
              ])

              @include('aproveitamentos.partials.forms.campos-disciplina-cursada', [
                  'discipline' => null,
                  'fieldPrefix' => 'create',
                  'useOldInput' => $openDisciplineModal === 'create',
              ])
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Salvar disciplina</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endpush
@endif
