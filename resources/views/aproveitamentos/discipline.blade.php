@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header card-header-sticky">
        <h3 class="mb-0">{{ $discipline ? 'Editar disciplina cursada' : 'Adicionar disciplina cursada' }}</h3>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            @include('aproveitamentos.partials.discipline-fields', [
                'discipline' => $discipline,
            ])

            <div class="d-flex justify-content-between">
                <a href="{{ route('equivalencias.newreq-create') }}" class="btn btn-outline-secondary">
                    Voltar ao resumo
                </a>
                <button type="submit" class="btn btn-primary">Salvar disciplina</button>
            </div>
        </form>
    </div>
</div>
@endsection
