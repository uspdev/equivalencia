@extends('layouts.app')

@section('content')

  <div class="card">
    <x-page-header :breadcrumbs="[['label' => 'Aproveitamentos automáticos']]" />

    <div class="card-body">
      @if (empty($cursos))
        <p class="mb-0">Nenhum curso/habilitação ativo encontrado.</p>
      @else
        <div class="table-responsive">
          <table class="table table-striped table-bordered mb-0">
            <thead>
              <tr>
                <th>Cursos/Habilitação</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($cursos as $curso)
                @php
                  $totalAproveitamentos = $totaisAproveitamentos[$curso['codcur'] . '/' . $curso['codhab']] ?? 0;
                @endphp
                <tr>
                  <td>
                    <p> <a href="{{ route('equivalencias.show', [$curso['codcur'], $curso['codhab']]) }}">
                        {{ $curso['codcur'] ?? '-' }} - {{ $curso['nomcur'] ?? '-' }}
                      </a>
                      / {{ $curso['nomhab'] ?? '-' }}
                      <span class="badge badge-outline-secondary ml-1" title="Total de aproveitamentos automáticos">
                        {{ $totalAproveitamentos }}
                      </span>
                    </p>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
@endsection
