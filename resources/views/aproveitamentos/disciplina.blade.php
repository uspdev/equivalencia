@extends('layouts.app')

@section('content')
  <div class="card">
    <div class="card-header card-header-sticky">
      <h3 class="mb-0">{{ $discipline ? 'Editar disciplina cursada' : 'Adicionar disciplina cursada' }}</h3>
    </div>
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
