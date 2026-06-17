@extends('layouts.app')

@section('content')
  @php
    $disciplinaRequerida = $show_data['requerida']['coddis'] . ' - ' . $show_data['requerida']['nomdis'];
  @endphp

  <div class="card">
    <x-page-header
      :breadcrumbs="[
          ['label' => 'Meus requerimentos', 'url' => route('equivalencias.req-index')],
          ['label' => $disciplinaRequerida],
      ]"
    >
    </x-page-header>

    <div class="card-body">
      <div class="card mb-4">
        <div class="card-header">
          <strong>Dados do requerimento</strong>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
              <div class="text-muted small">Grupo</div>
              <strong>{{ $show_data['grupo'] }}</strong>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
              <div class="text-muted small">Data de criação</div>
              <strong>{{ $show_data['created_at']->format('d/m/Y H:i') }}</strong>
            </div>
            <div class="col-md-4">
              <div class="text-muted small">Status</div>
              <span class="badge badge-warning">{{ $show_data['estado'] ?: 'Enviado' }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <strong>Disciplina desejada</strong>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 mb-3 mb-md-0">
              <div class="text-muted small">Código</div>
              <strong>{{ $show_data['requerida']['coddis'] }}</strong>
            </div>
            <div class="col-md-6 mb-3 mb-md-0">
              <div class="text-muted small">Nome</div>
              <strong>{{ $show_data['requerida']['nomdis'] }}</strong>
            </div>
            <div class="col-md-3">
              <div class="text-muted small">Unidade</div>
              <strong>{{ $show_data['requerida']['sglund'] ?: 'Não informada' }}</strong>
            </div>
          </div>
        </div>
      </div>

      <h4 class="mb-3">Disciplinas cursadas</h4>
      @foreach ($show_data['cursadas'] as $cursada)
        @include('aproveitamentos.partials.show-disciplina', [
            'cursada' => $cursada,
            'group' => $show_data['grupo'],
            'position' => $loop->iteration,
        ])
      @endforeach

      <div class="card">
        <div class="card-header">
          <strong>Histórico escolar</strong>
        </div>
        <div class="card-body">
          @forelse ($show_data['historicos'] as $arquivo)
            @include('aproveitamentos.partials.show-arquivo', [
                'arquivo' => $arquivo,
                'group' => $show_data['grupo'],
            ])
          @empty
            <p class="text-muted mb-0">Nenhum histórico escolar foi enviado.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
@endsection
