@extends('layouts.app')

@section('content')
    @php
        $workflowId = $workflowObjectData['workflowObject']->id;
        $breadcrumbLabel = $workflowId != 0 ? 'Requerimento #' . $workflowId : 'Novo requerimento';
    @endphp

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <x-page-header
            :breadcrumbs="[
                ['label' => 'Atendimentos', 'url' => route('workflows.atendimentos')],
                ['label' => $breadcrumbLabel],
            ]"
        />

        <div class="card-body">
            @include('show.partials.user-guidance')
            @include('show.partials.acoes-usuario')
            @include('show.partials.todas-transicoes-admin')
            @include('show.partials.formularios-transicao')
            @include('show.partials.transition-scripts')
        </div>

        <div class="card mt-2">
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-xl-8">
                        @include('show.partials.submissoes')
                    </div>
                    @include('show.partials.historico-estados')
                </div>
            </div>
        </div>
    </div>
@endsection
