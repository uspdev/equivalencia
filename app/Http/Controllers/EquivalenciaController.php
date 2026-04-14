<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquivalenciaRequest;
use App\Http\Requests\UpdateEquivalenciaRequest;
use App\Models\Disciplina;
use App\Models\Equivalencia;
use App\Replicado\Graduacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Uspdev\Forms\Form;

class EquivalenciaController extends Controller
{
    /**
     * Construtor do controlador.
     * Configura o middleware para ativar a URL no tema.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('equivalencias');

            return $next($request);
        });
    }

    /**
     * Exibe a página inicial com a lista de cursos e habilitações.
     *
     * @return View
     */
    public function index()
    {
        $cursos = Graduacao::listarCursosHabilitacoes();

        return view('equivalencias.index', [
            'cursos' => $cursos,
        ]);
    }

    /**
     * Exibe as disciplinas USP e suas equivalências para um curso específico.
     * Monta os formulários necessários para criação, edição e remoção de equivalências.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @return View
     */
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
            ->get();

        $disciplinas = $disciplinas->transform(function (Disciplina $disciplina) {
            $disciplina->setRelation(
                'equivalentes',
                // Ordena as equivalências primeiro pelo grupo
                // (com padding para garantir ordenação numérica correta) e depois pelo código da disciplina cursada.
                $disciplina->equivalentes
                    ->sortBy(function (Equivalencia $item) {
                        return sprintf('%010d-%s', (int) $item->grupo, (string) ($item->coddis ?? ''));
                    })
                    ->values()
            );

            return $disciplina;
        });

        $formHtml = $this->buildFormHtml(
            'eq_usp_create',
            route('equivalencias.store', ['codcur' => $codcur, 'codhab' => $codhab]),
            'POST',
            $this->oldInputForFields(['coddis'])
        );

        $formHtmlEdit = $disciplinas
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

        $formHtmlEquivalencia = $disciplinas
            ->mapWithKeys(function (Disciplina $disciplinaUsp) use ($codcur, $codhab) {
                return [
                    $disciplinaUsp->id => $this->buildFormHtml(
                        'eq_child_add',
                        route('equivalencias.add-equivalencia', [$codcur, $codhab, $disciplinaUsp]),
                        'POST',
                        $this->oldInputForFields([
                            'coddis',
                            'nome_disciplina',
                            'ies',
                            'coddis2',
                            'nome_disciplina2',
                            'ies2',
                            'coddis3',
                            'nome_disciplina3',
                            'ies3',
                        ])
                    ),
                ];
            })
            ->all();

