<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use App\Models\Equivalencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Uspdev\Forms\Form;

class AproveitamentoController extends Controller
{
    // TODO - Fazer CRUD, index, show e etc.
    public function create()
    {
        $formHtml = app(Form::class)->generateHtml(config('app.initial_form'));
        return view('createReq',['formHtml' => $formHtml]);
    }

    public function store(Request $request)
    {

        $user_id = Auth::user()->id;
        $request = $request->input();

        $eq_group = Equivalencia::proximoGrupo();
        
        $req_dis = Disciplina::create([
            'coddis' => $request['coddis4'],
            'nomdis' => $request['disciplina4'],
            'ies' => 'USP',
            'criado_por_id' => $user_id,
            'alterado_por_id' => $user_id,
        ]);

        for($i = 1; $i < 4; $i++)
        {
            $cur_dis = new Disciplina();
            if(!is_null($request['coddis' . $i]))
            {

                $cur_dis = Disciplina::create([
                    'coddis' => $request['coddis' . $i],
                    'nomdis' => $request['disciplina' . $i],
                    'creditos' => $request['credit_dis' . $i],
                    'carga_horaria' => $request['cghr_dis' . $i],
                    'ies' => $request['unidade_ies'],
                    'ano' => $request['ano_dis' . $i],
                    'semestre' => (int)$request['semestre_dis' . $i],
                    'frequencia' => $request['freq_dis' . $i],
                    'nota' => $request['nota_dis' . $i],
                    'criado_por_id' => $cur_dis->alterado_por_id = $user_id,
                ]);

                Equivalencia::create([
                    'grupo' => $eq_group,
                    'requerida_id' => $req_dis->id,
                    'cursada_id' => $cur_dis->id,
                    'criado_por_id' => $user_id,
                    'alterado_por_id'=>  $user_id,
                ]);    
            }
        }
    }
}
