@extends('layouts.app')

@section('content')
<div class="mt-3">
    <div class="mb-3 d-flex align-items-center justify-content-left">
        <h2 class="mb-3 mr-3">Equivalências Automáticas</h2>
        @include('equivalencias.partials.modal-create')
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
                                    <td>{{ $disciplina->equivalentes->pluck('coddis')->filter()->implode(', ') ?: '-' }}</td>
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
