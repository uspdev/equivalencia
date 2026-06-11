@php
    $modalId = $modalId ?? ('modalEditarDisciplina' . ($disciplina->id ?? ''));
    $modalLabelId = $modalLabelId ?? ($modalId . 'Label');
@endphp

<button type="button" class="btn btn-outline-primary btn-sm mr-2" data-toggle="modal" data-target="#{{ $modalId }}">
  <i class="fas fa-edit"></i>
</button>

<div class="modal fade equivalencias-edit-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalLabelId }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                                <h5 class="modal-title" id="{{ $modalLabelId }}">Editar disciplina requerida</h5>
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

                @include('aproveitamentos_automaticos.partials.forms.form-disciplina-requerida', [
                    'action' => route('equivalencias.update', [$codcur, $codhab, $disciplina]),
                    'method' => 'PUT',
                    'id' => $modalId.'-coddis',
                    'selected' => $disciplina->coddis,
                    'selectedName' => $disciplina->nome_disciplina,
                ])
            </div>
        </div>
    </div>
</div>

@once
    <script>
        $(document).on('show.bs.modal', '.equivalencias-edit-modal', function () {
            if (this.parentNode !== document.body) {
                document.body.appendChild(this);
            }
        });
    </script>
@endonce
