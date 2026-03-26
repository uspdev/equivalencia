@extends('layouts.app')

@section('content')
<div class="container mt-3">
    <h2 class="mb-3">Nova disciplina requerida</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-body">
        {!! $formHtml !!}

        <div class="mt-3 d-flex gap-2">
            <a href="{{ route('equivalencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>
</div>
@endsection
