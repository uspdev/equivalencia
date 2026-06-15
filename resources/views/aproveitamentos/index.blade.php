@extends('layouts.app')

@section('content')
  <div class="card">
    <div class="card-header card-header-sticky">
      <strong class="text-info" style="font-size: 24px;">Minhas requisições</strong>
    </div>
    <div class="card-body">
      @include('aproveitamentos.partials.display.requisicoes')
    </div>
  </div>
@endsection
