@extends('layouts.app')

@section('content')
  @php
    $pageTitle = $discipline ? 'Editar disciplina cursada' : 'Adicionar disciplina cursada';
  @endphp

  <div class="card">
    <x-page-header
      :breadcrumbs="[
          ['label' => 'Meus requerimentos', 'url' => route('equivalencias.req-index')],
          ['label' => 'Novo requerimento', 'url' => route('equivalencias.newreq-create')],
          ['label' => $pageTitle],
      ]"
    />

    <div class="card-body">
      @include('aproveitamentos.partials.forms.errors')

      @include('aproveitamentos.partials.forms.form-disciplina-cursada', [
          'discipline' => $discipline,
          'formAction' => $formAction,
          'formMethod' => $formMethod,
          'requiredDisciplineCode' => $requiredDisciplineCode,
          'fieldPrefix' => 'standalone',
      ])
    </div>
  </div>
@endsection
