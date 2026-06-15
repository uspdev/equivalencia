{{-- Exibe o bloco final de revisão e botão de envio do requerimento. --}}
<div class="card">
  <div class="card-header">
    <strong>Revisão e envio</strong>
  </div>
  <div class="card-body">
    <p class="text-muted">
      As disciplinas são salvas no rascunho. Os históricos escolares serão salvos ao enviar o requerimento.
    </p>
    <button type="submit" class="btn btn-success" @disabled(!$canSubmit)>
      Enviar requerimento
    </button>
  </div>
</div>
