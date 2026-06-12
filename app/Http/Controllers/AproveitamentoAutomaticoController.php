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
use Uspdev\Forms\Form;
use Uspdev\Forms\Models\FormDefinition;

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

        $formHtmlCreate = '';
        $formHtmlEdit = [];
        $formHtmlEquivalenciaCreate = [];
        $formHtmlEquivalenciaEdit = [];

        if ($canManageEquivalencias) {
            $formHtmlCreate = $this->buildFormHtml(
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
                        $this->oldInputForFields(['coddis'], ['coddis' => $disciplinaUsp->coddis])
                    );

                    return [
                        $disciplinaUsp->id => $this->namespaceFormHtmlForIndex($formHtml, $disciplinaUsp->id),
                    ];
                })
                ->all();

            $formHtmlEquivalenciaCreate = $disciplinas
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
                        ->mapWithKeys(function (Aproveitamento $equivalenciaFilha) use ($codcur, $codhab, $disciplinaUsp) {
                            return [
                                $equivalenciaFilha->id => $this->buildFormHtml(
                                    'eq_child_add',
                                    route('equivalencias.update-equivalencia', [$codcur, $codhab, $disciplinaUsp, $equivalenciaFilha]),
                                    'PUT',
                                    $disciplinaUsp->defaultsParaFormularioEdicaoDeGrupo($equivalenciaFilha)
                                ),
                            ];
                        })
                        ->all();

                    return $forms + $formsDaDisciplina;
                }, []);
        }

        return view('aproveitamentos_automaticos.show', [
            'disciplinas' => $disciplinas,
            'codcur' => $codcur,
            'codhab' => $codhab,
            'nomeCurso' => $curso['nomcur'],
            'editModeEnabled' => $canManageEquivalencias ? (bool) session()->get($this->editModeSessionKey(), false) : false,
            'canManageEquivalencias' => $canManageEquivalencias,
            'formHtmlCreate' => $formHtmlCreate,
            'formHtmlEdit' => $formHtmlEdit,
            'formHtmlEquivalenciaCreate' => $formHtmlEquivalenciaCreate,
            'formHtmlEquivalenciaEdit' => $formHtmlEquivalenciaEdit,
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

        $requerida = Disciplina::query()
            ->where('coddis', $dados['coddis'])
            ->where('ies', 'USP')
            ->first();

        Disciplina::garantirRequeridaAutomaticaNoContexto($dados['coddis'], $codcur, $codhab, $requerida);

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

    private function abortUnlessEquivalenciaFilhaValida(
        Disciplina $equivalencia,
        Aproveitamento $equivalenciaFilha,
        int $codcur,
        int $codhab
    ): void {
        abort_unless($equivalencia->pertenceComoRequeridaAoContexto($codcur, $codhab), 404);
        abort_unless($equivalenciaFilha->isEquivalenciaRealDaRequeridaNoContexto($equivalencia->id, $codcur, $codhab), 404);
    }

    public function showInitForm()
    {
        $initial_def = FormDefinition::where('name',config('app.initial_form'))->firstOrFail();
        $formHtml = app(Form::class)->generateHtml($initial_def->name);

        return view('showInitialForm',['formHtml' => $formHtml]);
    }

    public function novaReq(Request $request)
    {
        
    }
}
