<button type="button" class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#modalEditarDisciplina">
    Editar
</button>

<div class="modal fade" id="modalEditarDisciplina" tabindex="-1" aria-labelledby="modalEditarDisciplinaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarDisciplinaLabel">Editar disciplina requerida</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {!! $formHtmlEdit !!}
            </div>
        </div>
    </div>
</div>

