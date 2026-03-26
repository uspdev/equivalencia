@extends('layouts.app')

@section('content')
<div class="container mt-3">
    <div class="mb-3 d-flex">
        <h2><a href="{{ route('equivalencias.index') }}">Disciplinas USP</a></h2>
        <h2> / </h2>
        <h2 class="mb-0">{{ $disciplina->coddis }}</h2>   
    </div>

    <div class="card mb-4">
        <div class="card-header">Dados da disciplina requerida</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Verdis</th>
                            <th>Codcur</th>
                            <th>Codhab</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $disciplina->coddis ?: '-' }}</td>
                            <td>{{ $disciplina->nome_disciplina ?: '-' }}</td>
                            <td>{{ $disciplina->verdis ?: '-' }}</td>
                            <td>{{ $disciplina->codcur ?: '-' }}</td>
                            <td>{{ $disciplina->codhab ?: '-' }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('equivalencias.edit', $disciplina) }}" class="btn btn-primary btn-sm mr-2">Editar</a>
                                    <form action="{{ route('equivalencias.destroy', $disciplina) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remover disciplina e suas equivalências?')">Remover</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Adicionar disciplina cursada</div>
        <div class="card-body">
            {!! $formHtmlEquivalencia !!}
        </div>
    </div>

    <div class="card">
        <div class="card-header">Disciplinas cursadas equivalentes cadastradas</div>
        <div class="card-body p-0">
            @if ($equivalencias->isEmpty())
                <p class="p-3 mb-0">Nenhuma disciplina cursada equivalente cadastrada para esta disciplina.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-bordered datatable-simples mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>IES</th>
                                <th>Créditos</th>
                                <th>Carga horária</th>
                                <th style="width: 40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($equivalencias as $equivalencia)
                
                                <tr>
                                    <td>{{ $equivalencia->coddis }}</td>
                                    <td>{{ $equivalencia->nome_disciplina ?: '-' }}</td>
                                    <td>{{ $equivalencia->ies ?: '-' }}</td>
                                    <td>{{ $equivalencia->creditos ?: '-' }}</td>
                                    <td>{{ $equivalencia->carga_horaria ?: '-' }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <form action="{{ route('equivalencias.destroy-equivalencia', [$disciplina, $equivalencia]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover equivalência?')">Remover</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
