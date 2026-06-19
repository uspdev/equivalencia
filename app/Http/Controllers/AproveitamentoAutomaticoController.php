<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveEditModeStateRequest;
use App\Http\Requests\SaveEquivalenciaFilhaRequest;
use App\Http\Requests\StoreEquivalenciaRequest;
use App\Http\Requests\UpdateEquivalenciaRequest;
use App\Models\Disciplina;
use App\Models\Aproveitamento;
use App\Replicado\Graduacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AproveitamentoAutomaticoController extends Controller
{
    /**
     * Construtor do controlador.
     * Configura o middleware para ativar a URL no tema.
     */
    public function __construct()
    {
        $this->middleware('can:equivalencias');

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

        return view('aproveitamentos_automaticos.index', [
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
        $curso = Graduacao::obterCursoHabilitacao($codcur, $codhab);
        $canManageEquivalencias = auth()->user()?->can('svgrad') ?? false;

        abort_unless($curso, 404);

        $disciplinas = Disciplina::listarDisciplinasComEquivalencias($codcur, $codhab);
        $formDataEquivalenciaEdit = $canManageEquivalencias
            ? Aproveitamento::dadosParaFormularioEdicaoDeEquivalencias($disciplinas)
            : [];

        return view('aproveitamentos_automaticos.show', [
            'disciplinas' => $disciplinas,
            'codcur' => $codcur,
            'codhab' => $codhab,
            'nomeCurso' => $curso['nomcur'],
            'editModeEnabled' => $canManageEquivalencias ? (bool) session()->get($this->editModeSessionKey(), false) : false,
            'canManageEquivalencias' => $canManageEquivalencias,
            'formDataEquivalenciaEdit' => $formDataEquivalenciaEdit,
        ]);
    }

    /**
     * Persiste o estado global do modo de edicao da tela de equivalencias na sessao.
     *
     * @param  SaveEditModeStateRequest  $request  Dados da requisição
     */
    public function saveEditModeState(SaveEditModeStateRequest $request): JsonResponse
    {
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
        $dados = $request->validated();

        Disciplina::garantirRequeridaAutomaticaNoContexto(
            $dados['coddis'],
            $dados['verdis'] ?? null,
            $codcur,
            $codhab
        );

        return redirect()
            ->route('equivalencias.show', [$codcur, $codhab, 'filter'=> $dados['coddis']])
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
        abort_unless($equivalencia->pertenceComoRequeridaAoContexto($codcur, $codhab), 404);

        $dados = $request->validated();
        $requerida = Disciplina::upsertRequeridaPorCoddis($dados['coddis'], $dados['verdis'] ?? null, $equivalencia);

        if ((int) $requerida->id !== (int) $equivalencia->id) {
            $vinculos = $equivalencia->equivalenciasComoRequerida()
                ->automaticas()
                ->doContexto($codcur, $codhab)
                ->get();

            foreach ($vinculos as $vinculo) {
                $vinculo->update([
                    'requerida_id' => $requerida->id,
                    'cursada_id' => $vinculo->isPlaceholderRequerida()
                        ? $requerida->id
                        : $vinculo->cursada_id,
                ]);
            }

            $equivalencia->removerSeOrfa();
        }

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
        abort_unless($equivalencia->pertenceComoRequeridaAoContexto($codcur, $codhab), 404);

        Aproveitamento::criarGrupoDeCursadas(
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
     * Atualiza um grupo de equivalências para uma disciplina USP.
     * Permite editar as disciplinas equivalentes de um grupo mantendo ou criando novos vínculos.
     *
     * @param  SaveEquivalenciaFilhaRequest  $request  Dados da requisição
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP proprietária do grupo
     * @param  Aproveitamento  $equivalenciaFilha  Uma equivalência do grupo a ser atualizado
     * @return RedirectResponse
     */
    public function updateEquivalencia(SaveEquivalenciaFilhaRequest $request, int $codcur, int $codhab, Disciplina $equivalencia, Aproveitamento $equivalenciaFilha)
    {
        $this->abortUnlessEquivalenciaFilhaValida($equivalencia, $equivalenciaFilha, $codcur, $codhab);

        Aproveitamento::atualizarGrupoDeCursadas(
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
     * Remove uma única equivalência de um grupo.
     * Realiza limpeza da disciplina cursada se ficar órfã após a remoção.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @param  Disciplina  $equivalencia  A disciplina USP proprietária
     * @param  Aproveitamento  $equivalenciaFilha  A equivalência a ser removida
     * @return RedirectResponse
     */
    public function destroyEquivalencia(int $codcur, int $codhab, Disciplina $equivalencia, Aproveitamento $equivalenciaFilha)
    {
        $this->abortUnlessEquivalenciaFilhaValida($equivalencia, $equivalenciaFilha, $codcur, $codhab);

        $equivalenciaFilha->removerELimparCursada();

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
     * @param  Aproveitamento  $equivalenciaFilha  Uma equivalência do grupo a ser removido
     * @return RedirectResponse
     */
    public function destroyEquivalenciaGrupo(int $codcur, int $codhab, Disciplina $equivalencia, Aproveitamento $equivalenciaFilha)
    {
        $this->abortUnlessEquivalenciaFilhaValida($equivalencia, $equivalenciaFilha, $codcur, $codhab);

        Aproveitamento::removerGrupoELimparDisciplinas($equivalencia, $equivalenciaFilha, $codcur, $codhab);

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
        abort_unless($equivalenciaFilha->isEquivalenciaRealDaRequeridaNoContexto($equivalencia->id, $codcur, $codhab), 404);
    }
}
