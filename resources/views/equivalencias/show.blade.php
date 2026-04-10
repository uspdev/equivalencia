@extends('layouts.app')

@section('content')

  <div class="card">
    <div class="card-header d-flex align-items-center">
        <h4 class="mb-0">
            <a href="{{ route('equivalencias.index') }}">Cursos</a>
            <i class="fas fa-angle-right mx-2"></i>
            {{ $nomeCurso }} ({{ $codcur }}/{{ $codhab }})
        </h4>

        <div class="pt-2">
          @include('equivalencias.partials.modal-create')
        </div>
    </div>

    <div class="card-body">
      @if ($disciplinas->isEmpty())
        <p class="mb-0">Nenhuma disciplina requerida cadastrada.</p>
      @else
        <table class="table table-striped table-bordered datatable-simples dt-state-save">
          <thead>
            <tr>
              <th>Disciplina requerida (versão)</th>
              <th></th>
              <th>Disciplinas cursadas (IES)</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($disciplinas as $disciplina)
              <tr>
                <td>
                  @include('equivalencias.partials.disciplina-requerida')
                </td>
                <td>
                  @include('equivalencias.partials.modal-equivalencia', [
                      'modalId' => "modalAdicionarEquivalencia{$disciplina->id}",
                      'modalLabelId' => "modalAdicionarEquivalenciaLabel{$disciplina->id}",
                      'formHtmlEquivalencia' => $formHtmlEquivalencia[$disciplina->id] ?? '',
                  ])
                </td>
                <td>
                  @include('equivalencias.partials.disciplinas-equivalentes')
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>

  <div class="mt-3">
    {{-- não vamos usar paginação pois não serão muitas disciplinas requeridas --}}
    {{ $disciplinas->links() }}
  </div>
@endsection
