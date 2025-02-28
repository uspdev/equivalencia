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
        {{ $workflowObjectData['workflowDefinition']->definition['places'][$workflowObjectData['workflowObject']->state]['description'] }}
      </p>
      @php
        $places = $workflowObjectData['workflowDefinition']->definition['places'];
        $totalSteps = count($places);
        $currentStep = 0;
        $placeKeys = array_keys($places);

        foreach ($placeKeys as $index => $placeKey) {
            if ($workflowObjectData['workflowObject']->state == $placeKey) {
                $currentStep = $index + 1; 
                break;
            }
        }

        $progress = ($currentStep / $totalSteps) * 100;
      @endphp

      <div class="mb-4">
        <div class="progress" style="height: 25px;">
          <div class="progress-bar progress-bar-striped" role="progressbar" style="width: {{ $progress }}%;"
            aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
            {{ round($progress) }}%
          </div>
        </div>
      </div>

      @if (count($workflowObjectData['forms']) > 1)
        <div class="btn-group d-flex flex-wrap" role="group">
          @foreach ($workflowObjectData['workflowsTransitions']['all'] as $transitionName)
            <form action="{{ route('workflows.applyTransition', $workflowObjectData['workflowObject']->id) }}"
              method="POST" class="d-inline">
              @csrf
              <input type="hidden" name="transition" value="{{ $transitionName }}">
              <input type="hidden" name="workflowDefinitionName"
                value="{{ $workflowObjectData['workflowDefinition']->definition['name'] }}">

              <button type="submit"
                class="m-1 btn 
                        @if (
                            !\Illuminate\Support\Facades\Auth::user()->hasRole($workflowObjectData['workflowObject']->state) &&
                                !\Illuminate\Support\Facades\Gate::allows('admin')) btn-secondary" disabled
                        @else
                          @if (in_array($transitionName, $workflowObjectData['workflowsTransitions']['enabled'])) btn-primary" 
                          @else 
                            btn-secondary" disabled @endif @endif>
                    {{ $workflowObjectData['workflowDefinition']->definition['transitions'][$transitionName]['label'] ?? Str::replace('_', ' ', ucfirst($transitionName)) }}
                </button>
            </form>
@endforeach
      </div>
      @endif
      
      @if (
          \Illuminate\Support\Facades\Auth::user()->hasRole($workflowObjectData['workflowObject']->state) ||
              \Illuminate\Support\Facades\Gate::allows('admin'))
      {{-- @foreach ($workflowObjectData['forms'] as $form) --}}
        <div class="mb-3">
                <strong>Formulário para a transição {{ $workflowObjectData['forms'][0]['transition'] }}</strong>
                {!! $workflowObjectData['forms'][0]['html'] !!}
        </div>
        {{-- @endforeach --}}
      @endif
    </div>
  </div>
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
                    @can('admin')
                      <p><strong>ID do formulário: </strong> {{ $formSubmission->form_definition_id }}</p>
                      <p><strong>ID da submissão: </strong> {{ $formSubmission->id }}</p>
                    @endcan
                    <p><strong>Nome do usuário: </strong> {{ $userName }}</p>
                    <p><strong>Id do workflow: </strong> {{ $formSubmission->key }}</p>
                    <p><strong>Criado: </strong> {{ $formSubmission->created_at }}</p>
                    <p><strong>Estado:</strong> {{ isset($formSubmission->place) ? $formSubmission->place : '' }}
                    </p>

                    <h4>Conteúdo:</h4>
                    @foreach ($formSubmission->data as $key => $value)
                      <p><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>
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
