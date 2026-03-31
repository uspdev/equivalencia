<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovaDisciplina">
    Adicionar
</button>

<div class="modal fade" id="modalNovaDisciplina" tabindex="-1" aria-labelledby="modalNovaDisciplinaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovaDisciplinaLabel">Nova disciplina requerida</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                {!! $formHtmlCreate !!}
            </div>
        </div>
    </div>
</div>

