@php
  $modalId = $modalId ?? ('modalAdicionarEquivalencia' . ($disciplina->id ?? ''));
  $modalLabelId = $modalLabelId ?? ($modalId . 'Label');
  $formHtmlEquivalencia = $formHtmlEquivalencia ?? '';
@endphp

<button type="button" data-toggle="modal" data-target="#{{ $modalId }}"
  class="btn btn-sm btn-outline-success ml-4 mr-4" title="Adicionar disciplina cursada equivalente">
  <i class="fas fa-plus"></i>
</button>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalLabelId }}" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{{ $modalLabelId }}">Adicionar disciplina cursada equivalente</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        {!! $formHtmlEquivalencia !!}
      </div>
    </div>
  </div>
</div>
