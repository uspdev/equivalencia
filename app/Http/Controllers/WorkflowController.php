<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Uspdev\Workflow\Workflow;
use Uspdev\Forms\Form;

class WorkflowController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function createDefinition()
    {
        return view('createDefinition');
    }

    public function storeDefinition(Request $request)
    {
        Workflow::criarWorkflowDefinition($request);

        return redirect()->route('workflows.list-definitions')->with('success', 'Definition criada com sucesso.');
    }

    public function listDefinitions()
    {
        $workflowDefinitions = Workflow::obterTodosWorkflowDefinitions();

        return view('list', compact('workflowDefinitions'));
    }

    public function showDefinition($definitionName)
    {
        $workflowDefinitionData = Workflow::obterDadosDaDefinicao($definitionName);
        
        return view('showDefinition', compact('workflowDefinitionData'));
    }

    public function setUser(Request $request)
    {
        Workflow::definirUsuarios($request);

        return back();
    }

    public function destroyDefinition($definitionName)
    {
        Workflow::deletarDefinicaodeWorkflow($definitionName);

        return redirect()->route('workflows.list-definitions')->with('success', 'Definition apagada com sucesso.');
    }

    public function editDefinition($definitionName)
    {
        $workflow = Workflow::obterWorkflowDefinition($definitionName);
    
        return view('edit', compact('workflow'));
    }

    public function updateDefinition(Request $request)
    {
        Workflow::atualizarWorkflow($request);

        return redirect()->route('workflows.showDefinition', ['definition' => $request->name]);
    }

    public function viewCreateObject()
    {
        $workflowDefinitions = Workflow::obterTodosWorkflowDefinitions();
        \UspTheme::activeUrl('viewcreateobject');

        return view('createObject', compact('workflowDefinitions'));
    }

    public function createObject($definitionName)
    {
        $workflowObjectData = Workflow::criarWorkflowObject($definitionName);

        return view('showObject', compact('workflowObjectData'));
    }

    public function showUserObjects()
    {
        \UspTheme::activeUrl('showuserobjects');

        $userCodpes = auth()->user()->codpes;

        $workflowsDisplay = Workflow::listarWorkflowsdoUser($userCodpes);

        return view('userObjects', compact('workflowsDisplay'));
    }

    public function showForm($id, $transition)
    {
        $workflowObjectData = Workflow::obterDadosDoObjeto($id);

        $selectedForm = collect($workflowObjectData['forms'])->firstWhere('transition', $transition);

        if (!$selectedForm) {
            return redirect()->back()->with('error', 'Nenhum formulário encontrado para essa transição.');
        }

        return view('form', compact('workflowObjectData', 'selectedForm', 'transition'));
    }


    public function showObject($id)
    {
        $workflowObjectData = Workflow::obterDadosDoObjeto($id);
        $workflowObjectData['userGuidance'] = $this->buildUserGuidance($workflowObjectData);
        $workflowObjectData['userStateHistory'] = $this->buildUserStateHistory($workflowObjectData);

        return view('showObject', compact('workflowObjectData'));
    }

    // Método para construir as orientações ao usuário com base
    // nos estados atuais e transições disponíveis
    // Ele analisa os estados atuais do objeto, verifica as transições disponíveis para o usuário
    // e retorna uma estrutura de dados que pode ser usada na view para exibir mensagens e ações relevantes
    private function buildUserGuidance(array $workflowObjectData): array
    {
        $places = $workflowObjectData['workflowDefinition']->definition['places'] ?? [];
        // Obter as chaves dos estados atuais do objeto e mapear para suas descrições
        $currentStateKeys = array_keys($workflowObjectData['workflowObject']->state ?? []);
        $currentStateDescriptions = collect($currentStateKeys)
            ->map(function ($stateKey) use ($places) {
                return $places[$stateKey]['description'] ?? $stateKey;
            })
            ->values()
            ->all();

        $availableTransitions = $this->getVisibleTransitionsForCurrentUser($workflowObjectData);
        if (!empty($availableTransitions)) {
            return [
                'variant' => 'warning',
                'title' => 'Ação necessária',
                'message' => 'Existe uma ação pendente para você nesta solicitação.',
                'currentStates' => $currentStateDescriptions,
                'availableActions' => array_values($availableTransitions),
            ];
        }

        $combinedStateText = Str::lower(implode(' ', $currentStateDescriptions));

        if (Str::contains($combinedStateText, ['analise', 'análise', 'conferencia', 'conferência', 'deliberar'])) {
            return [
                'variant' => 'info',
                'title' => 'Em análise',
                'message' => 'Seu formulário está em análise. Nenhuma ação necessária no momento.',
                'currentStates' => $currentStateDescriptions,
                'availableActions' => [],
            ];
        }

        if (Str::contains($combinedStateText, ['concluido', 'concluído', 'finalização', 'deferido', 'indeferido'])) {
            return [
                'variant' => 'success',
                'title' => 'Processo concluído',
                'message' => 'A solicitação foi finalizada. Nenhuma ação necessária no momento.',
                'currentStates' => $currentStateDescriptions,
                'availableActions' => [],
            ];
        }

        return [
            'variant' => 'info',
            'title' => 'Acompanhamento da solicitação',
            'message' => 'Nenhuma ação necessária no momento.',
            'currentStates' => $currentStateDescriptions,
            'availableActions' => [],
        ];
    }
    // Método para obter as transições visíveis para o usuário com base nos estados atuais do objeto e nas regras de acesso
    private function getVisibleTransitionsForCurrentUser(array $workflowObjectData): array
    {
        $visibleTransitions = [];
        $transitions = $workflowObjectData['workflowDefinition']->definition['transitions'] ?? [];
        $places = $workflowObjectData['workflowDefinition']->definition['places'] ?? [];
        $currentStateKeys = array_keys($workflowObjectData['workflowObject']->state ?? []);
        $enabledTransitions = $workflowObjectData['workflowsTransitions']['enabled'] ?? [];
        $user = auth()->user();
    
        foreach ($currentStateKeys as $stateKey) {
            //  Verificar cada transição para ver se ela é aplicável ao estado atual e se o usuário tem permissão para executá-la
            foreach ($transitions as $transitionName => $transitionData) {
                if (($transitionData['from'] ?? null) !== $stateKey) {
                    continue;
                }

                if (!in_array($transitionName, $enabledTransitions, true)) {
                    continue;
                }
                // Verificar se o usuário tem pelo menos um dos papéis necessários para executar a transição
                $needRoles = array_values($places[$stateKey]['role'] ?? []);
                $hasRole = false;
                foreach ($needRoles as $role) {
                    if (($user && $user->hasRole($role)) || Gate::allows('admin')) {
                        $hasRole = true;
                        break;
                    }
                }
                // Se o usuário tiver permissão, adicionar a transição à lista de transições visíveis
                if ($hasRole) {
                    $visibleTransitions[$transitionName] =
                        $transitionData['label'] ?? Str::replace('_', ' ', ucfirst($transitionName));
                }
            }
        }

        return $visibleTransitions;
    }

    // Método para construir o histórico de estados do usuário com base nas submissões de formulários relacionadas ao objeto
    private function buildUserStateHistory(array $workflowObjectData): array
    {
        $transitions = $workflowObjectData['workflowDefinition']->definition['transitions'] ?? [];
        $places = $workflowObjectData['workflowDefinition']->definition['places'] ?? [];
        
        // Percorrer as submissões de formulários, ordenando por data de criação, e construir uma descrição legível 
        // do histórico de estados e transições para o usuário
        return collect($workflowObjectData['formSubmissions'] ?? [])
            ->sortByDesc('created_at')
            ->map(function ($submission) use ($transitions, $places) {
                $data = $submission->data ?? [];
                $placeValue = $submission->place ?? ($data['place'] ?? null);
                $transitionName = $data['transition'] ?? null;
                // Tratar o valor do estado para criar uma descrição legível
                $stateDescriptions = collect(explode(',', (string) $placeValue))
                    ->map(function ($state) {
                        return trim($state);
                    })
                    ->filter()
                    ->map(function ($stateKey) use ($places) {
                        return $places[$stateKey]['description'] ?? $stateKey;
                    })
                    ->values()
                    ->all();

                $transitionLabel = $transitionName
                    ? ($transitions[$transitionName]['label'] ?? Str::replace('_', ' ', ucfirst($transitionName)))
                    : null;
      
                $reason = $data['retorno'] ?? null;
                $isReturn = $transitionLabel
                    ? Str::contains(Str::lower($transitionLabel), ['devolv', 'retorn'])
                    : false;

                if ($isReturn && $reason) {
                    $detail = 'Formulário retornado ao usuário. Motivo: ' . $reason;
                } elseif ($isReturn) {
                    $detail = 'Formulário retornado ao usuário para correção.';
                } elseif ($reason) {
                    $detail = $reason;
                } elseif (!empty($stateDescriptions)) {
                    $detail = 'Estado atualizado para: ' . implode(', ', $stateDescriptions) . '.';
                } else {
                    $detail = 'Movimentação registrada no fluxo.';
                }

                $title = $transitionLabel
                    ? $transitionLabel
                    : (!empty($stateDescriptions) ? implode(', ', $stateDescriptions) : 'Atualização de solicitação');

                return [
                    'title' => $title,
                    'detail' => $detail,
                    'created_at' => $submission->created_at,
                ];
            })
            ->filter(function ($entry) {
                return !empty($entry['title']);
            })
            ->values()
            ->all();
    }


    public function deleteObject($workflowObjectId)
    {
        Workflow::deletarWorkflow($workflowObjectId);

        return self::showUserObjects();
    }

    public function applyTransition(Request $request, $id)
    {
        $workflowObjectId = Workflow::aplicarTransition($id, $request->input('transition'), $request->input('workflowDefinitionName'));        

        if($workflowObjectId == 0)
        {
            return redirect()->route('workflows.createObject', ['definitionName' => $request->input('workflowDefinitionName')]);
        }
        return redirect()->route('workflows.showObject', ['id' => $workflowObjectId]);
    }

    public function submitForm(Request $request) 
    {
        $request->merge(['id' => null]);
        $workflowObjectId = Workflow::enviarFormulario($request);
        return redirect()->route('workflows.showObject', ['id' => $workflowObjectId]);
    }

    public function atendimentos()
    {
        \UspTheme::activeUrl('atendimentos');

        $workflowsDisplay = Workflow::listarWorkflowsObjectsRelacionados();
        return view('userRelatedObjects', compact('workflowsDisplay'));

    }
    
}
