@php
  $method = strtoupper($method ?? 'POST');
  $formMethod = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';
  $values = $values ?? [];
  $formId = $formId ?? 'form-equivalencia-' . uniqid();
  $formState = \App\Models\Disciplina::estadoFormularioEquivalencia($values);
@endphp

<form id="{{ $formId }}" method="{{ $formMethod }}" action="{{ $action }}" class="equivalencia-filhas-form"
  data-max-disciplinas="{{ $formState['maxDisciplinas'] }}">
  @csrf
  @if (!in_array($method, ['GET', 'POST'], true))
    @method($method)
  @endif

  @include('aproveitamentos_automaticos.partials.form-equivalencia.administrative-fields')

  @foreach ($formState['blocks'] as $block)
    @include('aproveitamentos_automaticos.partials.form-equivalencia.fieldset')
  @endforeach

  @include('aproveitamentos_automaticos.partials.form-equivalencia.actions')
</form>

@include('aproveitamentos_automaticos.partials.form-equivalencia.scripts')
