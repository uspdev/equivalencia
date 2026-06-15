@extends('layouts.app')

@section('content')
  <div class="card">
    @include('aproveitamentos.partials.display.requerimento-header')
    <div class="card-body card-body-sticky">
      @include('aproveitamentos.partials.display.disciplinas-do-requerimento')
    </div>
  </div>
@endsection
