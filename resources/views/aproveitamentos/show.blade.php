@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header-sticky card-header">
        <h3 class="">
            Requisição de equivalência &rarr; 
            <span class="text-primary">
                <strong>{{$show_data['requerida']['coddis'] .' - ' . $show_data['requerida']['nomdis'] }}</strong>
            </span>
        </h3>
    </div>
    <div class="card-body card-body-sticky">
        @foreach ($show_data['cursada'] as $cursada)
            
        @endforeach
    </div>
</div>

@endsection