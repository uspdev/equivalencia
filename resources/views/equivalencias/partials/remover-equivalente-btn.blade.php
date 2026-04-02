<button class="btn btn-sm btn-outline-danger ml-2 btn-remover" title="Remover equivalência"
  onclick="return confirm('Tem certeza que deseja remover esta equivalência?')">
  <i class="fas fa-trash"></i>
</button>

@section('styles')
  @parent
  <style>
    .disciplina-equivalente .btn-remover {
      opacity: 0;
      transition: opacity 0.2s;
    }

    .disciplina-equivalente:hover .btn-remover {
      opacity: 1;
    }
  </style>
@endsection