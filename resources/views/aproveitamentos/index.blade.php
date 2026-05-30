@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header card-header-sticky">
        <strong class ="text-info" style="font-size: 24px;">Minhas requisições</strong>
    </div>
    <div class="card-body">
        <div class="card">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="text-center">Disciplina requerida</th>
                        <th class="text-center">Estado atual</th>
                        <th class="text-center">Grupo</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requisicoes as $reqinfo)
                        <tr>
                            <td class="text-center">
                                <a href="{{ route('equivalencias.req-show', ['group' => $reqinfo['grupo']]) }}">
                                    {{ $reqinfo['nomdis'] }}
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-warning">{{ $reqinfo['estado'] ?? 'PLACEHOLDERS_NULO' }}</span>
                            </td>
                            <td class="text-center">{{ $reqinfo['grupo'] }}</td>
                            <td class="text-center">
                                <a href="{{ route('equivalencias.req-destroy', ['group' => $reqinfo['grupo']]) }}" class="btn btn-sm btn-danger">Remover</a>
                                <a href="{{ route('equivalencias.req-edit', ['group' => $reqinfo['grupo']]) }}" class="btn btn-sm btn-warning">Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
  
@endsection