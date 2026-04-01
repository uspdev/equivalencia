<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquivalenciaRequest;
use App\Http\Requests\UpdateEquivalenciaRequest;
use App\Models\Equivalencia;
use App\Replicado\Graduacao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Uspdev\Forms\Form;

class EquivalenciaController extends Controller
{
    public function __construct()
    {
        // Adiciona o middleware para marcar a URL ativa no menu da aplicação, utilizando o pacote Uspdev/Theme.
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('equivalencias');

            return $next($request);
        });
    }

    /**
     * Lista os cursos e habilitações disponíveis para cadastro de equivalências.
     * Cada curso/habilitação é um link que leva para a página de disciplinas
     * USP equivalentes cadastradas para aquele curso/habilitação.
     */
    public function cursos()
    {
        $cursos = Graduacao::listarCursosHabilitacoes();

        return view('equivalencias.cursos', [
            'cursos' => $cursos,
        ]);
    }

    /**
     * Exibe a lista de disciplinas USP equivalentes para um curso/habilitação específico.
     * Pega o codcur e codhab da rota,
     * busca as disciplinas USP equivalentes cadastradas para esse curso/habilitação,
     * e retorna para a view. A view é responsável por exibir as disciplinas USP
     * e os formulários para criar/editar as disciplinas USP e adicionar/remover equivalências.
     */
    public function index(int $codcur, int $codhab)
    {

        $disciplinas = Equivalencia::query()
            ->usp()
            ->where('codcur', $codcur)
            ->where('codhab', $codhab)
            ->with(['equivalentes' => function ($query) {
                $query->orderBy('coddis');
            }])
            ->orderBy('coddis')
            ->paginate(15);

        return view('equivalencias.index', [
            'disciplinas' => $disciplinas,
            'codcur' => $codcur,
            'codhab' => $codhab,
            'formHtmlCreate' => $this->buildFormHtml(
                'eq_usp_create',
                route('equivalencias.store', ['codcur' => $codcur, 'codhab' => $codhab]),
                'POST',
                $this->oldInputForFields(['coddis'])
            ),
        ]);
    }

    /**
     * Armazena uma nova disciplina USP equivalente para um curso/habilitação específico.
     * Pega o codcur e codhab da rota, valida os dados do formulário utilizando a StoreEquivalenciaRequest,
     * preenche os dados da disciplina USP com as informações do Replicado
     */
    public function store(StoreEquivalenciaRequest $request, int $codcur, int $codhab)
    {
        $dados = $request->validated();
        $dados['equivalencias_id'] = null;
        $dados['tipo'] = Equivalencia::TIPO_AUTOMATICA;
        $dados['codcur'] = $codcur;
        $dados['codhab'] = $codhab;
        $dados = $this->preencherDadosDisciplinaUsp($dados);

        $equivalencia = Equivalencia::create($dados);

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab, $equivalencia])
            ->with('alert-success', 'Disciplina USP criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $codcur, int $codhab, Equivalencia $equivalencia)
    {
        abort_unless($equivalencia->isUsp(), 404);
        abort_unless($this->equivalenciaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);

        $equivalencia->load(['equivalentes' => function ($query) {
            $query->orderBy('coddis');
        }]);

        return view('equivalencias.show', [
            'disciplina' => $equivalencia,
            'equivalencias' => $equivalencia->equivalentes,
            'formHtmlEdit' => $this->buildFormHtml(
                'eq_usp_edit',
                route('equivalencias.update', [$codcur, $codhab, $equivalencia]),
                'PUT',
                $this->oldInputForFields(
                    ['coddis'],
                    ['coddis' => $equivalencia->coddis]
                )
            ),
            'formHtmlEquivalencia' => $this->buildFormHtml(
                'eq_child_add',
                route('equivalencias.add-equivalencia', [$codcur, $codhab, $equivalencia]),
                'POST',
                $this->oldInputForFields([
                    'coddis',
                    'nome_disciplina',
                    'ies',
                    'creditos',
                    'carga_horaria',
                ])
            ),
            'formHtmlEquivalenciaEdit' => $equivalencia->equivalentes
                ->mapWithKeys(function (Equivalencia $equivalenciaFilha) use ($codcur, $codhab, $equivalencia) {
                    return [
                        $equivalenciaFilha->id => $this->buildFormHtml(
                            'eq_child_add',
                            route('equivalencias.update-equivalencia', [$codcur, $codhab, $equivalencia, $equivalenciaFilha]),
                            'PUT',
                            [
                                'coddis' => $equivalenciaFilha->coddis,
                                'nome_disciplina' => $equivalenciaFilha->nome_disciplina,
                                'ies' => $equivalenciaFilha->ies,
                                'creditos' => $equivalenciaFilha->creditos,
                                'carga_horaria' => $equivalenciaFilha->carga_horaria,
                            ]
                        ),
                    ];
                })
                ->all(),
            'codcur' => $codcur,
            'codhab' => $codhab,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEquivalenciaRequest $request, int $codcur, int $codhab, Equivalencia $equivalencia)
    {
        abort_unless($equivalencia->isUsp(), 404);
        abort_unless($this->equivalenciaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);

        $dados = $request->validated();
        $dados['tipo'] = Equivalencia::TIPO_AUTOMATICA;
        $dados['codcur'] = $codcur;
        $dados['codhab'] = $codhab;

        $dados = $this->preencherDadosDisciplinaUsp($dados, $equivalencia->coddis);

        $equivalencia->update($dados);

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab, $equivalencia])
            ->with('alert-success', 'Disciplina USP atualizada com sucesso.');
    }

    /**
     * Deleta a disciplina USP, o que também deleta as equivalências
       filhas devido à relação de chave estrangeira com cascade on delete
     */
    public function destroy(int $codcur, int $codhab, Equivalencia $equivalencia)
    {
        abort_unless($equivalencia->isUsp(), 404);
        abort_unless($this->equivalenciaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);

        $equivalencia->delete();

        return redirect()
            ->route('equivalencias.curso', [$codcur, $codhab])
            ->with('alert-success', 'Disciplina USP removida com sucesso.');
    }

    /**
     * Adiciona uma nova disciplina equivalente (filha) para uma disciplina USP (pai).
     * Pega o codcur, codhab e a disciplina USP (pai) da rota
     */
    public function addEquivalencia(Request $request, int $codcur, int $codhab, Equivalencia $equivalencia)
    {
        abort_unless($equivalencia->isUsp(), 404);
        abort_unless($this->equivalenciaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);

        $request['equivalencias_id'] = $equivalencia->id;
        $request['tipo'] = Equivalencia::TIPO_CURSADA;
        $request['codcur'] = $codcur;
        $request['codhab'] = $codhab;

        Equivalencia::create($request->all());

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab, $equivalencia])
            ->with('alert-success', 'Equivalência adicionada com sucesso.');
    }

    /**
     * Atualiza uma disciplina equivalente (filha) de uma disciplina USP (pai).
     */
    public function updateEquivalencia(Request $request, int $codcur, int $codhab, Equivalencia $equivalencia, Equivalencia $equivalenciaFilha)
    {
        abort_unless($equivalencia->isUsp(), 404);
        abort_unless($this->equivalenciaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);
        abort_unless($equivalenciaFilha->isEquivalencia(), 404);
        abort_unless($equivalenciaFilha->equivalencias_id === $equivalencia->id, 404);

        $dados = $request->all();

        $dados['equivalencias_id'] = $equivalencia->id;
        $dados['tipo'] = Equivalencia::TIPO_CURSADA;
        $dados['codcur'] = $codcur;
        $dados['codhab'] = $codhab;

        $equivalenciaFilha->update($dados);

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab, $equivalencia])
            ->with('alert-success', 'Equivalência atualizada com sucesso.');
    }

    /**
     * Remove uma disciplina equivalente (filha) de uma disciplina USP (pai).
     */
    public function destroyEquivalencia(int $codcur, int $codhab, Equivalencia $equivalencia, Equivalencia $equivalenciaFilha)
    {
        abort_unless($equivalencia->isUsp(), 404);
        abort_unless($this->equivalenciaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);
        abort_unless($equivalenciaFilha->equivalencias_id === $equivalencia->id, 404);

        $equivalenciaFilha->delete();

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab, $equivalencia])
            ->with('alert-success', 'Equivalência removida com sucesso.');
    }

    /**
     * Cria o HTML do formulário utilizando o pacote Uspdev/Forms
     */
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
        $dados['codcur'] = $dados['codcur'] ?? $disciplina['codcur'] ?? null;
        $dados['codhab'] = $dados['codhab'] ?? $disciplina['codhab'] ?? null;

        return $dados;
    }
    // Verifica se a disciplina USP (equivalencia) pertence ao curso e habilitação especificados pelos códigos codcur e codhab.
    private function equivalenciaPertenceAoCurso(Equivalencia $equivalencia, int $codcur, int $codhab): bool
    {
        return (int) $equivalencia->codcur === $codcur
            && (int) $equivalencia->codhab === $codhab;
    }

    // Busca os dados da disciplina no Replicado a partir do código da disciplina (coddis).
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
