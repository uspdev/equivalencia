{{-- Formulário compartilhado para criar ou editar uma disciplina requerida automática. --}}
@php
  $method = strtoupper($method ?? 'POST');
  $formMethod = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';
  $useOldInput = $useOldInput ?? true;
  $dynamic = $dynamic ?? false;
  $selected = $useOldInput ? old('coddis', $selected ?? null) : $selected ?? null;
  $selectedVerdis = $useOldInput ? old('verdis', $selectedVerdis ?? null) : $selectedVerdis ?? null;
  $selectedName = $selectedName ?? null;
@endphp

<form method="{{ $formMethod }}" action="{{ $action }}"
  @if (isset($formId)) id="{{ $formId }}" @endif @class(['modal-dynamic-form' => $dynamic])>
  @csrf

  {{-- Nos modais compartilhados, o JavaScript define a rota, o método e o registro selecionado. --}}
  @if ($dynamic)
    <input type="hidden" name="_method" value="" data-dynamic-method disabled>
    <input type="hidden" name="_modal_type" value="required">
    <input type="hidden" name="_modal_key" value="">
  @elseif (!in_array($method, ['GET', 'POST'], true))
    @method($method)
  @endif

  @include('aproveitamentos.partials.forms.campo-disciplina-usp', [
      'name' => 'coddis',
      'verdisName' => 'verdis',
      'id' => $id ?? 'coddis',
      'label' => 'Código da disciplina',
      'selected' => $selected,
      'selectedVerdis' => $selectedVerdis,
      'selectedName' => $selectedName,
      'required' => true,
  ])

  <div class="text-right">
    <button type="submit" class="btn btn-primary">Salvar</button>
  </div>
</form>
