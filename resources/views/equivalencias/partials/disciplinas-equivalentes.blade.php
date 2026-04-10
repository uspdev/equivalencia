@php
  $equivalenciasPorGrupo = $disciplina->equivalentes->groupBy('grupo');
@endphp

@forelse ($equivalenciasPorGrupo as $grupo => $equivalenciasDoGrupo)
  @php
    $equivalenciaRepresentante = $equivalenciasDoGrupo->first();
  @endphp

  <div class="disciplina-equivalente d-flex align-items-center flex-nowrap mb-2">
    @if ($equivalenciaRepresentante)
      @include('equivalencias.partials.modal-edit-equivalencia', ['equivalencia' => $equivalenciaRepresentante])
      <form action="{{ route('equivalencias.destroy-equivalencia-grupo', [$codcur, $codhab, $disciplina, $equivalenciaRepresentante]) }}" method="POST" class="d-inline mr-2">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger ml-2 btn-remover" title="Remover grupo de equivalências"
          onclick="return confirm('Tem certeza que deseja remover todas as equivalências deste grupo?')">
          <i class="fas fa-trash"></i>
        </button>
      </form>
    @endif

    <p class="mb-0 text-truncate">
      @foreach ($equivalenciasDoGrupo as $e)
        <span>{{ $e->coddis ?: '-' }} - {{ $e->nome_disciplina ?: '-' }} ({{ $e->ies }})</span>
        @if (! $loop->last)
          <span class="mx-2">|</span>
        @endif
      @endforeach
    </p>
  </div>
@empty
  -
@endforelse
