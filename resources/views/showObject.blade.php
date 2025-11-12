@extends('layouts.app')

@section('content')
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  <div class="card">
    <div class="m-3">
      <h3>{{ $workflowObjectData['workflowDefinition']->definition['title'] }}
        @if ($workflowObjectData['workflowObject']->id != 0)
          - ID {{ $workflowObjectData['workflowObject']->id }}
        @endif
      </h3>
      <p><strong>Estado Atual:</strong>
        @foreach ($workflowObjectData['workflowObject']->state as $state => $one)
          {{ $workflowObjectData['workflowDefinition']->definition['places'][$state]['description'] }} | &rarr; <strong>Próximos Estados:</strong>
          @foreach ($workflowObjectData['workflowDefinition']->definition['transitions'] as $transition)
            @if ($transition['from'] == $state)
              {{ $transition['label'] }} |
            @endif
          @endforeach
        @endforeach
        </p>
      @php
        $places = $workflowObjectData['workflowDefinition']->definition['places'];
        $totalSteps = count($places);
        $currentStep = 0;
        $placeKeys = array_keys($places);

        foreach ($placeKeys as $index => $placeKey) {
            if (in_array($placeKey, $workflowObjectData['workflowObject']->state)) {
                $currentStep = $index + 1;
                break;
            }
        }

        $progress = ($currentStep / $totalSteps) * 100;

        $enabledTransitions = $workflowObjectData['workflowsTransitions']['enabled'] ?? [];
        $hasMultipleTransitions = count($enabledTransitions) > 1;
      @endphp

      {{-- <div class="mb-4">
        <div class="progress" style="height: 25px;">
          <div class="progress-bar progress-bar-striped" role="progressbar" style="width: {{ $progress }}%;"
            aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
            {{ round($progress) }}%
          </div>
        </div>
      </div> --}}

      <div class="d-flex flex-wrap
      @if (count($workflowObjectData['forms']) < 1)
          input-group" role="group">
      @else
          btn-group" role="group">
      @endif
        @foreach ($workflowObjectData['workflowsTransitions']['all'] as $transitionName)
          @if (count($workflowObjectData['forms']) < 1)
            <form action="{{ route('workflows.applyTransition', $workflowObjectData['workflowObject']->id) }}"
              method="POST" class="d-inline d-flex">
              @csrf
              <input type="hidden" name="transition" value="{{ $transitionName }}">
              <input type="hidden" name="workflowDefinitionName"
                value="{{ $workflowObjectData['workflowDefinition']->definition['name'] }}">
          @endif
          @php
            $hasForm = collect($workflowObjectData['forms'])->firstWhere('transition', $transitionName);
            $hasPermission = false;
            if(in_array($transitionName, $workflowObjectData['workflowsTransitions']['enabled'])){
              $place = $workflowObjectData['workflowDefinition']->definition['transitions'][$transitionName]['from'];
              $role = array_values($workflowObjectData['workflowDefinition']->definition['places'][$place]['role'])[0];
              $hasPermission = \Illuminate\Support\Facades\Auth::user()->hasRole($role) || \Illuminate\Support\Facades\Gate::allows('admin');
            }
          @endphp
          <button type="submit" data-transition="{{ $transitionName }}"
            @if (!$hasForm) data-url="{{ route('workflows.applyTransition', $workflowObjectData['workflowObject']->id) }}" 
                            data-workflow="{{ $workflowObjectData['workflowDefinition']->definition['name'] }}" @endif
            class="m-1 btn transition-btn rounded
            @if (!$hasPermission) btn-secondary" disabled
            @else
                @if (in_array($transitionName, $workflowObjectData['workflowsTransitions']['enabled'])) btn-primary"
                @else btn-secondary" disabled @endif 
            @endif">
            {{ $workflowObjectData['workflowDefinition']->definition['transitions'][$transitionName]['label'] ?? Str::replace('_', ' ', ucfirst($transitionName)) }}
          </button>

          @if (count($workflowObjectData['forms']) < 1)
            </form>
          @endif
        @endforeach
      </div>

      @if (
          \Illuminate\Support\Facades\Auth::user()->hasRole($workflowObjectData['workflowObject']->state) ||
              \Illuminate\Support\Facades\Gate::allows('admin'))
        @if (!$hasMultipleTransitions && isset($workflowObjectData['forms'][0]))
          <div class="mt-3">
            <strong>Formulário para a transição {{ $workflowObjectData['forms'][0]['transition'] }}</strong>
            {!! $workflowObjectData['forms'][0]['html'] !!}
          </div>
        @endif
      @endif
    </div>
  </div>
  @if (count($workflowObjectData['forms']) > 0) @include('partials.transition-modal') @endif
  
  @if (
      \Illuminate\Support\Facades\Auth::user()->hasRole($workflowObjectData['workflowObject']->state) ||
          \Illuminate\Support\Facades\Gate::allows('admin'))
    <div class="card mt-2">
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            @if (empty($workflowObjectData['formSubmissions']) || count($workflowObjectData['formSubmissions']) == 0)
              <div class="card mb-2">
                <div class="submission-details card-body">
                  <h4>Nenhuma submissão encontrada.</h4>
                </div>
              </div>
            @else
              @foreach ($workflowObjectData['formSubmissions'] as $formSubmission)
                @php
                  $userName = \App\Models\User::find($formSubmission->user_id)?->name ?? 'Usuário não encontrado';
                @endphp

                <div class="card mb-2">
                  <div class="submission-details card-body">
                    <h4>Submissão</h4>
                    <p>
                      <strong>Id do workflow: </strong> {{ $formSubmission->key }}
                      @can('admin')
                      |
                      <strong>ID do formulário: </strong> {{ $formSubmission->form_definition_id }} |
                      <strong>ID da submissão: </strong> {{ $formSubmission->id }} |
                      <strong>Criado: </strong> {{ $formSubmission->created_at }} 
                      @endcan
                    </p>
                    <p><strong>Nome do usuário: </strong> {{ $userName }}</p>
                    <p><strong>Estado:</strong> {{ isset($formSubmission->place) ? $formSubmission->place : '' }}
                    </p>

                    <h4>Conteúdo:</h4>
                    @foreach ($formSubmission->data as $key => $value)
                      @if ($key == 'arquivo')
                        <div class="d-flex">
                          <div class="card d-flex justify-content-center align-items-center mb-1"
                            style="width: 132px; height: 75px; overflow: hidden; background: #000; margin-right: 10px;">
                            <a href="{{ asset('storage/' . $value['stored_path']) }}" target="_blank" download style="color: white; text-decoration: none;">
                                <i class="fas fa-file-alt fa-2x"></i>
                                <div style="font-size: 12px;">
                                    {{ strtoupper(pathinfo($value['original_name'], PATHINFO_EXTENSION)) }}
                                </div>
                            </a>
                          </div>
                        </div>
                      @else
                        <p><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>
                      @endif                    
                    @endforeach
                  </div>
                </div>
              @endforeach
            @endif
          </div>
          <div class="col-md-4">
            <div class="card">
              <div class="card-header h5">Registro de Atividades</div>
              <div class="card-body">
                @foreach ($workflowObjectData['activities'] as $activity)
                  <p>
                    {{ $activity['created_at'] }} |
                    {{ $activity['user'] }} -
                    {{ $activity['description'] }}
                  </p>
                @endforeach
              </div>
            @else
  @endif

  </div>
  </div>
  </div>
  </div>
  </div>
@endsection
