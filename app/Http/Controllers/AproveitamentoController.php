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
        $request = $request->input();

        $req_dis = new Disciplina();

        $req_dis->coddis = $request['coddis4'];
        $req_dis->nomdis = $request['disciplina4'];

        $req_dis->ies = 'USP';
    
        $req_dis->criado_por_id = $req_dis->alterado_por_id = Auth::user()->id;

        $req_dis->save();

        $eq_group = Equivalencia::proximoGrupo();

        for($i = 1; $i < 4; $i++)
        {
            $cur_dis = new Disciplina();
            if(!is_null($request['coddis' . $i]))
            {
                $cur_dis->coddis = $request['coddis' . $i];
                $cur_dis->nomdis = $request['disciplina' . $i];
                $cur_dis->creditos = $request['credit_dis' . $i];
                $cur_dis->carga_horaria = $request['cghr_dis' . $i];
                $cur_dis->ies = $request['unidade_ies'];
                $cur_dis->ano = $request['ano_dis' . $i];
                $cur_dis->semestre = (int)$request['semestre_dis' . $i];
                $cur_dis->frequencia = $request['freq_dis' . $i];
                $cur_dis->nota = $request['nota_dis' . $i];
                $cur_dis->criado_por_id = $cur_dis->alterado_por_id = Auth::user()->id;

                $cur_dis->save();

                $equivalencia = new Equivalencia();
                $equivalencia->requerida_id = $req_dis->id;
                $equivalencia->cursada_id = $cur_dis->id;

                $equivalencia->criado_por_id = $equivalencia->alterado_por_id = Auth::user()->id;
                $equivalencia->grupo = $eq_group;

                
                $equivalencia->save();        
            }
        }
    }
}
