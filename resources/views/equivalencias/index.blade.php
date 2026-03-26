@extends('layouts.app')

@section('content')
<div class="container mt-3">
    <div class="mb-3">
        <h2 class="mb-3">Disciplinas Requeridas</h2>
        <a href="{{ route('equivalencias.create') }}" class="btn btn-primary">Nova disciplina requerida</a>
    </div>
    <div class="card">
        <div class="card-body">
            @if ($disciplinas->isEmpty())
                <p class="mb-0">Nenhuma disciplina requerida cadastrada.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-bordered datatable-simples mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Verdis</th>
                                <th>Codcur</th>
                                <th>Codhab</th>
                                <th>Equivalências</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($disciplinas as $disciplina)
                                <tr>
                                    <td><a href="{{ route('equivalencias.show', $disciplina) }}">{{ $disciplina->coddis }}</a></td>
                                    <td>{{ $disciplina->nome_disciplina ?: '-' }}</td>
                                    <td>{{ $disciplina->verdis ?: '-' }}</td>
                                    <td>{{ $disciplina->codcur ?: '-' }}</td>
                                    <td>{{ $disciplina->codhab ?: '-' }}</td>
                                    <td>{{ $disciplina->equivalentes_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-3">
        {{ $disciplinas->links() }}
    </div>
</div>
@endsection
