<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Uspdev\Workflow\Workflow;
use Uspdev\Forms\Form;

class WorkflowController extends Controller
{
    public function home()
    {
        return redirect()->route('workflows.show-user-objects');
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

        return view('createObject', compact('workflowDefinitions'));
    }

    public function createObject($definitionName)
    {
        $workflowObjectData = Workflow::criarWorkflowObject($definitionName);

        return view('showObject', compact('workflowObjectData'));
    }

    public function showUserObjects()
    {
        $userCodpes = auth()->user()->codpes;

        $workflowsDisplay = Workflow::listarWorkflowsdoUser($userCodpes);

        return view('userObjects', compact('workflowsDisplay'));
    }

    public function showObject($id)
    {
        $workflowObjectData = Workflow::obterDadosDoObjeto($id);

        return view('showObject', compact('workflowObjectData'));
    }

    public function deleteObject($workflowObjectId)
    {
        Workflow::deletarWorkflow($workflowObjectId);

        return SELF::showUserObjects();
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
        $workflowObjectId = Workflow::enviarFormulario($request);
        return redirect()->route('workflows.showObject', ['id' => $workflowObjectId]);
    }

    public function atendimentos()
    {
        $workflowsDisplay = Workflow::listarWorkflowsObjectsRelacionados();
        return view('userRelatedObjects', compact('workflowsDisplay'));

    }
    
}
