@extends('layouts.app')

@section('content')

  <div class="card">
    <div class="card-header h4">
      Aproveitamentos automáticos
    </div>
    <div class="card-body">
      @if (empty($cursos))
        <p class="mb-0">Nenhum curso/habilitação ativo encontrado.</p>
      @else
        <div class="table-responsive">
          <table class="table table-striped table-bordered mb-0">
            <thead>
              <tr>
                <th>Cursos</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($cursos as $curso)
                <tr>
                  <td>
                   <p> <a href="{{ route('equivalencias.show', [$curso['codcur'], $curso['codhab']]) }}">
                      {{ $curso['nomcur'] ?? '-' }} ({{ $curso['codcur'] ?? '-' }})
                    </a>
                    / {{ $curso['nomhab'] ?? '-' }} ({{ $curso['codhab'] ?? '-' }})</p>
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
