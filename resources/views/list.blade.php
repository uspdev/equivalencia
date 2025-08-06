@extends('uspdev-forms::layouts.app')
@can('admin')
  @section('content')
    <div class="card">
      <div class="card-header h4 card-header-sticky d-flex justify-content-between align-items-center">
        <div>
          <span class="text-danger">USPdev forms</span> >
          Definições de Workflow
        </div>
        <div>
          <a href="{{ route('workflows.create-definition') }}" class="btn btn-sm btn-primary">Nova Definição de Workflow</a>
        </div>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-hover">
          <thead>
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
