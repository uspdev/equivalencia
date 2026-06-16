@extends('layouts.app')

@section('content')
  <div class="card">
    <x-page-header
      :breadcrumbs="[
          ['label' => 'Meus requerimentos'],
      ]"
    />

    <div class="card-body">
      @include('aproveitamentos.partials.display.requisicoes')
    </div>
  </div>
@endsection
