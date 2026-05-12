<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Uspdev\Forms\Form;

class AproveitamentoController extends Controller
{
    // TODO - Fazer CRUD, index, show e etc.
    public function index()
    {
        $formHtml = app(Form::class)->generateHtml(config('app.initial_form'));
        return view('createReq',['formHtml' => $formHtml]);
    }
}