        $formHtmlEquivalenciaEdit = $disciplinas
            ->reduce(function (array $forms, Disciplina $disciplinaUsp) use ($codcur, $codhab) {
                $formsDaDisciplina = $disciplinaUsp->equivalentes
                    ->mapWithKeys(function (Equivalencia $equivalenciaFilha) use ($codcur, $codhab, $disciplinaUsp) {
                        return [
                            $equivalenciaFilha->id => $this->buildFormHtml(
                                'eq_child_add',
                                route('equivalencias.update-equivalencia', [$codcur, $codhab, $disciplinaUsp, $equivalenciaFilha]),
                                'PUT',
                                $this->defaultsParaFormularioEdicaoDeGrupo($disciplinaUsp, $equivalenciaFilha)
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

    /**
     * Cria uma nova disciplina USP no sistema.
     * Valida os dados, cria a disciplina se necessário e estabelece o placeholder de equivalência.
     *
     * @param  StoreEquivalenciaRequest  $request  Dados validados da requisição
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @return RedirectResponse
     */
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

    /**
     * Atualiza uma disciplina USP existente.
     * Valida se a disciplina pertence ao curso antes de atualizar.
     *
     * @param  UpdateEquivalenciaRequest  $request  Dados validados da requisição
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP a ser atualizada
     * @return RedirectResponse
     */
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
     * Remove uma disciplina USP e todas as suas equivalências.
     * Realiza limpeza de disciplinas órfãs após a remoção.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP a ser removida
     * @return RedirectResponse
     */
    public function destroy(int $codcur, int $codhab, Disciplina $equivalencia)
    {
        abort_unless($this->requeridaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);

        $vinculos = Equivalencia::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $equivalencia->id)
            ->get();
        // Vinculos que não são placeholders (ou seja, equivalências reais)
        // devem ter suas disciplinas cursadas verificadas para possível
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
     * Adiciona novas equivalências (disciplinas cursadas) para uma disciplina USP específica.
     * Valida os dados, cria as disciplinas cursadas e estabelece os vínculos.
     *
     * @param  Request  $request  Dados da requisição
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP que receberá as equivalências
     * @return RedirectResponse
     */
    public function addEquivalencia(Request $request, int $codcur, int $codhab, Disciplina $equivalencia)
    {
        abort_unless($this->requeridaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);

        $conjuntos = $this->validarEPrepararConjuntosDeEquivalencia($request);

        $grupo = Equivalencia::proximoGrupo();

        foreach ($conjuntos as $dadosCursada) {
            $cursada = Disciplina::criarCursadaPorFormulario($dadosCursada);

            Equivalencia::criarVinculoCursada(
                (int) $grupo,
                $equivalencia->id,
                $cursada->id,
                $codcur,
                $codhab,
                Equivalencia::TIPO_AUTOMATICA
            );
        }

        return redirect()
            ->back()
            ->with('alert-success', 'Equivalência adicionada com sucesso.');
    }

    /**
     * Atualiza um grupo de equivalências para uma disciplina USP.
     * Permite editar as disciplinas equivalentes de um grupo mantendo ou criando novos vínculos.
     *
     * @param  Request  $request  Dados da requisição
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP proprietária do grupo
     * @param  Equivalencia  $equivalenciaFilha  Uma equivalência do grupo a ser atualizado
     * @return RedirectResponse
     */
    public function updateEquivalencia(Request $request, int $codcur, int $codhab, Disciplina $equivalencia, Equivalencia $equivalenciaFilha)
    {
        // Validações de segurança para garantir que a equivalência filha realmente pertence à disciplina requerida
        // e ao contexto do curso e habilitação, e que não é uma placeholder.
        abort_unless($this->requeridaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);
        abort_unless($equivalenciaFilha->pertenceARequeridaNoContexto($equivalencia->id, $codcur, $codhab), 404);
        abort_unless(! $equivalenciaFilha->isPlaceholderRequerida(), 404);

        $conjuntos = $this->validarEPrepararConjuntosDeEquivalencia($request);

        $equivalenciasDoGrupo = Equivalencia::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $equivalencia->id)
            ->where('grupo', $equivalenciaFilha->grupo)
            ->whereColumn('cursada_id', '!=', 'requerida_id')
            ->with('cursada')
            ->orderBy('id')
            ->get();

        $equivalenciasOrdenadasParaEdicao = collect([$equivalenciaFilha])
            ->merge(
                $equivalenciasDoGrupo
                    ->reject(fn (Equivalencia $item) => $item->id === $equivalenciaFilha->id)
                    ->values()
            )
            ->values();

        foreach ($conjuntos as $index => $dadosCursada) {
            $vinculoExistente = $equivalenciasOrdenadasParaEdicao->get($index);

            if ($vinculoExistente) {
                $vinculoExistente->loadMissing('cursada');
                abort_unless($vinculoExistente->cursada, 404);
                $vinculoExistente->cursada->atualizarCursadaPorFormulario($dadosCursada);

                continue;
            }

            $novaCursada = Disciplina::criarCursadaPorFormulario($dadosCursada);

            Equivalencia::criarVinculoCursada(
                (int) $equivalenciaFilha->grupo,
                $equivalencia->id,
                $novaCursada->id,
                $codcur,
                $codhab,
                Equivalencia::TIPO_AUTOMATICA
            );
        }

        return redirect()
            ->back()
            ->with('alert-success', 'Equivalência atualizada com sucesso.');
    }

    /**
     * Remove uma única equivalência de um grupo.
     * Realiza limpeza da disciplina cursada se ficar órfã após a remoção.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP proprietária
     * @param  Equivalencia  $equivalenciaFilha  A equivalência a ser removida
     * @return RedirectResponse
     */
    public function destroyEquivalencia(int $codcur, int $codhab, Disciplina $equivalencia, Equivalencia $equivalenciaFilha)
    {
        abort_unless($this->requeridaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);
        abort_unless($equivalenciaFilha->pertenceARequeridaNoContexto($equivalencia->id, $codcur, $codhab), 404);
        abort_unless(! $equivalenciaFilha->isPlaceholderRequerida(), 404);

        $cursadaId = $equivalenciaFilha->cursada_id;
        $equivalenciaFilha->delete();

        $this->removerDisciplinaSeOrfa((int) $cursadaId);

        return redirect()
            ->back()
            ->with('alert-success', 'Equivalência removida com sucesso.');
    }

    /**
     * Remove um grupo inteiro de equivalências para uma disciplina USP.
     * Remove todos os vínculos do grupo e realiza limpeza de disciplinas órfãs.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP proprietária do grupo
     * @param  Equivalencia  $equivalenciaFilha  Uma equivalência do grupo a ser removido
     * @return RedirectResponse
     */
    public function destroyEquivalenciaGrupo(int $codcur, int $codhab, Disciplina $equivalencia, Equivalencia $equivalenciaFilha)
    {
        abort_unless($this->requeridaPertenceAoCurso($equivalencia, $codcur, $codhab), 404);
        abort_unless($equivalenciaFilha->pertenceARequeridaNoContexto($equivalencia->id, $codcur, $codhab), 404);
        abort_unless(! $equivalenciaFilha->isPlaceholderRequerida(), 404);

        $vinculosDoGrupo = Equivalencia::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $equivalencia->id)
            ->where('grupo', $equivalenciaFilha->grupo)
            ->get();

        $cursadasParaLimpeza = $vinculosDoGrupo
            ->pluck('cursada_id')
            ->unique()
            ->values();

        Equivalencia::query()
            ->whereIn('id', $vinculosDoGrupo->pluck('id'))
            ->delete();

        foreach ($cursadasParaLimpeza as $cursadaId) {
            $this->removerDisciplinaSeOrfa((int) $cursadaId);
        }

        $this->removerDisciplinaSeOrfa($equivalencia->id);

        return redirect()
            ->back()
            ->with('alert-success', 'Grupo de equivalências removido com sucesso.');
    }

    /**
     * Cria o HTML do formulário utilizando o pacote Uspdev/Forms.
     * Gera o HTML a partir da configuração fornecida.
     *
     * @param  string  $name  Nome do formulário
     * @param  string  $action  URL de ação do formulário
     * @param  string  $method  Método HTTP (POST, PUT, etc)
     * @param  array  $values  Valores padrão para os campos
     * @return string HTML do formulário gerado
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

    /**
     * Prepara os valores padrão para o formulário de edição de uma equivalência.
     * Organiza os vínculos do mesmo grupo para preencher os campos adicionais (coddis2, coddis3, etc).
     *
     * @param  Disciplina  $disciplinaUsp  A disciplina USP
     * @param  Equivalencia  $equivalenciaFilha  A equivalência sendo editada
     * @return array Array com os valores padrão para o formulário
     */
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

    /**
     * Valida e prepara os conjuntos de dados para criação/edição de equivalências.
     * Inclui validações condicionais e interdependentes entre os campos.
     * Não utiliza um Request separado devido à complexidade das regras de validação.
     *
     * @param  Request  $request  Dados da requisição
     * @return array Array com os conjuntos de equivalências validados
     *
     * @throws ValidationException
     */
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

    /**
     * Adiciona namespace aos IDs do formulário para evitar duplicatas em listas de modais.
     * Essencial para manter o funcionamento correto do Select2 em múltiplos formulários.
     *
     * @param  string  $formHtml  HTML do formulário
     * @param  int  $disciplinaId  ID da disciplina para criar um namespace único
     * @return string HTML do formulário com IDs namespaceados
     */
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

    /**
     * Recupera os valores antigos (old input) para os campos do formulário.
     * Utiliza valores padrão caso não haja old input disponível.
     *
     * @param  array  $fields  Lista de nomes de campos
     * @param  array  $defaults  Valores padrão para os campos (opcional)
     * @return array Array com os valores old ou padrão
     */
    private function oldInputForFields(array $fields, array $defaults = []): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field] = old($field, $defaults[$field] ?? null);
        }

        return $values;
    }

    /**
     * Verifica se uma disciplina requerida pertence a um curso específico.
     * Valida se existe equivalência automática vinculando a disciplina no contexto do curso.
     *
     * @param  Disciplina  $requerida  A disciplina requerida a verificar
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @return bool True se a disciplina pertence ao curso, false caso contrário
     */
    private function requeridaPertenceAoCurso(Disciplina $requerida, int $codcur, int $codhab): bool
    {
        return Equivalencia::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $requerida->id)
            ->exists();
    }

    /**
     * Remove uma disciplina se ela não tiver mais vínculos de equivalência.
     * Utilizado para limpeza de disciplinas órfãs após remoção de equivalências.
     *
     * @param  int  $disciplinaId  ID da disciplina a verificar e remover
     */
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
