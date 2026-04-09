@php
  $equivalenciasPorGrupo = $disciplina->equivalentes->groupBy('grupo');
@endphp

@forelse ($equivalenciasPorGrupo as $grupo => $equivalenciasDoGrupo)
  @php
    $equivalenciaRepresentante = $equivalenciasDoGrupo->first();
  @endphp

  <div class="mb-2 d-flex align-items-center">
    <span class="badge badge-pill badge-info mr-2">
      Equivalência {{ $grupo }}
    </span>
    @if ($equivalenciaRepresentante)
      @include('equivalencias.partials.modal-edit-equivalencia', ['equivalencia' => $equivalenciaRepresentante])
    @endif
  </div>

  @foreach ($equivalenciasDoGrupo as $e)
    <div class="disciplina-equivalente d-flex align-items-center">
      <p>{{ $e->coddis ?: '-' }} - {{ $e->nome_disciplina ?: '-' }} ({{ $e->ies }})</p>
      <div class="mr-2">@include('equivalencias.partials.remover-equivalente-btn')</div>
    </div>
  @endforeach
@empty
  -
@endforelse
@section('styles')
  @parent
  <style>
    .disciplina-equivalente .btn-remover,
    .disciplina-equivalente .btn-editar {
      opacity: 0;
      transition: opacity 0.2s;
    }

    .disciplina-equivalente:hover .btn-remover,
    .disciplina-equivalente:hover .btn-editar {
      opacity: 1;
    }
  </style>
@endsection
