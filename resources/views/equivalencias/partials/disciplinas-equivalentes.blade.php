@forelse ($disciplina->equivalentes as $e)
  <div class="disciplina-equivalente">
    {{ $e->coddis ?: '-' }} - {{ $e->nome_disciplina ?: '-' }} ({{ $e->ies }})
    @include('equivalencias.partials.remover-equivalente-btn')
  </div>
@empty
  -
@endforelse
