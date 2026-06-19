{{-- Renderiza o formulário para criar ou editar uma disciplina requerida automática. --}}
@php
  $method = strtoupper($method ?? 'POST');
  $formMethod = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';
  $selected = old('coddis', $selected ?? null);
  $selectedVerdis = old('verdis', $selectedVerdis ?? null);
  $selectedName = $selectedName ?? null;
@endphp

<form method="{{ $formMethod }}" action="{{ $action }}">
  @csrf
  @if (!in_array($method, ['GET', 'POST'], true))
    @method($method)
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
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
