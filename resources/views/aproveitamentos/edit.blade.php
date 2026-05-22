@extends('layouts.app')

@section('content')

  <div class="card-body">
    <strong style="font-size: 24px;">Requisição - {{ $submission->data['disciplina4'] }}</strong>
    <hr>
    
    <div class="card">
      <div class="card-header card-header-sticky ">{!! $formHtml !!}</div>
    </div>
  </div>
@endsection