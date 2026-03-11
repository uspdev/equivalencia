@extends('layouts.app')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card">
        <div class="m-3">
            <h2 class="card-title pb-3">{{ $workflowObjectData['workflowDefinition']->definition['title'] }}
                @if ($workflowObjectData['workflowObject']->id != 0)
                    - ID {{ $workflowObjectData['workflowObject']->id }}
                @endif
            </h2>
            <div>
                <h4><strong>Estado Atual:</strong></h4>
                @foreach ($workflowObjectData['workflowObject']->state as $state => $one)
                    <h5>{{ $workflowObjectData['workflowDefinition']->definition['places'][$state]['description'] }}</h5>
                    <h4><strong>Ações:</strong></h4>
                    @php
                        $visibleTransitions = [];
                        foreach (
                            $workflowObjectData['workflowDefinition']->definition['transitions']
                            as $transitionName => $transitionData
                        ) {
                            if ($transitionData['from'] != $state) {
                                continue;
                            }

                            $has_role = false;
                            $need_role =
                                $workflowObjectData['workflowDefinition']->definition['places'][$state]['role'];
                            foreach ($need_role as $role_name => $role) {
                                $has_role =
                                    \Illuminate\Support\Facades\Auth::user()->hasRole($role) ||
                                    \Illuminate\Support\Facades\Gate::allows('admin');
                            }

                            if ($has_role) {
                                $visibleTransitions[$transitionName] = $transitionData;
                            }
                        }
                    @endphp
                    @foreach ($visibleTransitions as $transitionName => $transitionData)
                        <button type="submit" data-transition="{{ $transitionName }}"
                            data-url="{{ route('workflows.applyTransition', $workflowObjectData['workflowObject']->id) }}"
                            data-workflow="{{ $workflowObjectData['workflowDefinition']->definition['name'] }}"
                            class="m-1 btn transition-btn rounded btn-primary ">
                            {{ $transitionData['label'] }}
                        </button>
                        @if (!$loop->last)
                            |
                        @endif
                    @endforeach
                @endforeach
            </div>
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

            <h4 class="mb-3 pt-4">Todas as Transições (Administrador):</h4>
            <div class="d-flex flex-wrap {{ count($workflowObjectData['forms']) < 1 ? 'input-group' : 'btn-group' }}"
                role="group">
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
                        if (in_array($transitionName, $workflowObjectData['workflowsTransitions']['enabled'])) {
                            $place =
                                $workflowObjectData['workflowDefinition']->definition['transitions'][$transitionName][
                                    'from'
                                ];
                            $role = array_values(
                                $workflowObjectData['workflowDefinition']->definition['places'][$place]['role'],
                            )[0];
                            $hasPermission =
                                \Illuminate\Support\Facades\Auth::user()->hasRole($role) ||
                                \Illuminate\Support\Facades\Gate::allows('admin');
                        }
                    @endphp
                    @if (\Illuminate\Support\Facades\Gate::allows('admin'))
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
                    @endif
                @endforeach
            </div>
            @if (count($workflowObjectData['forms']) > 0)
                <div class="card mt-3" id="transition-forms-container" style="display: none;">
                    <div class="card-body">
                        <h4 class="mb-3">Formulário da Transição</h4>
                        @foreach ($workflowObjectData['forms'] as $form)
                            @php
                                $transitionLabel =
                                    $workflowObjectData['workflowDefinition']->definition['transitions'][
                                        $form['transition']
                                    ]['label'] ?? Str::replace('_', ' ', ucfirst($form['transition']));
                            @endphp
                            <div class="card mb-3 inline-transition-form d-none"
                                data-transition="{{ $form['transition'] }}">
                                <div class="card-header">
                                    Transição: <strong>{{ $transitionLabel }}</strong>
                                </div>
                                <div class="card-body">
                                    {!! $form['html'] !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @once
                @section('javascripts_bottom')
                    @parent
                    <script>
                        $(document).ready(function() {
                            $('.transition-btn').on('click', function(e) {
                                var transitionName = $(this).data('transition');
                                var transitionUrl = $(this).data('url');
                                var workflowName = $(this).data('workflow');
                                var formsContainer = $('#transition-forms-container');
                                var formWrapper = $('.inline-transition-form[data-transition="' + transitionName + '"]');

                                if (formWrapper.length > 0) {
                                    e.preventDefault();

                                    var transitionForm = formWrapper.find('form').first();
                                    if (transitionForm.length === 0) {
                                        return;
                                    }

                                    $('.inline-transition-form').addClass('d-none');
                                    formsContainer.show();
                                    formWrapper.removeClass('d-none');

                                    if (transitionForm.find('input[name="transition"]').length === 0) {
                                        transitionForm.append('<input type="hidden" name="transition" value="' +
                                            transitionName + '">');
                                    } else {
                                        transitionForm.find('input[name="transition"]').val(transitionName);
                                    }

                                    if (transitionForm.find('input[name="workflowDefinitionName"]').length === 0 &&
                                        workflowName) {
                                        transitionForm.append('<input type="hidden" name="workflowDefinitionName" value="' +
                                            workflowName + '">');
                                    }

                                    $('html, body').animate({
                                        scrollTop: formsContainer.offset().top - 20
                                    }, 200);
                                    return;
                                }

                                if (transitionUrl) {
                                    e.preventDefault();
                                    $.ajax({
                                        url: transitionUrl,
                                        type: 'POST',
                                        data: {
                                            _token: '{{ csrf_token() }}',
                                            transition: transitionName,
                                            workflowDefinitionName: workflowName
                                        },
                                        success: function(response) {
                                            location.reload();
                                        },
                                        error: function(xhr) {
                                            alert('Erro ao processar a transição: ' + xhr.responseText);
                                        }
                                    });
                                }
                            });
                        });
                    </script>
                @endsection
            @endonce

            @php
                $lead_tr[] = [];
            @endphp

            @foreach ($workflowObjectData['workflowObject']->state as $name => $one)
                @foreach ($workflowObjectData['workflowsTransitions']['all'] as $key => $tr_name)
                    @php
                        $tos = $workflowObjectData['workflowDefinition']->definition['transitions'][$tr_name]['tos'];
                        $tos = is_array($tos) ? $tos : [$tos];
                        foreach ($tos as $key => $value) {
                            if ($value == $name) {
                                $lead_tr[] = $tr_name;
                            }
                        }
                    @endphp
                @endforeach
            @endforeach

            <div class="card mt-2">
                <div class="card-body">
                    <div class="row">
                        @can('admin')
                            <div class="col-md-8">
                                <h4 class="mb-3">Submissões de Formulário</h4>
                                @if (empty($workflowObjectData['formSubmissions']) || count($workflowObjectData['formSubmissions']) == 0)
                                    <div class="card mb-2">
                                        <div class="submission-details card-body">
                                            <h4>Nenhuma submissão encontrada.</h4>
                                        </div>
                                    </div>
                                @else
                                    @foreach ($workflowObjectData['formSubmissions'] as $formSubmission)
                                        @php
                                            $userName =
                                                \App\Models\User::find($formSubmission->user_id)?->name ??
                                                'Usuário não encontrado';
                                        @endphp

                                        <div class="card mb-2">
                                            <div class="submission-details card-body">

                                                <p>
                                                    <strong>Id do workflow: </strong> {{ $formSubmission->key }} |
                                                    <strong>ID da submissão: </strong> {{ $formSubmission->id }} |
                                                    <strong>Criado: </strong> {{ $formSubmission->created_at }} |
                                                </p>

                                                <p><strong>ID do formulário: </strong>
                                                    {{ $formSubmission->form_definition_id }}</p>
                                                <p><strong>Nome do usuário: </strong> {{ $userName }}</p>

                                                <h4>Conteúdo:</h4>
                                                <p>
                                                    @foreach ($formSubmission->data as $key => $value)
                                                        @if ($key == 'arquivo')
                                                            <div class="d-flex">
                                                                <div class="card d-flex justify-content-center align-items-center mb-1"
                                                                    style="width: 132px; height: 75px; overflow: hidden; background: #000; margin-right: 10px;">
                                                                    <a href="{{ asset('storage/' . $value['stored_path']) }}"
                                                                        target="_blank" download
                                                                        style="color: white; text-decoration: none;">
                                                                        <i class="fas fa-file-alt fa-2x"></i>
                                                                        <div style="font-size: 12px;">
                                                                            {{ strtoupper(pathinfo($value['original_name'], PATHINFO_EXTENSION)) }}
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <strong>{{ ucfirst($key) }}:</strong> {{ $value }} |
                                                        @endif
                                                    @endforeach
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endcan
                        <div class="col-md-4 ms-auto">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0">Historico de Estados</span>
                                </div>
                                <div class="card-body">
                                    @forelse ($workflowObjectData['formSubmissions'] as $historyItem)
                                        @php
                                            $placeValue =
                                                $historyItem->place ??
                                                ($historyItem['place'] ??
                                                    null ??
                                                    ($historyItem->data['place'] ?? null));
                                            $stateDescriptions = collect(explode(',', (string) $placeValue))
                                                ->map(fn($state) => trim($state))
                                                ->filter()
                                                ->map(function ($stateKey) use ($workflowObjectData) {
                                                    return $workflowObjectData['workflowDefinition']->definition[
                                                        'places'
                                                    ][$stateKey]['description'] ?? $stateKey;
                                                });
                                        @endphp
                                        <div class="border rounded px-3 py-2 mb-2 bg-light">
                                            <div class="d-flex flex-wrap gap-2 mb-1">
                                                @forelse ($stateDescriptions as $stateDescription)
                                                    <span class="badge text-bg-light border">{{ $stateDescription }}</span>
                                                @empty
                                                    <span class="text-muted">Estado nao informado</span>
                                                @endforelse
                                            </div>
                                            @if (!empty($historyItem->created_at ?? ($historyItem['created_at'] ?? null)))
                                                <div class="small text-muted">
                                                    {{ $historyItem->created_at ?? ($historyItem['created_at'] ?? null) }}
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-muted">
                                            Nenhum estado anterior encontrado para esta requisição.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endsection
