@extends('layouts.app')

@section('content')
<div class="mt-3">
    <div class="mb-3 d-flex align-items-center justify-content-left">
        <a href="{{ route('equivalencias.index') }}" style="font-size: 2em;" class="mr-2">Cursos</a>
        <div class="d-flex align-items-center pt-2"><i class="fas fa-chevron-right" style="font-size: 1.2em;"></i></div>
        <h2 class="mb-3 ml-2 pt-3">Equivalências Automáticas ({{ $codcur }}/{{ $codhab }})</h2>
        <div class="mt-2 ml-2">@include('equivalencias.partials.modal-create')</div>
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
                                <th>Equivalências</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($disciplinas as $disciplina)
                                <tr>
                                    <td><a href="{{ route('equivalencias.show', [$codcur, $codhab, $disciplina]) }}">{{ $disciplina->coddis }}</a></td>
                                    <td>{{ $disciplina->nome_disciplina ?: '-' }}</td>
                                    <td>{{ $disciplina->verdis ?: '-' }}</td>
                                    <td>
                                        @forelse ($disciplina->equivalentes as $equivalencia)
                                            <div>{{ $equivalencia->coddis ?: '-' }} - {{ $equivalencia->nome_disciplina ?: '-' }}</div>
                                        @empty
                                            -
                                        @endforelse
                                    </td>
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
