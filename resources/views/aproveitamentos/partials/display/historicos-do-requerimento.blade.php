{{-- Lista os arquivos de histórico escolar anexados ao requerimento. --}}
<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <strong>Histórico escolar</strong>
    <span class="badge badge-outline-secondary">{{ count($show_data['historicos']) }}</span>
  </div>
  <div class="card-body">
    @forelse ($show_data['historicos'] as $arquivo)
      @include('aproveitamentos.partials.show-arquivo', [
          'arquivo' => $arquivo,
          'aproveitamentoId' => $show_data['id'],
      ])
    @empty
      <p class="alert alert-light border text-center mb-0">Nenhum histórico escolar foi enviado.</p>
    @endforelse
  </div>
</div>
