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
    public function create()
    {
        $formHtml = app(Form::class)->generateHtml(config('app.initial_form'));
        return view('createReq',['formHtml' => $formHtml]);
    }

    private static function validate_req(Request $request)
    {
        $rules = [
            'coddis4' => 'required|string|max:20',
            'disciplina4' => 'required|string|max:255',
            'unidade_ies' => 'required|string|max:255',
        ];

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

            $validator = Validator::make($request->all(), $rules);

        return $validator;
    }

    public function store(Request $request)
    {
        // dd($request);
        for($i = 1; $i < 4; $i++)
        {
            if(!is_null($request->input('semestre_dis' . $i)))
            {$request->merge(['semestre_dis' . $i => (int)$request->input('semestre_dis' . $i)]);}
        }

        $validator = static::validate_req($request);

        if($validator->fails())
        {
            return redirect()->back()->withErrors($validator)->withInput();
        }

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
            if(!empty($request['coddis' . $i]))
            {
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
