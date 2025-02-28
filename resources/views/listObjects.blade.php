@extends('layouts.app')

@section('content')
  <a href="{{ route('workflows.index') }}" class="link-primary">Voltar à pagina inicial</a>
  <h1>Gerenciar Workflows - {{ $workflowsDisplay['workflowDefinition']->name }}</h1>

  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @foreach ($workflowsDisplay['workflows'] as $workflowObject)
    <div class="card mb-3">
      <div class="card-body">
        <a href="{{ route('workflows.showObject', $workflowObject->id) }}">
          <h5 class="card-title">Workflow ID: {{ $workflowObject->id }}</h5>
        </a>
        
        <p class="card-text">Estado atual: {{ $workflowsDisplay['workflowDefinition']->definition['places'][$workflowObject->state]['description']  }}</p>
        <form action="{{ route('workflows.delete-object', $workflowObject->id) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
      </div>
    </div>
    
  @endforeach
@endsection
