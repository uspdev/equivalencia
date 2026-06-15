{{-- Agrupa os campos usados para cadastrar ou editar uma disciplina cursada. --}}
@php
  $fieldPrefix = $fieldPrefix ?? 'discipline';
  $useOldInput = $useOldInput ?? true;
  $fieldId = fn(string $name) => $fieldPrefix . '_' . $name;
  $value = fn(string $name, mixed $default = null) => $useOldInput ? old($name, $default) : $default;
  $unitType = $value('unidade_tipo', $discipline['unidade_tipo'] ?? 'USP');
  $selectedCode = $value('coddis', $discipline['coddis'] ?? null);
  $selectedName = $value('nomdis', $discipline['nomdis'] ?? null);
@endphp

<div class="js-discipline-fields" data-has-syllabus="@json(isset($discipline['ementa']))">
  @include('aproveitamentos.partials.forms.campos-disciplina-cursada.unidade')
  @include('aproveitamentos.partials.forms.campos-disciplina-cursada.periodo')
  @include('aproveitamentos.partials.forms.campos-disciplina-cursada.dados-externos')
</div>

@include('aproveitamentos.partials.scripts.campos-disciplina-cursada')
