<div class="disciplina-requerida d-flex align-items-center">
    <div>
        <p>
            {{ $disciplina->coddis }}
            - {{ $disciplina->nome_disciplina ?: '-' }}
        </p>
    </div>

    <div class="disciplina-requerida-acoes js-edit-only ml-3 d-inline-flex align-items-center">
        <div> @include('equivalencias.partials.modal-equivalencia', [
            'modalId' => "modalAdicionarEquivalencia{$disciplina->id}",
            'modalLabelId' => "modalAdicionarEquivalenciaLabel{$disciplina->id}",
            'formHtmlEquivalencia' => $formHtmlEquivalencia[$disciplina->id] ?? '',
        ])</div>
        <div>@include('equivalencias.partials.modal-edit')</div>

        <form action="{{ route('equivalencias.destroy', [$codcur, $codhab, $disciplina]) }}" method="POST"
            class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover disciplina requerida"
                onclick="return confirm('Tem certeza que deseja remover esta disciplina e suas equivalências?')">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    </div>
</div>
