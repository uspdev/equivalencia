{{-- Lista as disciplinas cursadas informadas no requerimento enviado. --}}
<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <strong>Disciplinas cursadas</strong>
    <span class="badge badge-outline-secondary">{{ count($show_data['cursadas']) }}</span>
  </div>
  <div class="card-body">
    @forelse ($show_data['cursadas'] as $cursada)
      @include('aproveitamentos.partials.display.disciplina-do-requerimento', [
          'position' => $loop->iteration,
          'aproveitamentoId' => $show_data['id'],
      ])
    @empty
      <p class="alert alert-light border text-center mb-0">Nenhuma disciplina cursada foi informada.</p>
    @endforelse
  </div>
</div>
