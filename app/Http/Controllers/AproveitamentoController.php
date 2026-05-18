<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use App\Models\Equivalencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Uspdev\Forms\Form;
use Illuminate\Support\Facades\Validator;

class AproveitamentoController extends Controller
{
    // TODO - Fazer CRUD, index, show e etc.

    /**
     * Exibe o formulário para criação de uma requisição de equivalência,
     * gerando o html dinâmicamente a partir da biblioteca de formulários.
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $formHtml = app(Form::class)->generateHtml(config('app.initial_form'));
        return view('aproveitamentos.createReq',['formHtml' => $formHtml]);
    }

    /**
     * Gera o validator para os dados de entrada da requisição de equivalencia, com as regras especifícas
     * para este tipo de requisição
     * @param Request $request
     * @return \Illuminate\Validation\Validator
     */
    private static function validate_req(Request $request): Validator
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
            {$request->merge(['semestre_dis' . $i => (int)$request->input('semestre_dis' . $i)]);}
        }

        // Gera o validator
        $validator = static::validate_req($request);

        // Caso alguma falha seja encontrada, retorna com os erros
        if($validator->fails())
        {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // ID do usuário que fez a requisição
        $user_id = Auth::user()->id;

        // Dados de entrada
        $request = $request->input();

        // Próximo grupo de equivalências do banco de dados
        $eq_group = Equivalencia::proximoGrupo();
        
        // Salva a disciplina requerida no banco de dados de disciplinas e cria um objeto para referencia
        // TODO - Verificar se  disciplina já não existe no banco de dados (dado que essa é uma disciplina da USP), e fazer esse link ou criar o novo objeto
        $req_dis = Disciplina::create([
            'coddis' => $request['coddis4'],
            'nomdis' => $request['disciplina4'],
            'ies' => 'USP',
            'criado_por_id' => $user_id,
            'alterado_por_id' => $user_id,
        ]);

        // Atua em todas as disciplinas cursadas (de 1 à 3)
        for($i = 1; $i < 4; $i++)
        {
            // Verifica se a disciplina foi preenchida (coddis{$i} preenchido)
            if(!empty($request['coddis' . $i]))
            {
                // Salva a i-ésima disciplina no banco de dados de disciplinas e gera um objeto para referência
                $cur_dis = Disciplina::create([
                    'coddis' => $request['coddis' . $i],
                    'nomdis' => $request['disciplina' . $i],
                    'creditos' => $request['credit_dis' . $i],
                    'carga_horaria' => $request['cghr_dis' . $i],
                    'ies' => $request['unidade_ies'],
                    'ano' => $request['ano_dis' . $i],
                    'semestre' => $request['semestre_dis' . $i],
                    'frequencia' => $request['freq_dis' . $i],
                    'nota' => $request['nota_dis' . $i],
                    'criado_por_id' => $user_id,
                    'alterado_por_id' => $user_id,
                ]);

                // Salva a relação entre a i-ésima disciplina cursada e a disciplina requerida na tabela
                // de equivalências.
                Equivalencia::create([
                    'grupo' => $eq_group,
                    'requerida_id' => $req_dis->id,
                    'cursada_id' => $cur_dis->id,
                    'criado_por_id' => $user_id,
                    'alterado_por_id'=>  $user_id,
                ]);    
            }
        }

        // Redireciona para a rota de show do pedido de aproveitamento criado
        return redirect()->route('equivalencias.req-show', ['group' => $eq_group]);
    }

    /**
     * Função index, apresenta todas as requisições feitas pelo usuário
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        // Recupera o id do user
        $user_id = Auth::user()->id;

        // Busca na tabela de equivalências as requisições feitas pelo usuário
        // Retorna em formato de array, ordenado pelo tempo de criação, de forma decrescente (mais novo -> mais velho)
        $reqs = Equivalencia::where('criado_por_id', $user_id)->orderBy('created_at', 'desc')->get()->toArray();

        $curr_group = 0;    // Variável auxiliar
        $requisitions = []; //  Variável para exibição das requisições

        // Percorre todas as requisições encontradas na busca anterior
        foreach($reqs as $req)
        {
            // TODO - Verificar o que será necessário adicionar aqui para a exibição

            // Recupera o nome da disciplina pelo id
            $dis_name = Disciplina::where('id',$req['requerida_id'])->value('nomdis');

            // Verifica se o grupo da requisição atual é o que está sendo utilizado, senão o atribui
            if($curr_group != $req['grupo']){$curr_group = $req['grupo'];}

            // Verifica se existe já um registro do grupo atual, se não, o cria
            if(!isset($requisitions[$dis_name .'_gp' . $curr_group]))
            {
                $requisitions[$dis_name . '_gp'. $curr_group] = [
                    'nomdis' => $dis_name,
                    'estado' => $req['estado'],
                    'grupo' => $req['grupo'],
                ];
            }
            
        }

        // Retorna a view para exibição dos registros criados no loop acima
        return view('aproveitamentos.index',['requisicoes' => $requisitions]);
    }

    // TODO - implementar a função show

    /**
     * Função para exibição de um pedido de aproveitamento - Placeholder
     * @param int $group
     */
    public function show(int $group)
    {
        $eqs = Equivalencia::where('grupo', $group)->get()->toArray();
        $req_dis = Disciplina::where('id', $eqs[0]['requerida_id'])->firstOrFail();

        $show_data = [];
        $show_data['requerida'] = [
            'coddis' => $req_dis->coddis,
            'nomdis' => $req_dis->nomdis,
            'sglund' => $req_dis->sglund,
        ];

        foreach($eqs as $eq)
        {
            $cur_dis = Disciplina::where('id', $eq['cursada_id'])->firstOrFail();

            $show_data['cursadas'][] = [
                'coddis' => $cur_dis->coddis,
                'nomdis' => $cur_dis->nomdis,
                // 'ementa' => TODO - Encontrar maneira de exibir a ementa
                'semestre' => $cur_dis->semestre,
                'ano' => $cur_dis->ano,
                'freq' => $cur_dis->frequencia,
                'nota' => $cur_dis->nota,
                'creditos' => $cur_dis->creditos,
                'carga_hr' => $cur_dis->carga_horaria
            ];
        }

        return view('aproveitamentos.show', ['show_data' => $show_data]);
    }
}
