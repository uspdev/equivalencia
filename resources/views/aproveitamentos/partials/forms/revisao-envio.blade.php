{{-- Exibe o bloco final de revisão e botão de envio do requerimento. --}}
<div class="card">
  <div class="card-header">
    <strong>Revisão e envio</strong>
  </div>
  <div class="card-body">
    <p class="text-muted">
      Confira as disciplinas e o histórico escolar antes de enviar o requerimento.
    </p>
    <button type="submit" class="btn btn-success" @disabled(!$canSubmit)>
      Enviar requerimento
    </button>
  </div>
</div>
