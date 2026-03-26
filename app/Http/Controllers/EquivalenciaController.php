<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquivalenciaFilhaRequest;
use App\Http\Requests\StoreEquivalenciaRequest;
use App\Http\Requests\UpdateEquivalenciaRequest;
use App\Models\Equivalencia;
use Uspdev\Forms\Form;
use Uspdev\Replicado\Graduacao;

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
        $campos = ['coddis'];

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
        $dados['tipo'] = Equivalencia::TIPO_REQUERIDA;
        $dados = $this->preencherDadosDisciplinaUsp($dados);

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
        abort_unless($equivalencia->isUsp(), 404);

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
                    'creditos',
                    'carga_horaria',
                ])
            ),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Equivalencia $equivalencia)
    {
        abort_unless($equivalencia->isUsp(), 404);

        $dadosPadrao = [
            'coddis' => $equivalencia->coddis,
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
        abort_unless($equivalencia->isUsp(), 404);

        $dados = $request->validated();
        $dados['tipo'] = Equivalencia::TIPO_REQUERIDA;

        $dados = $this->preencherDadosDisciplinaUsp($dados, $equivalencia->coddis);

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
        abort_unless($equivalencia->isUsp(), 404);

        $equivalencia->delete();

        return redirect()
            ->route('equivalencias.index')
            ->with('success', 'Disciplina USP removida com sucesso.');
    }

    public function addEquivalencia(StoreEquivalenciaFilhaRequest $request, Equivalencia $equivalencia)
    {
        abort_unless($equivalencia->isUsp(), 404);

        $dados = $request->validated();
        $dados['equivalencias_id'] = $equivalencia->id;
        $dados['tipo'] = Equivalencia::TIPO_CURSADA;

        Equivalencia::create($dados);

        return redirect()
            ->route('equivalencias.show', $equivalencia)
            ->with('success', 'Equivalência adicionada com sucesso.');
    }

    public function destroyEquivalencia(Equivalencia $equivalencia, Equivalencia $equivalenciaFilha)
    {
        abort_unless($equivalencia->isUsp(), 404);
        abort_unless($equivalenciaFilha->equivalencias_id === $equivalencia->id, 404);

        $equivalenciaFilha->delete();

        return redirect()
            ->route('equivalencias.show', $equivalencia)
            ->with('success', 'Equivalência removida com sucesso.');
    }

    // Cria um formulário HTML para as views, utilizando a classe Form do pacote Uspdev/Forms.
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

    // Recupera os valores antigos (old input) para os campos do formulário,
    // utilizando os valores padrão fornecidos caso não haja old input.
    private function oldInputForFields(array $fields, array $defaults = []): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field] = old($field, $defaults[$field] ?? null);
        }

        return $values;
    }

    // A partir do código da disciplina (coddis), busca os dados da disciplina no Replicado e preenche os campos correspondentes.
    private function preencherDadosDisciplinaUsp(array $dados, ?string $coddisAtual = null): array
    {
        $coddis = $dados['coddis'] ?? $coddisAtual;

        $disciplina = $this->buscarDisciplinaNoReplicado($coddis);

        if (! $disciplina) {
            return $dados;
        }

        // Preenche os campos da disciplina USP com os dados do Replicado, caso estejam disponíveis.
        $dados['nome_disciplina'] = $disciplina['nomdis'] ?? $dados['nome_disciplina'] ?? null;
        $dados['verdis'] = $disciplina['verdis'] ?? $dados['verdis'] ?? null;
        $dados['creditos'] = $disciplina['creaul'] ?? $dados['creditos'] ?? null;
        $dados['carga_horaria'] = $disciplina['numhor'] ?? $dados['carga_horaria'] ?? null;
        $dados['nomcur'] = $disciplina['nomcur'] ?? $dados['nomcur'] ?? null;
        $dados['codcur'] = $disciplina['codcur'] ?? $dados['codcur'] ?? null;
        $dados['codhab'] = $disciplina['codhab'] ?? $dados['codhab'] ?? null;

        return $dados;
    }

    private function buscarDisciplinaNoReplicado(?string $coddis): ?array
    {
        if (! $coddis) {
            return null;
        }

        
        try {
            $disciplinas = Graduacao::obterDisciplinas([$coddis]) ?? [];
        } catch (\Throwable $e) {
            return null;
        }
      

        foreach ($disciplinas as $disciplina) {
            if (($disciplina['coddis'] ?? null) === $coddis) {
                return $disciplina;
            }
        }

        return $disciplinas[0] ?? null;
    }
}
