<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquivalenciaFilhaRequest;
use App\Http\Requests\StoreEquivalenciaRequest;
use App\Http\Requests\UpdateEquivalenciaRequest;
use App\Models\Equivalencia;
use Uspdev\Forms\Form;

class EquivalenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $disciplinas = Equivalencia::query()
            ->usp()
            ->withCount('equivalentes')
            ->orderBy('coddis')
            ->paginate(15);

        return view('equivalencias.index', [
            'disciplinas' => $disciplinas,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $campos = ['coddis', 'nome_disciplina', 'verdis', 'codcur', 'codhab'];

        return view('equivalencias.create', [
            'formHtml' => $this->buildFormHtml(
                'eq_usp_create',
                route('equivalencias.store'),
                'POST',
                $this->oldInputForFields($campos)
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEquivalenciaRequest $request)
    {
        $dados = $request->validated();
        $dados['equivalencias_id'] = null;

        $equivalencia = Equivalencia::create($dados);

        return redirect()
            ->route('equivalencias.show', $equivalencia)
            ->with('success', 'Disciplina USP criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Equivalencia $equivalencia)
    {
        abort_if($equivalencia->isEquivalencia(), 404);

        $equivalencia->load(['equivalentes' => function ($query) {
            $query->orderBy('coddis');
        }]);

        return view('equivalencias.show', [
            'disciplina' => $equivalencia,
            'equivalencias' => $equivalencia->equivalentes,
            'formHtmlEquivalencia' => $this->buildFormHtml(
                'eq_child_add',
                route('equivalencias.add-equivalencia', $equivalencia),
                'POST',
                $this->oldInputForFields([
                    'coddis',
                    'nome_disciplina',
                    'ies',
                    'ano',
                    'semestre',
                    'nota',
                    'frequencia',
                    'tipo',
                ])
            ),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Equivalencia $equivalencia)
    {
        abort_if($equivalencia->isEquivalencia(), 404);

        $dadosPadrao = [
            'coddis' => $equivalencia->coddis,
            'nome_disciplina' => $equivalencia->nome_disciplina,
            'verdis' => $equivalencia->verdis,
            'codcur' => $equivalencia->codcur,
            'codhab' => $equivalencia->codhab,
        ];

        return view('equivalencias.edit', [
            'disciplina' => $equivalencia,
            'formHtml' => $this->buildFormHtml(
                'eq_usp_edit',
                route('equivalencias.update', $equivalencia),
                'PUT',
                $this->oldInputForFields(array_keys($dadosPadrao), $dadosPadrao)
            ),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEquivalenciaRequest $request, Equivalencia $equivalencia)
    {
        abort_if($equivalencia->isEquivalencia(), 404);

        $dados = $request->validated();
        unset($dados['equivalencias_id']);

        $equivalencia->update($dados);

        return redirect()
            ->route('equivalencias.show', $equivalencia)
            ->with('success', 'Disciplina USP atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Equivalencia $equivalencia)
    {
        abort_if($equivalencia->isEquivalencia(), 404);

        $equivalencia->delete();

        return redirect()
            ->route('equivalencias.index')
            ->with('success', 'Disciplina USP removida com sucesso.');
    }

    public function addEquivalencia(StoreEquivalenciaFilhaRequest $request, Equivalencia $equivalencia)
    {
        abort_if($equivalencia->isEquivalencia(), 404);

        $dados = $request->validated();
        $dados['equivalencias_id'] = $equivalencia->id;

        Equivalencia::create($dados);

        return redirect()
            ->route('equivalencias.show', $equivalencia)
            ->with('success', 'Equivalência adicionada com sucesso.');
    }

    public function destroyEquivalencia(Equivalencia $equivalencia, Equivalencia $equivalenciaFilha)
    {
        abort_if($equivalencia->isEquivalencia(), 404);
        abort_unless($equivalenciaFilha->equivalencias_id === $equivalencia->id, 404);

        $equivalenciaFilha->delete();

        return redirect()
            ->route('equivalencias.show', $equivalencia)
            ->with('success', 'Equivalência removida com sucesso.');
    }

    private function buildFormHtml(string $name, string $action, string $method, array $values): string
    {
        $form = new Form([
            'name' => $name,
            'action' => $action,
            'method' => $method,
        ]);

        $formSubmission = in_array(strtoupper($method), ['PUT', 'PATCH'], true)
            ? (object) ['data' => $values]
            : null;

        return $form->generateHtml($name, $formSubmission) ?? '';
    }

    private function oldInputForFields(array $fields, array $defaults = []): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field] = old($field, $defaults[$field] ?? null);
        }

        return $values;
    }
}
