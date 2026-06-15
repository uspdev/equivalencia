{{-- Registra os modais de edição das disciplinas cursadas do rascunho. --}}
@foreach ($disciplines as $discipline)
  @php
    $editModalId = 'edit-discipline-modal-' . $discipline['id'];
    $editFieldPrefix = 'edit_' . str_replace('-', '_', $discipline['id']);
    $isOpenEditModal = $openDisciplineModal === $discipline['id'];
  @endphp

  @push('modals')
    <div class="modal fade" id="{{ $editModalId }}" tabindex="-1" aria-labelledby="{{ $editModalId }}-label"
      aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="POST" action="{{ route('equivalencias.newreq-discipline-update', $discipline['id']) }}"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" class="js-required-discipline-code" name="requerida_coddis"
              value="{{ $selectedRequiredCode }}">
            <div class="modal-header">
              <h5 class="modal-title" id="{{ $editModalId }}-label">Editar disciplina cursada</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              @include('aproveitamentos.partials.forms.errors', [
                  'title' => 'Revise os dados da disciplina.',
                  'show' => $isOpenEditModal && $errors->any(),
              ])

              @include('aproveitamentos.partials.forms.campos-disciplina-cursada', [
                  'fieldPrefix' => $editFieldPrefix,
                  'useOldInput' => $isOpenEditModal,
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
@endforeach
