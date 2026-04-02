<a href="{{ route('equivalencias.show', [$codcur, $codhab, $disciplina]) }}">
  {{ $disciplina->coddis }}
</a>
- {{ $disciplina->nome_disciplina ?: '-' }} ({{ $disciplina->verdis ?: '-' }})

{{-- precisa fazer esses botões funcionais --}}
{{-- fazer ele aparecer com o hover --}}
<button class="btn btn-sm btn-outline-primary mx-2"><i class="fas fa-edit"></i></button>
