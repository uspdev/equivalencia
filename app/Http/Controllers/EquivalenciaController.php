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

    public function destroy(int $codcur, int $codhab, Disciplina $equivalencia)
    {
        abort_unless($this->requeridaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);

        $vinculos = Equivalencia::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $equivalencia->id)
            ->get();
        // Vinculos que não são placeholders (ou seja, equivalências reais)
        //devem ter suas disciplinas cursadas verificadas para possível
        // remoção caso fiquem órfãs após a exclusão dos vínculos.
        $cursadasParaLimpeza = $vinculos
            ->filter(fn (Equivalencia $item) => ! $item->isPlaceholderRequerida())
            ->pluck('cursada_id')
            ->unique()
            ->values();

        Equivalencia::query()
            ->whereIn('id', $vinculos->pluck('id'))
            ->delete();

        foreach ($cursadasParaLimpeza as $cursadaId) {
            $this->removerDisciplinaSeOrfa((int) $cursadaId);
        }

        $this->removerDisciplinaSeOrfa($equivalencia->id);

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

    // Prepara os valores padrão para o formulário de edição de uma equivalência,
    // considerando os outros vínculos do mesmo grupo para preencher os campos adicionais.
    private function defaultsParaFormularioEdicaoDeGrupo(Disciplina $disciplinaUsp, Equivalencia $equivalenciaFilha): array
    {
        $equivalentesDoMesmoGrupo = $disciplinaUsp->equivalentes
            ->where('grupo', $equivalenciaFilha->grupo)
            ->sortBy('id')
            ->values();

        $outrosDoGrupo = $equivalentesDoMesmoGrupo
            ->reject(fn (Equivalencia $item) => $item->id === $equivalenciaFilha->id)
            ->values();

        $equivalencia2 = $outrosDoGrupo->get(0);
        $equivalencia3 = $outrosDoGrupo->get(1);

        return [
            'coddis' => old('coddis', $equivalenciaFilha->coddis),
            'nome_disciplina' => old('nome_disciplina', $equivalenciaFilha->nome_disciplina),
            'ies' => old('ies', $equivalenciaFilha->ies),
            'coddis2' => old('coddis2', $equivalencia2?->coddis),
            'nome_disciplina2' => old('nome_disciplina2', $equivalencia2?->nome_disciplina),
            'ies2' => old('ies2', $equivalencia2?->ies),
            'coddis3' => old('coddis3', $equivalencia3?->coddis),
            'nome_disciplina3' => old('nome_disciplina3', $equivalencia3?->nome_disciplina),
            'ies3' => old('ies3', $equivalencia3?->ies),
        ];
    }

    // Valida os dados do formulário de criação/edição de equivalências, p
    // reparando os conjuntos de dados para cada equivalência a ser criada/atualizada.
    // Não foi feito em um request separado porque as regras de validação
    // são um pouco mais complexas do que o usual, envolvendo validações condicionais
    // e interdependentes entre os campos, e o formato dos dados é específico para a estrutura do formulário de equivalências.
    private function validarEPrepararConjuntosDeEquivalencia(Request $request): array
    {
        $dados = $request->validate([
            'coddis' => ['nullable', 'string', 'max:7'],
            'nome_disciplina' => ['nullable', 'string', 'max:240'],
            'ies' => ['nullable', 'string', 'max:255'],
            'coddis2' => ['nullable', 'string', 'max:7'],
            'nome_disciplina2' => ['nullable', 'string', 'max:240'],
            'ies2' => ['nullable', 'string', 'max:255'],
            'coddis3' => ['nullable', 'string', 'max:7'],
            'nome_disciplina3' => ['nullable', 'string', 'max:240'],
            'ies3' => ['nullable', 'string', 'max:255'],
        ]);

        $conjuntos = [];
        $erros = [];

        foreach (['', '2', '3'] as $sufixo) {
            $kCoddis = 'coddis'.$sufixo;
            $kNome = 'nome_disciplina'.$sufixo;
            $kIes = 'ies'.$sufixo;

            $coddis = trim((string) ($dados[$kCoddis] ?? ''));
            $nome = trim((string) ($dados[$kNome] ?? ''));
            $ies = trim((string) ($dados[$kIes] ?? ''));

            if ($coddis === '' && $nome === '' && $ies === '') {
                continue;
            }

            if ($coddis === '') {
                $erros[$kCoddis] = 'O campo código da equivalência é obrigatório para cada conjunto preenchido.';

                continue;
            }

            $dadosCursada = [
                'coddis' => $coddis,
                'nome_disciplina' => $nome !== '' ? $nome : null,
                'ies' => $ies !== '' ? $ies : null,
            ];

            $encontradaNoReplicado = Disciplina::disciplinaUspNoReplicado($coddis);

            if (! $encontradaNoReplicado) {
                if (empty($dadosCursada['nome_disciplina'])) {
                    $erros[$kNome] = 'Nome da equivalência é obrigatório quando a disciplina não for USP.';
                }

                if (empty($dadosCursada['ies'])) {
                    $erros[$kIes] = 'IES é obrigatória quando a disciplina não for USP.';
                }
            }

            $conjuntos[] = $dadosCursada;
        }

        if ($erros) {
            throw ValidationException::withMessages($erros);
        }

        if (count($conjuntos) === 0) {
            throw ValidationException::withMessages([
                'coddis' => 'Preencha ao menos um conjunto de equivalência.',
            ]);
        }

        return $conjuntos;
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

    // Verifica se a disciplina requerida pertence ao curso e habilitação, ou seja,
    // se existe alguma equivalência automática vinculando essa disciplina como requerida
    // no contexto do curso e habilitação fornecidos.
    private function requeridaPertenceAoCurso(Disciplina $requerida, int $codcur, int $codhab): bool
    {
        return Equivalencia::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $requerida->id)
            ->exists();
    }

    // Remove uma disciplina se ela não tiver mais vínculos com outras disciplinas.
    // Ultilizado para limpar disciplinas equivalentes após remoção de equivalências.
    private function removerDisciplinaSeOrfa(int $disciplinaId): void
    {
        $disciplina = Disciplina::find($disciplinaId);

        if (! $disciplina) {
            return;
        }

        $temVinculoComoRequerida = Equivalencia::query()
            ->where('requerida_id', $disciplina->id)
            ->exists();

        $temVinculoComoCursada = Equivalencia::query()
            ->where('cursada_id', $disciplina->id)
            ->exists();

        if (! $temVinculoComoRequerida && ! $temVinculoComoCursada) {
            $disciplina->delete();
        }
    }
}
