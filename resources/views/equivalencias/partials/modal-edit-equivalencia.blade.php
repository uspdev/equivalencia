<button type="button" class="btn btn-sm btn-outline-primary mr-2 btn-editar" data-toggle="modal" data-target="#modalEditarEquivalencia{{ $equivalencia->id }}">
  <i class="fas fa-edit"></i>
</button>

<div class="modal fade" id="modalEditarEquivalencia{{ $equivalencia->id }}" tabindex="-1" aria-labelledby="modalEditarEquivalenciaLabel{{ $equivalencia->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarEquivalenciaLabel{{ $equivalencia->id }}">Editar disciplina cursada equivalente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {!! $formHtmlEquivalenciaEdit[$equivalencia->id] ?? '' !!}
            </div>
        </div>
    </div>
</div>
