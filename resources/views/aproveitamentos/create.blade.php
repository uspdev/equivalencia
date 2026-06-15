@extends('layouts.app')

@section('content')
  @php
    $openDisciplineModal = session('discipline_modal');
    $selectedRequiredCode = $openDisciplineModal
        ? old('requerida_coddis', $draft->requerida_coddis)
        : $draft->requerida_coddis;
    $canSubmit = $draft->requerida_coddis && $disciplines->isNotEmpty();
  @endphp

  <div class="card">
    <div class="card-header card-header-sticky">
      <h3 class="mb-0">Novo requerimento de aproveitamento de estudos</h3>
    </div>

    <div class="card-body">
      @include('aproveitamentos.partials.forms.errors', [
          'title' => 'Revise os dados do requerimento.',
          'show' => $errors->any() && !$openDisciplineModal,
      ])

      @include('aproveitamentos.partials.display.disciplina-desejada')
      @include('aproveitamentos.partials.display.disciplinas-cursadas')
      @include('aproveitamentos.partials.forms.form-requerimento')
    </div>
  </div>

  @include('aproveitamentos.partials.modals.modal-create-disciplina-cursada')
  @include('aproveitamentos.partials.modals.modal-edit-disciplina-cursada')
  @include('aproveitamentos.partials.scripts.rascunho')
@endsection
