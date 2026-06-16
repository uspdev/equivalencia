{{-- Exibe uma disciplina cursada no rascunho com ações de edição e remoção. --}}
<div class="list-group-item d-flex align-items-center justify-content-between">
  <button type="button" class="btn btn-link flex-grow-1 p-0 text-left" data-toggle="modal"
    data-target="#edit-discipline-modal-{{ $discipline['id'] }}">
    <strong>{{ $discipline['unidade_nome'] }}</strong>
    <span class="ml-2">{{ $discipline['coddis'] }}</span>
  </button>

  <form method="POST" action="{{ route('equivalencias.newreq-discipline-destroy', $discipline['id']) }}"
    onsubmit="return confirm('Remover esta disciplina do rascunho?')">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
  </form>
</div>
