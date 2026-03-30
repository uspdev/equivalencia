<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAdicionarEquivalencia">
    Adicionar
</button>

<div class="modal fade" id="modalAdicionarEquivalencia" tabindex="-1" aria-labelledby="modalAdicionarEquivalenciaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAdicionarEquivalenciaLabel">Adicionar disciplina cursada equivalente</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        {!! $formHtmlEquivalencia !!}
      </div>
    </div>
  </div>
</div>