<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\SaveDraftDisciplineRequest;
use App\Http\Requests\SaveHistoryRequest;
use App\Http\Requests\SaveRequiredDisciplineRequest;
use App\Http\Requests\StoreAproveitamentoRequest;
use App\Models\Aproveitamento;
use App\Models\AproveitamentoRascunho;
use App\Models\Arquivo;
use App\Replicado\Graduacao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AproveitamentoController extends Controller
{
    public function home()
    {
        return view('home');
    }

    /**
     * Exibe o formulário para criação de uma requisição de equivalência,
     * gerando o html dinâmicamente a partir da biblioteca de formulários.
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $draft = $this->currentDraft();
        $disciplines = $draft->disciplinas();

        return view('aproveitamentos.create', [
            'draft' => $draft,
            'requiredDisciplineName' => $draft->nomeDaDisciplinaRequerida(),
            'disciplines' => $disciplines,
            'history' => $draft->historico(),
        ]);
    }

    public function saveRequiredDiscipline(SaveRequiredDisciplineRequest $request): RedirectResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $this->currentDraft()->salvarDisciplinaRequerida(
            $request->requiredDisciplineCode(),
            $request->requiredDisciplineVersion()
        );

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina desejada salva.');
    }

    public function createDiscipline(): View
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $draft = $this->currentDraft();
        abort_if($draft->atingiuLimiteDeDisciplinas(), 422, 'O limite de três disciplinas foi atingido.');

        return view('aproveitamentos.disciplina', [
            'discipline' => null,
            'formAction' => route('equivalencias.newreq-discipline-store'),
            'formMethod' => 'POST',
            'requiredDisciplineCode' => $draft->requerida_coddis,
        ]);
    }

    public function storeDiscipline(SaveDraftDisciplineRequest $request): RedirectResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $draft = $request->draft();
        abort_if($draft->atingiuLimiteDeDisciplinas(), 422, 'O limite de três disciplinas foi atingido.');

        $draft->salvarDisciplinaRequerida($request->requiredDisciplineCode(), $request->requiredDisciplineVersion());
        $draft->adicionarDisciplina($request->validated(), $request->file('ementa'));
        $request->session()->forget('discipline_modal');

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina adicionada.');
    }

    public function editDiscipline(string $disciplineId): View
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $draft = $this->currentDraft();
        $discipline = $draft->disciplinaPorIdOrFail($disciplineId);

        return view('aproveitamentos.disciplina', [
            'discipline' => $discipline,
            'formAction' => route('equivalencias.newreq-discipline-update', $disciplineId),
            'formMethod' => 'PUT',
            'requiredDisciplineCode' => $draft->requerida_coddis,
        ]);
    }

    public function updateDiscipline(SaveDraftDisciplineRequest $request, string $disciplineId): RedirectResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $draft = $request->draft();

        $draft->salvarDisciplinaRequerida($request->requiredDisciplineCode(), $request->requiredDisciplineVersion());
        $draft->atualizarDisciplina($disciplineId, $request->validated(), $request->file('ementa'));
        $request->session()->forget('discipline_modal');

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina atualizada.');
    }

    public function destroyDiscipline(string $disciplineId): RedirectResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $this->currentDraft()->removerDisciplina($disciplineId);

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina removida.');
    }

    public function saveHistory(SaveHistoryRequest $request): JsonResponse|RedirectResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $history = $request->draft()->salvarHistorico($request->file('historico'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Histórico escolar enviado.',
                'fileName' => $history->nome,
            ]);
        }

        return redirect()
            ->route('equivalencias.newreq-create');
    }

    /**
     * Lista as versões disponíveis para uma disciplina USP selecionada.
     */
    public function versoesDisciplina(Request $request): JsonResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $coddis = trim((string) $request->query('coddis', ''));

        if (! preg_match('/^[A-Za-z0-9]{3,7}$/', $coddis)) {
            return response()->json(['results' => []]);
        }

        return response()->json([
            'results' => app(Graduacao::class)->listarVersoesDisciplinaParaSelect($coddis),
        ]);
    }

    /**
     * Função que persiste os dados de uma requisição de aproveitamento
     * na tabela de disciplinas e também na tabela de equivalência
     * @param StoreAproveitamentoRequest $request
     * @return RedirectResponse
     */
    public function store(StoreAproveitamentoRequest $request): RedirectResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_CREATE->value);

        $draft = $request->draft();

        if ($request->hasFile('historico')) {
            $draft->salvarHistorico($request->file('historico'));
        }

        $result = Aproveitamento::criarRequerimentoDoRascunho(
            $draft,
            (int) $request->user()->id
        );

        return redirect()
            ->route('equivalencias.req-index')
            ->with('alert-success', "Requerimento para {$result['name']} criado com sucesso!");
    }

    /**
     * Função index, apresenta todas as requisições feitas pelo usuário
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        Gate::authorize(Permission::REQUERIMENTOS_VIEW_OWN->value);

        $requisitions = Aproveitamento::requerimentosDoUsuario(Auth::id());

        return view('aproveitamentos.index', ['requisicoes' => $requisitions]);
    }

    /**
     * Exibe um pedido de aproveitamento do usuário autenticado.
     * @param int $aproveitamento
     */
    public function show(int $aproveitamento): View
    {
        Gate::authorize(Permission::REQUERIMENTOS_VIEW_OWN->value);

        $show_data = Aproveitamento::dadosDeExibicaoDoRequerimento($aproveitamento, Auth::id());

        return view('aproveitamentos.show', ['show_data' => $show_data]);
    }

    /**
     * Exibe um PDF do requerimento no navegador.
     */
    public function showFile(int $aproveitamento, int $arquivo): StreamedResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_VIEW_OWN->value);

        $file = Arquivo::pertencenteAoRequerimentoDoUsuarioOrFail($arquivo, $aproveitamento, (int) Auth::id());

        abort_unless(Storage::exists($file->path), 404);

        return Storage::response(
            $file->path,
            $file->nome,
            ['Content-Type' => 'application/pdf'],
            'inline'
        );
    }

    /**
     * Remove um pedido de aproveitamento do banco de dados
     * @param int $aproveitamento
     * @return RedirectResponse
     */
    public function destroy(int $aproveitamento): RedirectResponse
    {
        Gate::authorize(Permission::REQUERIMENTOS_VIEW_OWN->value);

        $req_name = Aproveitamento::removerRequerimentoDoUsuario($aproveitamento, Auth::id());

        return redirect()
            ->back()
            ->with('alert-success', 'Requerimento de equivalência para ' . $req_name . ' removido com sucesso.');
    }

    private function currentDraft(): AproveitamentoRascunho
    {
        return AproveitamentoRascunho::atualDoUsuario((int) Auth::id());
    }
}
