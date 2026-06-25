@php
  $method = strtoupper($method ?? 'POST');
  $formMethod = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';
  $values = $values ?? [];
  $formId = $formId ?? 'form-equivalencia-' . uniqid();
  $useOldInput = $useOldInput ?? true;
  $dynamic = $dynamic ?? false;
  // Monta o estado do formulário com base nos valores fornecidos, no número máximo de disciplinas e se deve usar os valores antigos.
  $formState = \App\Models\Disciplina::estadoFormularioEquivalencia($values, 3, $useOldInput);
@endphp

<form id="{{ $formId }}" method="{{ $formMethod }}" action="{{ $action }}" class="equivalencia-filhas-form"
  data-max-disciplinas="{{ $formState['maxDisciplinas'] }}">
  @csrf
  @if ($dynamic)
    <input type="hidden" name="_method" value="" data-dynamic-method disabled>
    <input type="hidden" name="_modal_type" value="equivalence">
    <input type="hidden" name="_modal_key" value="">
  @elseif (!in_array($method, ['GET', 'POST'], true))
    @method($method)
  @endif

  @include('aproveitamentos_automaticos.partials.form-equivalencia.administrative-fields')

  @foreach ($formState['blocks'] as $block)
    @include('aproveitamentos_automaticos.partials.form-equivalencia.fieldset')
  @endforeach

  @include('aproveitamentos_automaticos.partials.form-equivalencia.actions')
</form>

@include('aproveitamentos_automaticos.partials.form-equivalencia.scripts')
