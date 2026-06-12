<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAproveitamentoRequest;
use App\Models\Aproveitamento;
use Illuminate\Support\Facades\Auth;
use Uspdev\Forms\Form;
use Uspdev\Forms\Models\FormSubmission;
use Illuminate\Contracts\View\View;

class AproveitamentoController extends Controller
{
    // TODO - Fazer CRUD, index, show e etc.

    /**
     * Exibe o formulário para criação de uma requisição de equivalência,
     * gerando o html dinâmicamente a partir da biblioteca de formulários.
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        $formHtml = app(Form::class)->generateHtml(config('app.initial_form'));
        return view('aproveitamentos.createReq',['formHtml' => $formHtml]);
    }

    /**
     * Função que persiste os dados de uma requisição de aproveitamento
     * na tabela de disciplinas e também na tabela de equivalência
     * @param SaveAproveitamentoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SaveAproveitamentoRequest $request)
    {
        // Gera (ou recupera) a submissão do formulário e a salva no banco de dados
        $submission = (new Form(['editable' => true]))->handleSubmission($request);
        $data = Aproveitamento::criarRequerimentoDoFormulario(
            $request->input(),
            $submission->data,
            $request->user()->id
        );
    
        return redirect()->route('equivalencias.req-show', ['group' => $data['eq_group']])
            ->with('alert-success','Requerimento para '. $data['req_name'] . ' ' . $data['modo'] . ' com sucesso !');
    }

    /**
     * Função index, apresenta todas as requisições feitas pelo usuário
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        $requisitions = Aproveitamento::requerimentosDoUsuario(Auth::id());

        // Retorna a view para exibição dos registros criados no loop acima
        return view('aproveitamentos.index',['requisicoes' => $requisitions]);
    }

    /**
     * Função para exibição de um pedido de aproveitamento - Placeholder
     * @param int $group
     */
    public function show(int $group): View
    {
        $show_data = Aproveitamento::dadosDeExibicaoDoRequerimento($group, Auth::id());

        return view('aproveitamentos.show', ['show_data' => $show_data]);
    }

    /**
     * Remove um pedido de aproveitamento do banco de dados
     * @param int $group
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $group)
    {   
        $req_name = Aproveitamento::removerRequerimentoDoUsuario($group, Auth::id());

        return redirect()->back()->with('alert-success','Requerimento de equivalência para '. $req_name . ' removido com sucesso.');
    }

    /**
     * Função que possibilita a edição de uma requisição de equivalência
     * @param int $group
     * @return View
     */
    public function edit(int $group): View
    {
        $submission = $this->submissionFromGrupo($group);
        
        // Gera o html para a edição do formulário
        $formHtml = (new Form(['method' => 'PUT']))->generateHtml(config('app.initial_form'), $submission);

        return view('aproveitamentos.edit',['formHtml' => $formHtml, 'submission' => $submission]);
    }

    /**
     * Atualiza os registros de equivalencia de acordo com as modificações do usuário
     * @param SaveAproveitamentoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SaveAproveitamentoRequest $request, int $group)
    {
        // Processa a submissão do formulário para aproveitar a rotina de upload do pacote.
        $submission = (new Form(['editable' => true]))->handleSubmission($request);
        $data = Aproveitamento::atualizarRequerimentoDoFormulario(
            $group,
            $request->input(),
            $submission->data,
            $request->user()->id
        );

        if(empty($data))
        {
            return redirect()->back()->with('alert-danger', 'Nao há equivalências para a submissão editada');
        }

        return redirect()
            ->route('equivalencias.req-show', ['group' => $data['eq_group']])
            ->with('alert-success','Requerimento para '. $data['req_name'] . ' ' . $data['modo'] . ' com sucesso !');
    }

    private function submissionFromGrupo(int $group): FormSubmission
    {
        $submission = new FormSubmission();
        $submission->data = Aproveitamento::dadosParaFormularioDoRequerimento($group, Auth::id());

        return $submission;
    }
}
