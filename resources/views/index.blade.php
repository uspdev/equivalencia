 @extends('layouts.app')

@section('content')
 <div class="container mt-5">
        <h1>Workflows</h1>

        <form action="{{ route('workflows.create-definition') }}" method="POST" class="mb-3">
            @csrf
            <button class="btn btn-primary">Criar Definição</button>
        </form>

        <form action="{{ route('workflows.list-definitions') }}" method="GET" class="mb-3">
            <button class="btn btn-secondary">Listar Definições</button>
        </form>

        <form action="{{ route('workflows.createObject') }}" method="POST" class="mb-3">
            @csrf
            <button class="btn btn-success">Criar Objeto</button>
        </form>

        <form action="{{ route('workflows.show-objects') }}" method="GET" class="mb-3">
            <button class="btn btn-info">Exibir Objetos de Definição</button>
        </form>

         <form action="{{ route('workflows.show-user-objects') }}" method="GET" class="mb-3">
            <button class="btn btn-danger">Exibir Objetos do user</button>
        </form>

    </div>
    @endsection
