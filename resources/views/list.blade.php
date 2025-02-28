@extends('layouts.app')

@can('admin')
  @section('content')
    <div class="container mt-2">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-3">Gerenciar definições de workflow</h1>
        <form action="{{ route('workflows.create-definition') }}" method="POST" class="d-inline">
          @csrf
          @method('GET')
          <button type="submit" class="btn btn-warning btn-sm">Criar nova definição de workflow</button>
        </form>
      </div>
      <p>Estas são as definições de workflow/requerimento cadastradas no sistema.
      Para criar uma nova definição, clique em "Criar nova definição de workflow" acima.
      Para gerenciar uma definição, clique em seu ID ou, para excluir, clique no botão à frente da definição.</p>
      <ul class="list-group">
        @foreach ($workflowDefinitions as $definition)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <a href="{{ route('workflows.showDefinition', $definition->name) }}" class="link-primary">
              {{ $definition->name }}
            </a>
            <div>
              {{-- <form action="{{ route('adminworkflows.list', $definition->id) }}" method="GET" class="d-inline">
                @csrf
                @method('GET')
                <button type="submit" class="btn btn-warning btn-sm">Listar formulários da definição</button>
              </form> --}}
              <form action="{{ route('workflows.destroyDefinition', $definition->name) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
              </form>
            </div>
          </li>
        @endforeach
      </ul>
    </div>
  @endsection
@endcan
