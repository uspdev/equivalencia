<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\DisciplinaRole;
use App\Http\Requests\SaveEditModeStateRequest;
use App\Http\Requests\SaveEquivalenciaFilhaRequest;
use App\Http\Requests\StoreEquivalenciaRequest;
use App\Http\Requests\UpdateEquivalenciaRequest;
use App\Models\Aproveitamento;
use App\Models\Disciplina;
use App\Replicado\Graduacao;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AproveitamentoAutomaticoController extends Controller
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
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_VIEW->value);

        $cursos = Graduacao::listarCursosHabilitacoes();
        // Consulta para obter o total de aproveitamentos automáticos por curso e habilitação
        $requeridasPorContexto = Aproveitamento::query()
            ->automaticas()
            ->join('disciplinas', function ($join) {
                $join
                    ->on('disciplinas.aproveitamento_id', '=', 'aproveitamentos.id')
                    ->where('disciplinas.role', DisciplinaRole::REQUERIDA->value);
            })
            ->select([
                'aproveitamentos.codcur',
                'aproveitamentos.codhab',
                'disciplinas.ies',
                'disciplinas.coddis',
                'disciplinas.verdis',
            ])
            ->distinct();
        // Agrupa os resultados por curso e habilitação e
        // conta o total de aproveitamentos automáticos
        $totaisAproveitamentos = DB::query()
            ->fromSub($requeridasPorContexto, 'requeridas')
            ->select(['codcur', 'codhab'])
            ->selectRaw('COUNT(*) as total')
            ->groupBy('codcur', 'codhab')
            ->get()
            // Mapeia os resultados para um array associativo com chave "codcur/codhab" e valor do total
            ->mapWithKeys(fn(object $aproveitamento) => [
                "{$aproveitamento->codcur}/{$aproveitamento->codhab}" => (int) $aproveitamento->total,
            ])
            ->all();

        return view('aproveitamentos_automaticos.index', [
            'cursos' => $cursos,
            'totaisAproveitamentos' => $totaisAproveitamentos,
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
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_VIEW->value);

        $curso = Graduacao::obterCursoHabilitacao($codcur, $codhab);
        $canManageEquivalencias = auth()->user()?->can(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value) ?? false;

        abort_unless($curso, 404);

        $disciplinas = Disciplina::listarDisciplinasComEquivalencias($codcur, $codhab);
        $vigenciasVersoesCursadas = Disciplina::vigenciasDasVersoesDasCursadas($disciplinas);
        $formDataEquivalenciaEdit = $canManageEquivalencias
            ? Aproveitamento::dadosParaFormularioEdicaoDeEquivalencias($disciplinas, false)
            : [];
        $modalData = $this->modalData(
            $disciplinas,
            $codcur,
            $codhab,
            $vigenciasVersoesCursadas,
            $formDataEquivalenciaEdit,
            $canManageEquivalencias
        );

        return view('aproveitamentos_automaticos.show', [
            'disciplinas' => $disciplinas,
            'codcur' => $codcur,
            'codhab' => $codhab,
            'nomeCurso' => $curso['nomcur'],
            'editModeEnabled' => $canManageEquivalencias ? (bool) session()->get($this->editModeSessionKey(), false) : false,
            'canManageEquivalencias' => $canManageEquivalencias,
            'formDataEquivalenciaEdit' => $formDataEquivalenciaEdit,
            'vigenciasVersoesCursadas' => $vigenciasVersoesCursadas,
            'modalData' => $modalData,
        ]);
    }

    /**
     * Monta o payload usado pelos modais compartilhados da página.
     */
    private function modalData(
        Collection $disciplinas,
        int $codcur,
        int $codhab,
        array $vigenciasVersoesCursadas,
        array $formDataEquivalenciaEdit,
        bool $canManageEquivalencias
    ): array {
        $data = [
            'details' => [],
            'requiredForms' => [],
            'equivalenceForms' => [],
        ];

        if ($canManageEquivalencias) {
            $data['requiredForms']['create'] = [
                'title' => 'Nova disciplina requerida',
                'action' => route('equivalencias.store', [$codcur, $codhab]),
                'method' => 'POST',
                'values' => [],
            ];
        }

        foreach ($disciplinas as $disciplina) {
            $requiredKey = 'required-' . $disciplina->id;
            $data['details'][$requiredKey] = $this->disciplineDetails(
                $disciplina,
                'Dados da disciplina requerida'
            );

            if ($canManageEquivalencias) {
                $data['requiredForms'][(string) $disciplina->id] = [
                    'title' => 'Editar disciplina requerida',
                    'action' => route('equivalencias.update', [$codcur, $codhab, $disciplina]),
                    'method' => 'PUT',
                    'values' => [
                        'coddis' => $disciplina->coddis,
                        'verdis' => $disciplina->verdis,
                        'nome_disciplina' => $disciplina->nome_disciplina,
                    ],
                ];

                $data['equivalenceForms']['add-' . $disciplina->id] = [
                    'title' => 'Adicionar disciplina cursada equivalente',
                    'action' => route('equivalencias.add-equivalencia', [$codcur, $codhab, $disciplina]),
                    'method' => 'POST',
                    'values' => [],
                ];
            }

            foreach ($disciplina->equivalentes as $equivalencia) {
                foreach ($equivalencia->cursadas as $cursada) {
                    $detailsKey = 'equivalence-' . $cursada->id;
                    $data['details'][$detailsKey] = $this->disciplineDetails(
                        $cursada,
                        'Dados da disciplina cursada',
                        $vigenciasVersoesCursadas[$cursada->id] ?? null,
                        $equivalencia
                    );
                }
            }

            if (! $canManageEquivalencias) {
                continue;
            }

            foreach ($disciplina->equivalentes as $representante) {
                $data['equivalenceForms']['edit-' . $representante->id] = [
                    'title' => 'Editar disciplina cursada equivalente',
                    'action' => route('equivalencias.update-equivalencia', [
                        $codcur,
                        $codhab,
                        $disciplina,
                        $representante,
                    ]),
                    'method' => 'PUT',
                    'values' => $formDataEquivalenciaEdit[$representante->id] ?? [],
                ];
            }
        }

        return $data;
    }

    /**
     * Normaliza os dados de uma disciplina para o modal de consulta.
     */
    private function disciplineDetails(
        Disciplina $disciplina,
        string $title,
        ?string $vigenciaVersao = null,
        ?Aproveitamento $equivalencia = null
    ): array {
        return [
            'title' => $title,
            'heading' => $disciplina->coddis . ' - ' . ($disciplina->nome_disciplina ?: 'Nome não informado'),
            'code' => $disciplina->coddis,
            'institution' => $disciplina->ies,
            'unit' => $disciplina->sglund,
            'credits' => $disciplina->creditos,
            'workload' => $disciplina->carga_horaria ? $disciplina->carga_horaria . ' horas' : null,
            'version' => filled($disciplina->verdis)
                ? $disciplina->verdis . ($vigenciaVersao ? ' — ' . $vigenciaVersao : '')
                : null,
            'equivalence' => $equivalencia ? [
                'meetingNumber' => $equivalencia->numero_reuniao,
                'meetingDate' => $equivalencia->data_reuniao?->format('d/m/Y'),
                'notes' => $equivalencia->observacoes,
            ] : null,
        ];
    }

    /**
     * Persiste o estado global do modo de edicao da tela de equivalencias na sessao.
     *
     * @param  SaveEditModeStateRequest  $request  Dados da requisição
     */
    public function saveEditModeState(SaveEditModeStateRequest $request): JsonResponse
    {
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value);

        $dados = $request->validated();

        session()->put($this->editModeSessionKey(), (bool) $dados['enabled']);

        return response()->json([
            'saved' => true,
            'enabled' => (bool) $dados['enabled'],
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
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value);

        $dados = $request->validated();

        Disciplina::garantirRequeridaAutomaticaNoContexto(
            $dados['coddis'],
            $dados['verdis'] ?? null,
            $codcur,
            $codhab
        );

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab, 'filter' => $dados['coddis']])
            ->with('alert-success', 'Equivalência automática criada com sucesso.');
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
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value);

        abort_unless($equivalencia->pertenceComoRequeridaAoContexto($codcur, $codhab), 404);

        $dados = $request->validated();
        $dadosRequerida = array_merge(
            Disciplina::dadosDaRequeridaPorCoddis($dados['coddis'], $dados['verdis'] ?? null),
            ['role' => DisciplinaRole::REQUERIDA]
        );

        Disciplina::query()
            ->requeridas()
            ->where('ies', $equivalencia->ies)
            ->where('coddis', $equivalencia->coddis)
            ->where('verdis', $equivalencia->verdis)
            ->whereHas('aproveitamento', fn($query) => $query->automaticas()->doContexto($codcur, $codhab))
            ->get()
            ->each(fn(Disciplina $disciplina) => $disciplina->update($dadosRequerida));

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
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value);

        abort_unless($equivalencia->pertenceComoRequeridaAoContexto($codcur, $codhab), 404);

        Aproveitamento::removerVinculosDaRequeridaNoContexto($equivalencia, $codcur, $codhab);

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab])
            ->with('alert-success', 'Disciplina USP removida com sucesso.');
    }

    /**
     * Retorna a chave de sessão para estado do modo de edição por usuário.
     * Inclui o ID do usuário autenticado para isolamento entre funcionários.
     */
    private function editModeSessionKey(): string
    {
        $userId = auth()->id();
        $baseKey = config('equivalencia.edit_mode_session_key', 'equivalencias.edit_mode');

        return (string) "{$baseKey}.user.{$userId}";
    }

    /**
     * Adiciona novas equivalências (disciplinas cursadas) para uma disciplina USP específica.
     * Valida os dados, cria as disciplinas cursadas e estabelece os vínculos.
     *
     * @param  SaveEquivalenciaFilhaRequest  $request  Dados da requisição
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP que receberá as equivalências
     * @return RedirectResponse
     */
    public function addEquivalencia(SaveEquivalenciaFilhaRequest $request, int $codcur, int $codhab, Disciplina $equivalencia)
    {
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value);

        abort_unless($equivalencia->pertenceComoRequeridaAoContexto($codcur, $codhab), 404);

        Aproveitamento::criarAproveitamentoAutomaticoComCursadas(
            $equivalencia,
            $codcur,
            $codhab,
            $request->conjuntosDeEquivalencia()
        );

        return redirect()
            ->back()
            ->with('alert-success', 'Equivalência adicionada com sucesso.');
    }

    /**
     * Atualiza um aproveitamento automático para uma disciplina USP.
     * Permite editar as disciplinas cursadas equivalentes mantendo ou criando novos vínculos.
     *
     * @param  SaveEquivalenciaFilhaRequest  $request  Dados da requisição
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP proprietária
     * @param  Aproveitamento  $equivalenciaFilha  O aproveitamento automático a ser atualizado
     * @return RedirectResponse
     */
    public function updateEquivalencia(SaveEquivalenciaFilhaRequest $request, int $codcur, int $codhab, Disciplina $equivalencia, Aproveitamento $equivalenciaFilha)
    {
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value);

        $this->abortUnlessEquivalenciaFilhaValida($equivalencia, $equivalenciaFilha, $codcur, $codhab);

        Aproveitamento::atualizarAproveitamentoAutomaticoComCursadas(
            $equivalencia,
            $equivalenciaFilha,
            $codcur,
            $codhab,
            $request->conjuntosDeEquivalencia()
        );

        return redirect()
            ->back()
            ->with('alert-success', 'Equivalência atualizada com sucesso.');
    }

    /**
     * Remove uma única disciplina cursada de um aproveitamento automático.
     * Realiza limpeza da disciplina cursada se ficar órfã após a remoção.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP proprietária
     * @param  Aproveitamento  $equivalenciaFilha  A equivalência a ser removida
     * @return RedirectResponse
     */
    public function destroyEquivalencia(int $codcur, int $codhab, Disciplina $equivalencia, Disciplina $equivalenciaFilha)
    {
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value);

        $aproveitamento = $equivalenciaFilha->aproveitamento;
        abort_unless($aproveitamento instanceof Aproveitamento, 404);
        $this->abortUnlessEquivalenciaFilhaValida($equivalencia, $aproveitamento, $codcur, $codhab);

        $aproveitamento->removerELimparCursada($equivalenciaFilha);

        return redirect()
            ->back()
            ->with('alert-success', 'Equivalência removida com sucesso.');
    }

    /**
     * Remove um aproveitamento automático inteiro para uma disciplina USP.
     * Remove todos os vínculos e realiza limpeza de disciplinas órfãs.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP proprietária
     * @param  Aproveitamento  $equivalenciaFilha  O aproveitamento automático a ser removido
     * @return RedirectResponse
     */
    public function destroyEquivalenciaGrupo(int $codcur, int $codhab, Disciplina $equivalencia, Aproveitamento $equivalenciaFilha)
    {
        Gate::authorize(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value);

        $this->abortUnlessEquivalenciaFilhaValida($equivalencia, $equivalenciaFilha, $codcur, $codhab);

        Aproveitamento::removerAproveitamentoAutomatico($equivalencia, $equivalenciaFilha, $codcur, $codhab);

        return redirect()
            ->back()
            ->with('alert-success', 'Grupo de equivalências removido com sucesso.');
    }

    private function abortUnlessEquivalenciaFilhaValida(
        Disciplina $equivalencia,
        Aproveitamento $equivalenciaFilha,
        int $codcur,
        int $codhab
    ): void {
        abort_unless($equivalencia->pertenceComoRequeridaAoContexto($codcur, $codhab), 404);
        abort_unless($equivalenciaFilha->isAutomaticoDaRequeridaNoContexto($equivalencia, $codcur, $codhab), 404);
    }
}
