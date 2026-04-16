<form
    action="{{ route('equivalencias.destroy-equivalencia-grupo', [$codcur, $codhab, $disciplina, $equivalenciaRepresentante]) }}"
    method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger ml-2 btn-remover py-0"
        title="Remover grupo de equivalências"
        onclick="return confirm('Tem certeza que deseja remover todas as equivalências deste grupo?')">
        <i class="fas fa-trash"></i>
    </button>
</form>
