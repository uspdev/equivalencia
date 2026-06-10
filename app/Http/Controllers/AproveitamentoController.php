<?php

namespace App\Http\Controllers;

use App\Enums\EquivalenciaTipo;
use App\Models\Arquivo;
use App\Models\Disciplina;
use App\Models\Aproveitamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Uspdev\Forms\Form;
use Illuminate\Support\Facades\Validator;
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
     * Gera o validator para os dados de entrada da requisição de equivalencia, com as regras especifícas
     * para este tipo de requisição
     * @param Request $request
     * @return mixed
     */
    private static function validate_req(Request $request)
    {
        // Regras para a disciplina requerida
        $rules = [
            'coddis4' => 'required|string|max:20',
            'disciplina4' => 'required|string|max:255',
            'unidade_ies' => 'required|string|max:255',
        ];

        // Regras para as disciplinas cursadas (disciplina 1 à 3)
        for ($i = 1; $i < 4; $i++) 
        {
            $rules["coddis{$i}"] = 'nullable|string|max:20';
            $rules["disciplina{$i}"] = "nullable|required_with:coddis{$i}|string|max:255";
            $rules["credit_dis{$i}"] = "nullable|required_with:coddis{$i}|integer|min:0";
            $rules["cghr_dis{$i}"] = "nullable|required_with:coddis{$i}|integer|min:0";
            $rules["ano_dis{$i}"] = "nullable|required_with:coddis{$i}|integer|min:1900|max:" . date('Y');
            $rules["semestre_dis{$i}"] = "nullable|required_with:coddis{$i}|integer|in:1,2";
            $rules["freq_dis{$i}"] = "nullable|required_with:coddis{$i}|numeric|min:0|max:100";
            $rules["nota_dis{$i}"] = "nullable|required_with:coddis{$i}|numeric|min:0|max:10";
        }

        // Gera o validator baseado nas regras geradas acima
        $validator = Validator::make($request->all(), $rules);

        return $validator;
    }

    /**
     * Função auxiliar para criar registros de equivalência no banco de dados
     * @param Request $request
     * @return array{eq_group: int, modo: string, req_name: string|null}
     */
    private static function create_req(Request $request, FormSubmission $submission)
    {
        // ID do usuário que fez a requisição
        $user_id = $request->user()->id;

        // TODO - Ver o motivo da 'duplicação' do file e corrigir par não ter campo vazio
        $sub_data = $submission->data;

        // Dados de entrada
        $input = $request->input();

        // Próximo grupo de equivalências do banco de dados
        $eq_group = Aproveitamento::proximoGrupo();
        
        // Salva a disciplina requerida no banco de dados de disciplinas e cria um objeto para referencia
        $req_dis = Disciplina::create([
            'coddis' => $input['coddis4'],
            'nomdis' => $input['disciplina4'],
            'ies' => 'USP',
            'criado_por_id' => $user_id,
            'alterado_por_id' => $user_id,
        ]);

        // Atua em todas as disciplinas cursadas (de 1 à 3)
        for($i = 1; $i < 4; $i++)
        {
            // Verifica se a disciplina foi preenchida (coddis{$i} preenchido)
            if(!empty($input['coddis' . $i]))
            {
                // Salva a i-ésima disciplina no banco de dados de disciplinas e gera um objeto para referência
                $cur_dis = Disciplina::create([
                    'coddis' => $input['coddis' . $i],
                    'nomdis' => $input['disciplina' . $i],
                    'creditos' => $input['credit_dis' . $i],
                    'carga_horaria' => $input['cghr_dis' . $i],
                    'ies' => $input['unidade_ies'],
                    'ano' => $input['ano_dis' . $i],
                    'semestre' => $input['semestre_dis' . $i],
                    'frequencia' => $input['freq_dis' . $i],
                    'nota' => $input['nota_dis' . $i],
                    'criado_por_id' => $user_id,
                    'alterado_por_id' => $user_id,
                ]);

                // Salva a relação entre a i-ésima disciplina cursada e a disciplina requerida na tabela
                // de equivalências.
                $eq = Aproveitamento::create([
                    'grupo' => $eq_group,
                    'requerida_id' => $req_dis->id,
                    'cursada_id' => $cur_dis->id,
                    'tipo' => EquivalenciaTipo::REQUERIDA,
                    'criado_por_id' => $user_id,
                    'alterado_por_id'=>  $user_id,
                ]);

                // Registra o histórico escolar do aluno no primeiro registro de equivalência na tabela de arquivos
                if($i == 1)
                {
                    Arquivo::create([
                        'equivalencia_id' => $eq->id,
                        'tipo' => 'historico',
                        'nome' => $sub_data['hist_esc']['original_name'],
                        'path' => $sub_data['hist_esc']['stored_path']
                    ]);
                }

                // Registra o arquivos da ementa disciplina cursada na tabela de arquivos
                Arquivo::create([
                    'equivalencia_id' => $eq->id,
                    'tipo' => 'ementa',
                    'nome' => $sub_data['file_dis' .$i]['original_name'],
                    'path' => $sub_data['file_dis' .$i]['stored_path'],
                ]);
            }
        }

        return ['eq_group' => $eq_group, 'req_name' => $req_dis->nomdis, 'modo' => 'criado'];
    }

    /**
     * Função auxiliar para atualizar os registros existentes após mudanças feitas pelo usuário
     * @param Request $request
     * @param FormSubmission $submission
     * @return array{eq_group: int|mixed, modo: string, req_name: string|null|null}
     */
    private static function update_req(Request $request, FormSubmission $submission, int $group)
    {
        // ID do usuário que fez a requisição
        $user_id = $request->user()->id;

        // TODO - Ver o motivo da 'duplicação' do file e corrigir par não ter campo vazio
        $sub_data = $submission->data;

        // Recupera o grupo de equivalências do aluno autenticado.
        $eqs = Aproveitamento::query()
            ->where('grupo', $group)
            ->where('criado_por_id', $user_id)
            ->orderBy('id')
            ->get();

        if($eqs->isEmpty()){return [];}
        
        // Dados de entrada
        $input = $request->input();

        // Recupera a disciplina requerida do pedido de aproveitamento
        $req_dis = Disciplina::where('id',$eqs[0]->requerida_id)->firstOrFail();
        $eq_group = $eqs[0]->grupo;

        // Atualiza o registro da disciplina requerida
        $req_dis->update([
            'coddis' => $input['coddis4'],
            'nomdis' => $input['disciplina4'],
            'ies' => 'USP',
            'alterado_por_id' => $user_id,
        ]);

        // Percorre todos os registros de equivalencia (todas as disciplinas cursadas)
        for($i = 0; $i < sizeof($eqs); $i++)
        {
            // Recupera a disciplina cursada, e atualiza de acordo com as modificações
            $cur_dis = Disciplina::where('id', $eqs[$i]->cursada_id)->firstOrFail();
            $cur_dis->update([
                'coddis' => $input['coddis' . ($i + 1)],
                'nomdis' => $input['disciplina' . ($i + 1)],
                'creditos' => $input['credit_dis' . ($i + 1)],
                'carga_horaria' => $input['cghr_dis' . ($i + 1)],
                'ies' => $input['unidade_ies'],
                'ano' => $input['ano_dis' . ($i + 1)],
                'semestre' => $input['semestre_dis' . ($i + 1)],
                'frequencia' => $input['freq_dis' . ($i + 1)],
                'nota' => $input['nota_dis' . ($i + 1)],
                'alterado_por_id' => $user_id,
            ]);

            // Recupera os arquivos atrelados à um registro de equivalência
            $files = Arquivo::where('equivalencia_id', $eqs[$i]->id)->get();
            foreach($files as $file)
            {
                // Verifica se é histŕoico ou ementa e atualiza com os respectivos dados de entrada
                $field = '';
                if($file->tipo == 'historico')
                {
                    $field = 'hist_esc';
                }
                else{ $field = 'file_dis' . ($i + 1); }

                if(isset($sub_data[$field]))
                {
                    $file->update([
                        'nome' => $sub_data[$field]['original_name'],
                        'path' => $sub_data[$field]['stored_path'],
                    ]);
                }
            }
        }
        
        // Retorna um vetor com dados relevantes para o redirect
        return ['eq_group' => $eq_group, 'req_name' => $req_dis->nomdis, 'modo' => 'atualizado'];
    }

    /**
     * Função que persiste os dados de uma requisição de aproveitamento
     * na tabela de disciplinas e também na tabela de equivalência
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Transforma os semestres da disciplina cursada do formato ordinal (1° ou 2°) para inteiro (1 ou 2)
        for($i = 1; $i < 4; $i++)
        {
            if(!is_null($request->input('semestre_dis' . $i)))
            { $request->merge(['semestre_dis' . $i => (int)$request->input('semestre_dis' . $i)]); }
        }

        // Gera o validator
        $validator = static::validate_req($request);
        
        // Caso alguma falha seja encontrada, retorna com os erros
        if($validator->fails()) { return redirect()->back()->withErrors($validator)->withInput(); }


        // Gera (ou recupera) a submissão do formulário e a salva no banco de dados
        $submission = (new Form(['editable' => true]))->handleSubmission($request);
    
        $data = self::create_req($request, $submission);
    
        if(!isset($data))
        { 
            return redirect()->back()->with('alert-danger', 'Nao há equivalências para a submissão editada'); 
        }
        else
        {        
            // Redireciona para a rota de show do pedido de aproveitamento criado
            return redirect()->route('equivalencias.req-show', ['group' => $data['eq_group']])->with('alert-success','Requerimento para '. $data['req_name'] . ' ' . $data['modo'] . ' com sucesso !');
        }

    }

    /**
     * Função index, apresenta todas as requisições feitas pelo usuário
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        // Recupera o id do user
        $user_id = Auth::user()->id;

        // Busca as solicitações feitas pelo aluno e agrupa por grupo.
        $requisitions = Aproveitamento::query()
            ->where('criado_por_id', $user_id)
            ->with('requerida')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('grupo')
            ->map(function ($equivalenciasDoGrupo, $grupo) {
                $primeiraEquivalencia = $equivalenciasDoGrupo->first();

                return [
                    'nomdis' => $primeiraEquivalencia->requerida?->nomdis,
                    'estado' => $primeiraEquivalencia->estado,
                    'grupo' => (int) $grupo,
                ];
            })
            ->all();

        // Retorna a view para exibição dos registros criados no loop acima
        return view('aproveitamentos.index',['requisicoes' => $requisitions]);
    }

    // TODO - implementar a função show

    /**
     * Função para exibição de um pedido de aproveitamento - Placeholder
     * @param int $group
     */
    public function show(int $group): View
    {
        $user_id = Auth::id();

        // Recupera os registros de equivalencia e a a disciplina requerida
        $eqs = Aproveitamento::query()
            ->where('grupo', $group)
            ->where('criado_por_id', $user_id)
            ->orderBy('id')
            ->get()
            ->toArray();

        abort_if(empty($eqs), 404);

        $req_dis = Disciplina::where('id', $eqs[0]['requerida_id'])->firstOrFail();
        
        // Array que armazena os dados a serem exibidos, divido em disciplina requerida e cursadas
        $show_data = [];
        $show_data['requerida'] = [
            'coddis' => $req_dis->coddis,
            'nomdis' => $req_dis->nomdis,
            'sglund' => $req_dis->sglund,
        ];

        // Percorre todos os registros de equivalência atrelados ao grupo
        foreach($eqs as $eq)
        {
            // Recupera a disciplina cursada neste registro
            $cur_dis = Disciplina::where('id', $eq['cursada_id'])->firstOrFail();

            // Recupera o arquivo atrelado àquele registro
            $file = Arquivo::where('equivalencia_id',$eq['id'])->firstOrFail();

            // Adiciona as informaçẽos da disciplina cursada ao array
            $show_data['cursadas'][] = [
                'coddis' => $cur_dis->coddis,
                'nomdis' => $cur_dis->nomdis,
                'ementa_file' => [
                    'name' => $file->nome,
                    'path' => $file->path
                ],
                'semestre' => $cur_dis->semestre,
                'ano' => $cur_dis->ano,
                'freq' => $cur_dis->frequencia,
                'nota' => $cur_dis->nota,
                'creditos' => $cur_dis->creditos,
                'carga_hr' => $cur_dis->carga_horaria,
                'ies' => $cur_dis->ies
            ];
        }

        return view('aproveitamentos.show', ['show_data' => $show_data]);
    }

    /**
     * Remove um pedido de aproveitamento do banco de dados
     * @param int $group
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $group)
    {   
        $user_id = Auth::id();

        // Busca todos os registros de equivalencia do mesmo grupo (mesmo requerimento)
        $eqs = Aproveitamento::query()
            ->where('grupo', $group)
            ->where('criado_por_id', $user_id)
            ->get();

        abort_if($eqs->isEmpty(), 404);

        // Recupera a disciplina requerida
        $req_dis = Disciplina::where('id', $eqs[0]->requerida_id)->firstOrFail();

        $req_name = $req_dis->nomdis;   // Guarda o nome da disciplina requerida
        
        // Percorre todos os registros de equivalencia do grupo
        foreach($eqs as $eq)
        {
            // Remove o registro de cada disciplina cursada, e por consequência, o registro de equivalência
            $cur_dis = Disciplina::find($eq->cursada_id);
            if($cur_dis){$cur_dis->delete();}
        }

        // Remove a disciplina requerida
        $req_dis->delete();

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
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, int $group)
    {
        // Transforma os semestres da disciplina cursada do formato ordinal (1° ou 2°) para inteiro (1 ou 2)
        for($i = 1; $i < 4; $i++)
        {
            if(!is_null($request->input('semestre_dis' . $i)))
            { $request->merge(['semestre_dis' . $i => (int)$request->input('semestre_dis' . $i)]); }
        }

        // Gera o validator
        $validator = static::validate_req($request);

        // Caso alguma falha seja encontrada, retorna com os erros
        if($validator->fails()) { return redirect()->back()->withErrors($validator)->withInput(); }

        // Processa a submissão do formulário para aproveitar a rotina de upload do pacote.
        $submission = (new Form(['editable' => true]))->handleSubmission($request);
        $data = self::update_req($request, $submission, $group);

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
        $user_id = Auth::id();

        $eqs = Aproveitamento::query()
            ->where('grupo', $group)
            ->where('criado_por_id', $user_id)
            ->with(['requerida', 'cursada', 'arquivos'])
            ->orderBy('id')
            ->get();

        abort_if($eqs->isEmpty(), 404);

        $primeiraEquivalencia = $eqs->first();
        $data = [
            'unidade_ies' => $primeiraEquivalencia->cursada?->ies,
            'coddis4' => $primeiraEquivalencia->requerida?->coddis,
            'disciplina4' => $primeiraEquivalencia->requerida?->nomdis,
        ];

        foreach ($eqs as $index => $equivalencia) {
            $numeroDisciplina = $index + 1;
            $cursada = $equivalencia->cursada;

            $data["coddis{$numeroDisciplina}"] = $cursada?->coddis;
            $data["disciplina{$numeroDisciplina}"] = $cursada?->nomdis;
            $data["credit_dis{$numeroDisciplina}"] = $cursada?->creditos;
            $data["cghr_dis{$numeroDisciplina}"] = $cursada?->carga_horaria;
            $data["ano_dis{$numeroDisciplina}"] = $cursada?->ano;
            $data["semestre_dis{$numeroDisciplina}"] = match ((int) $cursada?->semestre) {
                1 => '1°',
                2 => '2°',
                default => null,
            };
            $data["freq_dis{$numeroDisciplina}"] = $cursada?->frequencia;
            $data["nota_dis{$numeroDisciplina}"] = $cursada?->nota;

            foreach ($equivalencia->arquivos as $arquivo) {
                if ($arquivo->tipo === 'historico') {
                    $data['hist_esc'] = [
                        'original_name' => $arquivo->nome,
                        'stored_path' => $arquivo->path,
                    ];

                    continue;
                }

                if ($arquivo->tipo === 'ementa') {
                    $data["file_dis{$numeroDisciplina}"] = [
                        'original_name' => $arquivo->nome,
                        'stored_path' => $arquivo->path,
                    ];
                }
            }
        }

        $submission = new FormSubmission();
        $submission->data = $data;

        return $submission;
    }
}
