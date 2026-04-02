@extends('layouts.app')

@section('content')
    <div class="mt-3">
        <div class="mb-3 d-flex">
            <a href="{{ route('equivalencias.index') }}" style="font-size: 2em;" class="mr-2">Cursos</a>
            <div class="d-flex align-items-center"><i class="fas fa-chevron-right" style="font-size: 1.2em;"></i></div>
            <a href="{{ route('equivalencias.curso', [$codcur, $codhab]) }}" style="font-size: 2em;"
                class="mr-2 ml-2">{{ $nomeCurso ?? ($disciplinas->first()->nomcur ?? 'Curso') }}
                ({{ $codcur }}/{{ $codhab }})</a>
            <div class="d-flex align-items-center"><i class="fas fa-chevron-right" style="font-size: 1.2em;"></i></div>
            <h2 class="ml-2 mt-2">{{ $disciplina->coddis }}</h2>
        </div>

        <div class="card mb-4">
            <div class="card-header">Dados da disciplina requerida</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Disciplina requerida</th>
                                <th>Verdis</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>({{ $disciplina->coddis ?: '-' }}) {{ $disciplina->nome_disciplina ?: '-' }}</td>
                                <td>{{ $disciplina->verdis ?: '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @include('equivalencias.partials.modal-edit')
                                        <form action="{{ route('equivalencias.destroy', [$codcur, $codhab, $disciplina]) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Remover disciplina e suas equivalências?')">Remover</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex">
                Disciplinas cursadas equivalentes cadastradas
                <div class="ml-2">
                    @include('equivalencias.partials.modal-equivalencia', [
                        'modalId' => "modalAdicionarEquivalencia{$disciplina->id}",
                        'modalLabelId' => "modalAdicionarEquivalenciaLabel{$disciplina->id}",
                        'formHtmlEquivalencia' => $formHtmlEquivalencia,
                    ])
                </div>
            </div>
            <div class="card-body p-0">
                @if ($equivalencias->isEmpty())
                    <p class="p-3 mb-0">Nenhuma disciplina cursada equivalente cadastrada para esta disciplina.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered datatable-simples mb-0">
                            <thead>
                                <tr>
                                    <th>Disciplina equivalente</th>
                                    <th>IES</th>
                                    <th style="width: 40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($equivalencias as $equivalencia)
                                    <tr>
                                        <td>({{ $equivalencia->coddis ?: '-' }})
                                            {{ $equivalencia->nome_disciplina ?: '-' }}</td>
                                        <td>{{ $equivalencia->ies ?: '-' }}</td>
                                        <td>
                                            <div class="d-flex">
                                                @include('equivalencias.partials.modal-edit-equivalencia')
                                                <form
                                                    action="{{ route('equivalencias.destroy-equivalencia', [$codcur, $codhab, $disciplina, $equivalencia]) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Remover equivalência?')">Remover</button>
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
