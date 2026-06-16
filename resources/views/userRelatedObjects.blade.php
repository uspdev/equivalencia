@extends('layouts.app')

@section('content')
  @php
    $workflowObjects = collect($workflowsDisplay['workflows'] ?? []);
    $workflowData = $workflowsDisplay['workflowData'] ?? [];
    $totalAtendimentos = $workflowObjects->count();
    $totalPendentes = $workflowObjects
        ->filter(fn($workflowObject) => data_get($workflowData, $workflowObject->id . '.state') === 'progress')
        ->count();
  @endphp

  <div class="card atendimentos-card">
    <x-page-header
      :breadcrumbs="[
          ['label' => 'Atendimentos'],
      ]"
    >
      <x-slot:actions>
        <div class="atendimentos-summary">
          <span class="badge badge-outline-primary mr-2">{{ $totalAtendimentos }} atendimento(s)</span>
          <span class="badge badge-outline-success">{{ $totalPendentes }} em andamento</span>
        </div>
      </x-slot:actions>
    </x-page-header>

    <div class="card-body">
      @if ($workflowObjects->isEmpty())
        <div class="alert alert-info mb-0" role="alert">
          Nenhum requerimento precisa do seu atendimento no momento.
        </div>
      @else
        <div class="table-responsive">
          <table class="table datatable-simples responsive table-striped table-sm table-bordered table-hover mb-0 atendimentos-table">
            <thead>
              <tr>
                <th>Requerimento</th>
                <th>Estado atual</th>
                <th>Workflow</th>
                <th>Autor</th>
                <th>Criado em</th>
                <th>Atualizado em</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($workflowObjects as $workflowObject)
                @php
                  $dados = $workflowData[$workflowObject->id] ?? [];
                  $status = data_get($dados, 'state', 'end');
                  $badgeColor = match ($status) {
                      'start' => 'warning',
                      'progress' => 'success',
                      default => 'secondary',
                  };
                  $workflowTitle = data_get($dados, 'workflowDefinition.definition.title', $workflowObject->workflow_definition_name ?? '-');
                  $authorName = data_get($dados, 'user.name', '-');
                  $states = collect($workflowObject->state ?? [])->keys();
                  $createdAt = $workflowObject->created_at
                      ? \Carbon\Carbon::parse($workflowObject->created_at)->format('d/m/Y H:i')
                      : '-';
                  $updatedAt = $workflowObject->updated_at
                      ? \Carbon\Carbon::parse($workflowObject->updated_at)->format('d/m/Y H:i')
                      : '-';
                @endphp
                <tr>
                  <td class="font-weight-bold">
                    <a href="{{ route('workflows.showObject', $workflowObject->id) }}">
                      #{{ $workflowObject->id }}
                    </a>
                  </td>
                  <td>
                    @forelse ($states as $state)
                      <span class="badge bg-{{ $badgeColor }} mb-1">{{ str_replace('_', ' ', $state) }}</span>
                    @empty
                      <span class="badge bg-secondary">Sem estado</span>
                    @endforelse
                  </td>
                  <td>{{ $workflowTitle }}</td>
                  <td>{{ $authorName }}</td>
                  <td>{{ $createdAt }}</td>
                  <td>{{ $updatedAt }}</td>
                  <td class="text-center">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('workflows.showObject', $workflowObject->id) }}">
                      <i class="fas fa-folder-open" aria-hidden="true"></i>
                      Abrir
                    </a>
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
