{{-- Exibe a lista de disciplinas cursadas adicionadas ao rascunho. --}}
<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <strong>Disciplinas cursadas</strong>
    @if ($disciplines->count() < 3)
      <button type="button" id="add-discipline-button"
        class="btn btn-sm btn-primary {{ $selectedRequiredCode ? '' : 'disabled' }}" data-toggle="modal"
        data-target="#create-discipline-modal" @disabled(!$selectedRequiredCode)
        aria-disabled="{{ $selectedRequiredCode ? 'false' : 'true' }}">
        Adicionar disciplina
      </button>
    @endif
  </div>
  <div class="card-body">
    @if ($disciplines->isEmpty())
      <p class="text-muted mb-0">Nenhuma disciplina adicionada. É necessário adicionar ao menos uma.</p>
    @else
      <div class="list-group">
        @foreach ($disciplines as $discipline)
          @include('aproveitamentos.partials.display.disciplina-cursada')
        @endforeach
      </div>
      <small class="form-text text-muted mt-2">
        Clique em uma disciplina para revisar ou editar suas informações.
      </small>
    @endif
  </div>
</div>
