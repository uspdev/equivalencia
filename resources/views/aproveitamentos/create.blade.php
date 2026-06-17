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
    <x-page-header :breadcrumbs="[
        ['label' => 'Meus requerimentos', 'url' => route('equivalencias.req-index')],
        ['label' => 'Novo requerimento'],
    ]" />

    <div class="card-body">

      @include('aproveitamentos.partials.display.disciplina-desejada')
      @include('aproveitamentos.partials.display.disciplinas-cursadas')
      @include('aproveitamentos.partials.forms.form-requerimento')
    </div>
  </div>

  @include('aproveitamentos.partials.modals.modal-create-disciplina-cursada')
  @include('aproveitamentos.partials.modals.modal-edit-disciplina-cursada')
  @include('aproveitamentos.partials.scripts.rascunho')
@endsection
