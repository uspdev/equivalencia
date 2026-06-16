{{--
  Este arquivo contém os campos específicos para disciplinas da USP dentro do formulário de equivalência.
  Ele é incluído dentro do fieldset de cada bloco, e sua visibilidade é controlada por classes CSS e pelo estado do bloco.
--}}
@include('aproveitamentos.partials.forms.campo-disciplina-usp', [
    'name' => $coddisField,
    'id' => $baseId . '-usp-coddis',
    'label' => 'Código da disciplina',
    'selected' => $isUsp ? $coddis : null,
    'selectedName' => $isUsp ? $nome : null,
    'required' => $visible && $isUsp,
    'disabled' => !($visible && $isUsp),
    'class' => 'js-equivalencia-usp-select',
])
