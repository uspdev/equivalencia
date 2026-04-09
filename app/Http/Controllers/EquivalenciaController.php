<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquivalenciaRequest;
use App\Http\Requests\UpdateEquivalenciaRequest;
use App\Models\Disciplina;
use App\Models\Equivalencia;
use App\Replicado\Graduacao;
use Illuminate\Http\Request;
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
    public function index()
    {
        $cursos = Graduacao::listarCursosHabilitacoes();

        return view('equivalencias.index', [
            'cursos' => $cursos,
        ]);
    }

    public function show(int $codcur, int $codhab)
    {
        $curso = collect(Graduacao::listarCursosHabilitacoes())
            ->first(fn ($item) => (int) $item['codcur'] === $codcur && (int) $item['codhab'] === $codhab);

        abort_unless($curso, 404);

        $disciplinas = Disciplina::query()
            ->whereHas('equivalenciasComoRequerida', function ($query) use ($codcur, $codhab) {
                $query->automaticas()->doContexto($codcur, $codhab);
            })
            ->with(['equivalentes' => function ($query) use ($codcur, $codhab) {
                $query->automaticas()->doContexto($codcur, $codhab)->with('cursada')->orderBy('id');
            }])
            ->orderBy('coddis')
            ->paginate(15);

        $disciplinas->getCollection()->transform(function (Disciplina $disciplina) {
            $disciplina->setRelation(
                'equivalentes',
                $disciplina->equivalentes->sortBy('coddis')->values()
            );

            return $disciplina;
        });

        $formHtml = $this->buildFormHtml(
            'eq_usp_create',
            route('equivalencias.store', ['codcur' => $codcur, 'codhab' => $codhab]),
            'POST',
            $this->oldInputForFields(['coddis'])
        );

        $formHtmlEdit = $disciplinas->getCollection()
            ->mapWithKeys(function (Disciplina $disciplinaUsp) use ($codcur, $codhab) {
                $formHtml = $this->buildFormHtml(
                    'eq_usp_edit',
                    route('equivalencias.update', [$codcur, $codhab, $disciplinaUsp]),
                    'PUT',
                    $this->oldInputForFields(
                        ['coddis'],
                        ['coddis' => $disciplinaUsp->coddis]
                    )
                );

                return [
                    $disciplinaUsp->id => $this->namespaceFormHtmlForIndex($formHtml, $disciplinaUsp->id),
                ];
            })
            ->all();

        $formHtmlEquivalencia = $disciplinas->getCollection()
            ->mapWithKeys(function (Disciplina $disciplinaUsp) use ($codcur, $codhab) {
                return [
                    $disciplinaUsp->id => $this->buildFormHtml(
                        'eq_child_add',
                        route('equivalencias.add-equivalencia', [$codcur, $codhab, $disciplinaUsp]),
                        'POST',
                        $this->oldInputForFields(['coddis', 'nome_disciplina', 'ies'])
                    ),
                ];
            })
            ->all();

        $formHtmlEquivalenciaEdit = $disciplinas->getCollection()
            ->reduce(function (array $forms, Disciplina $disciplinaUsp) use ($codcur, $codhab) {
                $formsDaDisciplina = $disciplinaUsp->equivalentes
                    ->mapWithKeys(function (Equivalencia $equivalenciaFilha) use ($codcur, $codhab, $disciplinaUsp) {
                        return [
                            $equivalenciaFilha->id => $this->buildFormHtml(
                                'eq_child_add',
                                route('equivalencias.update-equivalencia', [$codcur, $codhab, $disciplinaUsp, $equivalenciaFilha]),
                                'PUT',
                                [
                                    'coddis' => old('coddis', $equivalenciaFilha->coddis),
                                    'nome_disciplina' => old('nome_disciplina', $equivalenciaFilha->nome_disciplina),
                                    'ies' => old('ies', $equivalenciaFilha->ies),
                                ]
                            ),
                        ];
                    })
                    ->all();

                return $forms + $formsDaDisciplina;
            }, []);

        return view('equivalencias.show', [
            'disciplinas' => $disciplinas,
            'codcur' => $codcur,
            'codhab' => $codhab,
            'nomeCurso' => $curso['nomcur'],
            'formHtmlCreate' => $formHtml,
            'formHtmlEdit' => $formHtmlEdit,
            'formHtmlEquivalencia' => $formHtmlEquivalencia,
            'formHtmlEquivalenciaEdit' => $formHtmlEquivalenciaEdit,
        ]);
    }

    public function store(StoreEquivalenciaRequest $request, int $codcur, int $codhab)
    {
        $dados = $request->validated();

        $requerida = Disciplina::query()
            ->where('coddis', $dados['coddis'])
            ->where('ies', 'USP')
            ->first();

        $requerida = Disciplina::upsertRequeridaPorCoddis($dados['coddis'], $requerida);

        if (! Equivalencia::grupoDaRequerida($requerida->id, $codcur, $codhab)) {
            Equivalencia::criarPlaceholderDaRequerida($requerida->id, $codcur, $codhab);
        }

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab])
            ->with('alert-success', 'Disciplina USP criada com sucesso.');
    }

    public function update(UpdateEquivalenciaRequest $request, int $codcur, int $codhab, Disciplina $equivalencia)
    {
        abort_unless($this->requeridaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);

        $dados = $request->validated();
        Disciplina::upsertRequeridaPorCoddis($dados['coddis'], $equivalencia);

        return redirect()
            ->back()
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
            ->route('equivalencias.show', [$codcur, $codhab])
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
            ->back()
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
            ->back()
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
            ->back()
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

    // Em telas com lista de modais (index), evita IDs e seletores duplicados para o Select2.
    private function namespaceFormHtmlForIndex(string $formHtml, int $disciplinaId): string
    {
        $suffix = (string) $disciplinaId;

        return str_replace(
            [
                'id="generatedForm"',
                'id="uspdev-forms-coddis"',
                'for="uspdev-forms-coddis"',
                "selector: '#uspdev-forms-coddis'",
                'id="uspdev-forms-disciplina-usp"',
            ],
            [
                'id="generatedForm-'.$suffix.'"',
                'id="uspdev-forms-coddis-'.$suffix.'"',
                'for="uspdev-forms-coddis-'.$suffix.'"',
                "selector: '#uspdev-forms-coddis-".$suffix."'",
                'id="uspdev-forms-disciplina-usp-'.$suffix.'"',
            ],
            $formHtml
        );
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
