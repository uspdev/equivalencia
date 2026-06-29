<div class="modal fade disciplina-dados-modal" id="modalDadosDisciplina" tabindex="-1"
  aria-labelledby="modalDadosDisciplinaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDadosDisciplinaLabel">Dados da disciplina</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <h6 class="font-weight-bold mb-3" data-detail-heading></h6>

        {{-- Campos 'hardcoded' para ser preenchidos dinamicamente --}}
        <div class="row">
          <div class="col-md-3 mb-3">
            <strong class="d-block">Código</strong>
            <span data-detail-field="code">-</span>
          </div>
          <div class="col-md-4 mb-3">
            <strong class="d-block">Instituição</strong>
            <span data-detail-field="institution">-</span>
          </div>
          <div class="col-md-4 mb-3">
            <strong class="d-block">Unidade</strong>
            <span data-detail-field="unit">-</span>
          </div>
          <div class="col-md-3 mb-3">
            <strong class="d-block">Crédito aula</strong>
            <span data-detail-field="classCredits">-</span>
          </div>
          <div class="col-md-3 mb-3">
            <strong class="d-block">Crédito trabalho</strong>
            <span data-detail-field="workCredits">-</span>
          </div>
          <div class="col-md-4 mb-3">
            <strong class="d-block">Carga horária</strong>
            <span data-detail-field="workload">-</span>
          </div>
          <div class="col-md-4 mb-3">
            <strong class="d-block">Versão</strong>
            <span data-detail-field="version">-</span>
          </div>
        </div>

        <div class="d-none" data-equivalence-details>
          <hr>
          <h6 class="font-weight-bold mb-3">Dados da equivalência</h6>
          <div class="row">
            <div class="col-md-4 mb-3">
              <strong class="d-block">Número da reunião</strong>
              <span data-equivalence-field="meetingNumber">-</span>
            </div>
            <div class="col-md-4 mb-3">
              <strong class="d-block">Data da reunião</strong>
              <span data-equivalence-field="meetingDate">-</span>
            </div>
          </div>
          <div class="mb-3">
            <strong class="d-block">Observações</strong>
            <span data-equivalence-field="notes">-</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
