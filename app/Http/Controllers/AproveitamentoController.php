<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDraftDisciplineRequest;
use App\Http\Requests\SaveRequiredDisciplineRequest;
use App\Http\Requests\StoreAproveitamentoRequest;
use App\Models\Aproveitamento;
use App\Models\AproveitamentoRascunho;
use App\Models\Arquivo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AproveitamentoController extends Controller
{
    public function create(): View
    {
        $draft = $this->currentDraft();
        $disciplines = $draft->disciplinas();

        return view('aproveitamentos.create', [
            'draft' => $draft,
            'requiredDisciplineName' => $draft->nomeDaDisciplinaRequerida(),
            'disciplines' => $disciplines,
            'transcriptGroups' => $draft->gruposDeHistorico(),
        ]);
    }

    public function saveRequiredDiscipline(SaveRequiredDisciplineRequest $request): RedirectResponse
    {
        $this->currentDraft()->salvarDisciplinaRequerida($request->requiredDisciplineCode());

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina desejada salva no rascunho.');
    }

    public function createDiscipline(): View
    {
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
        $draft = $request->draft();
        abort_if($draft->atingiuLimiteDeDisciplinas(), 422, 'O limite de três disciplinas foi atingido.');

        $draft->salvarDisciplinaRequerida($request->requiredDisciplineCode());
        $draft->adicionarDisciplina($request->validated(), $request->file('ementa'));
        $request->session()->forget('discipline_modal');

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina adicionada ao rascunho.');
    }

    public function editDiscipline(string $disciplineId): View
    {
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
        $draft = $request->draft();

        $draft->salvarDisciplinaRequerida($request->requiredDisciplineCode());
        $draft->atualizarDisciplina($disciplineId, $request->validated(), $request->file('ementa'));
        $request->session()->forget('discipline_modal');

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina atualizada no rascunho.');
    }

    public function destroyDiscipline(string $disciplineId): RedirectResponse
    {
        $this->currentDraft()->removerDisciplina($disciplineId);

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina removida do rascunho.');
    }

    /**
     * Função que persiste os dados de uma requisição de aproveitamento
     * na tabela de disciplinas e também na tabela de equivalência
     * @param StoreAproveitamentoRequest $request
     * @return RedirectResponse
     */
    public function store(StoreAproveitamentoRequest $request): RedirectResponse
    {
        $draft = $request->draft();
        $histories = $draft->armazenarHistoricos(
            $request->file('historicos', []),
            $request->file('historico_adicional')
        );
        $result = Aproveitamento::criarRequerimentoDoRascunho(
            $draft,
            $histories,
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
        $requisitions = Aproveitamento::requerimentosDoUsuario(Auth::id());

        return view('aproveitamentos.index', ['requisicoes' => $requisitions]);
    }

    /**
     * Exibe um pedido de aproveitamento do usuário autenticado.
     * @param int $group
     */
    public function show(int $group): View
    {
        $show_data = Aproveitamento::dadosDeExibicaoDoRequerimento($group, Auth::id());

        return view('aproveitamentos.show', ['show_data' => $show_data]);
    }

    /**
     * Exibe um PDF do requerimento no navegador.
     */
    public function showFile(int $group, int $arquivo): StreamedResponse
    {
        $file = Arquivo::doRequerimentoDoUsuarioOrFail($arquivo, $group, (int) Auth::id());

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
     * @param int $group
     * @return RedirectResponse
     */
    public function destroy(int $group): RedirectResponse
    {
        $req_name = Aproveitamento::removerRequerimentoDoUsuario($group, Auth::id());

        return redirect()
            ->back()
            ->with('alert-success', 'Requerimento de equivalência para '.$req_name.' removido com sucesso.');
    }

    private function currentDraft(): AproveitamentoRascunho
    {
        return AproveitamentoRascunho::atualDoUsuario((int) Auth::id());
    }
}
