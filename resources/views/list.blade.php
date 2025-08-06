@extends('layouts.app')

@can('admin')
  @section('content')
    <div class="container mt-2">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-3">Definições de workflow</h1>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <a href="{{ route('workflows.create-definition') }}" class="btn btn-primary">Nova Definição</a>
        </div>
      </div>
      <p>Estas são as definições de workflow/requerimento cadastradas no sistema.
        Para criar uma nova definição, clique em "Nova definição" acima.
        Para gerenciar uma definição, clique em seu nome ou, para excluir, clique no botão à frente da definição.</p>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
          <thead class="table-light">
            <tr>
              <th>Nome</th>
              <th>Descrição</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($workflowDefinitions as $definition)
              <tr>
                <td><a href="{{ route('workflows.showDefinition', $definition->name) }}" class="link-primary">
                    {{ $definition->name }}
                  </a>
                </td>
                <td>{{ $definition->description ?? '' }}</td>
                <td class="d-flex justify-content-start">
                  <form action="{{ route('workflows.destroyDefinition', $definition->name) }}" method="POST"
                    class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"
                      onclick="return confirm('Tem certeza que deseja excluir esta definição?')">Excluir</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endsection
@endcan
