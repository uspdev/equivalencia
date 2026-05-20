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
        <div class="card card-header card-header-sticky">
            <div class="row text-center h2 mb-0 mt-0">
                <div class="text-info col"><strong>Código</strong></div>
                <div class="text-warning col"><strong>Nome</strong></div>
                <div class="text-secondary col"><strong>Semestre</strong></div>
                <div class="text-danger col"><strong>Ano</strong></div>
                <div class="col"><strong>Frequência</strong></div>
                <div class="col"><strong>Nota</strong></div>
                <div class="col"><strong>Créditos</strong></div>
                <div class="col"><span class="text-nowrap"><strong>Carga horária</strong></span></div>
            </div>
        </div>
        <hr>
        @foreach ($show_data['cursadas'] as $cursada)
            <div class="card card-header">
                <div class="row text-center h3 mb-0 mt-0 ml-0 mr-0">
                    <div class="text-info col">{{ $cursada['coddis'] }}</div>
                    <div class="text-warning col">{{ $cursada['nomdis'] }}</div>
                    <div class="text-secondary col">{{ $cursada['semestre'] }}°</div>
                    <div class="text-danger col">{{ $cursada['ano'] }}</div>
                    <div class="col">{{ $cursada['freq'] }}%</div>
                    <div class="col">{{ $cursada['nota'] }}</div>
                    <div class="col">{{ $cursada['creditos'] }}</div>
                    <div class="col">{{ $cursada['carga_hr'] }}</div>
                </div>
            </div>
            @if (!$loop->last)
                <br>                
            @endif
        @endforeach
    </div>
</div>

@endsection