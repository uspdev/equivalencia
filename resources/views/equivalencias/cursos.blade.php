@extends('layouts.app')

@section('content')
<div class="mt-3">
    <div class="mb-3 d-flex align-items-center justify-content-left">
        <h2 class="mb-0">Cursos e habilitações</h2>
    </div>

    <div class="card">
        <div class="card-body">
            @if (empty($cursos))
                <p class="mb-0">Nenhum curso/habilitação ativo encontrado.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-bordered datatable-simples mb-0">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Habilitação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cursos as $curso)
                                <tr>
                                    <td>
                                          <a href="{{ route('equivalencias.curso', ['codcur' => $curso['codcur'], 'codhab' => $curso['codhab']]) }}">
                                           {{ $curso['nomcur'] ?? '-' }} ({{ $curso['codcur'] ?? '-' }})
                                        </a></td>
                                    <td>{{ $curso['nomhab'] ?? '-' }} ({{ $curso['codhab'] ?? '-' }})</td>
                             
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
