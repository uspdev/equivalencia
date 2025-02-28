@extends('layouts.app')

@can('admin')
  @section('content')
    <a href="{{ route('workflows.index') }}" class="link-primary">Voltar à pagina inicial</a>
    <div class="row">
      @foreach ($workflowDefinitionData['places'] as $key => $place)
        <div class="col-md-4">

          <div class="card m-3">
            <div class="card">
              <form method="post" id="form-{{ $key }}" action="{{ route('workflows.setuser') }}">
                @csrf
                @method('put')
                <input type="hidden" name="place" value="{{ $key }}">
                <input type="hidden" name="workflowDefinitionName" value="{{ $workflowDefinitionData['definitionName'] }}">
                <div class="card-header py-1">
                  <span class="h5">
                    {{ $place['description'] }}
                    @include('partials.codpes-adicionar-btn')
                  </span><br>
                </div>
                <div class="card-body py-1">
                  @foreach (Spatie\Permission\Models\Role::findByName($key)->users as $user)
                    <div class="hover">
                      <span>{{ $user->name }}</span>
                      <span class="hide">
                        @if ($user->codpes != auth()->user()->codpes)
                          @include('partials.codpes-remover-btn', ['codpes' => $user->codpes])
                        @endif
                      </span>
                    </div>
                  @endforeach
                </div>
              </form>

            </div>
          </div>
        </div>
      @endforeach
    </div>
    </div>
    <div class="d-flex align-items-center ">
      <h1 class="mb-4 ml-2">Detalhes do workflow {{ $workflowDefinitionData['definitionName'] }}</h1>
      <a href="{{ route('workflows.editDefinition', $workflowDefinitionData['definitionName']) }}"
        class="btn btn-warning btn-sm mb-3 ml-4">Editar workflow</a>
    </div>
    <div class="row ml-2">
      <div class="col-md-7">
        <h2>Definições</h2>
        <pre>{{ $workflowDefinitionData['formattedJson'] }}</pre>
      </div>
      <div class="col-md-5">
        <img src="{{ asset('../' . $workflowDefinitionData['path']) }}" class="img-fluid w-300">
      </div>
    @endsection
  @endcan
