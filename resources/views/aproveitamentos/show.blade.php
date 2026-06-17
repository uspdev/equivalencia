@extends('layouts.app')

@section('content')
  <div class="card">
    @include('aproveitamentos.partials.display.requerimento-header')

    <div class="card-body">
      @include('aproveitamentos.partials.display.resumo-requerimento')

      <div class="row">
        <div class="col-lg-4">
          @include('aproveitamentos.partials.display.disciplina-requerida')
          @include('aproveitamentos.partials.display.historicos-do-requerimento')
        </div>

        <div class="col-lg-8">
          @include('aproveitamentos.partials.display.disciplinas-do-requerimento')
        </div>
      </div>
    </div>
  </div>
@endsection
