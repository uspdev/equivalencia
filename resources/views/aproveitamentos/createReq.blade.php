@extends('layouts.app')

@section('content')
  @php
    $externalDisciplines = $disciplines->where('unidade_tipo', 'OUTRA');
    $canSubmit =
        $draft->requerida_coddis &&
        $disciplines->isNotEmpty() &&
        $externalDisciplines->every(fn($discipline) => isset($transcripts[$discipline['id']]));
  @endphp

  <div class="card">
    <div class="card-header card-header-sticky">
      <h3 class="mb-0">Novo requerimento de aproveitamento de estudos</h3>
    </div>

    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <strong>Revise os dados do requerimento.</strong>
          <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="d-flex justify-content-between mb-4 text-center">
        <div class="flex-fill">
          <span class="badge badge-primary">1</span>
          <div>Disciplina desejada</div>
        </div>
        <div class="flex-fill">
          <span class="badge {{ $disciplines->isNotEmpty() ? 'badge-primary' : 'badge-secondary' }}">2</span>
          <div>Disciplinas cursadas</div>
        </div>
        <div class="flex-fill">
          <span class="badge {{ $canSubmit ? 'badge-primary' : 'badge-secondary' }}">3</span>
          <div>Revisão e envio</div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <strong>Disciplina para a qual deseja equivalência</strong>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('equivalencias.newreq-required') }}">
            @csrf
            @include('aproveitamentos.partials.disciplina-usp-field', [
                'name' => 'requerida_coddis',
                'id' => 'requerida_coddis',
                'label' => 'Disciplina com equivalência desejada',
                'selected' => old('requerida_coddis', $draft->requerida_coddis),
                'selectedName' => $requiredDisciplineName,
                'required' => true,
            ])
            <button type="submit" class="btn btn-primary">Salvar e continuar</button>
          </form>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <strong>Disciplinas cursadas</strong>
          @if ($disciplines->count() < 3)
            <a href="{{ route('equivalencias.newreq-discipline-create') }}"
              class="btn btn-sm btn-primary {{ $draft->requerida_coddis ? '' : 'disabled' }}"
              @if (!$draft->requerida_coddis) aria-disabled="true" tabindex="-1" @endif>
              Adicionar disciplina
            </a>
          @endif
        </div>
        <div class="card-body">
          @if (!$draft->requerida_coddis)
            <p class="text-muted mb-0">Salve primeiro a disciplina desejada.</p>
          @elseif ($disciplines->isEmpty())
            <p class="text-muted mb-0">Nenhuma disciplina adicionada. É necessário adicionar ao menos uma.</p>
          @else
            <div class="list-group">
              @foreach ($disciplines as $discipline)
                <div class="list-group-item d-flex align-items-center justify-content-between">
                  <a href="{{ route('equivalencias.newreq-discipline-edit', $discipline['id']) }}" class="flex-grow-1">
                    <strong>{{ $discipline['unidade_nome'] }}</strong>
                    <span class="ml-2">{{ $discipline['coddis'] }}</span>
                  </a>
                  <form method="POST" action="{{ route('equivalencias.newreq-discipline-destroy', $discipline['id']) }}"
                    onsubmit="return confirm('Remover esta disciplina do rascunho?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                  </form>
                </div>
              @endforeach
            </div>
            <small class="form-text text-muted mt-2">
              Clique em uma disciplina para revisar ou editar suas informações.
            </small>
          @endif
        </div>
      </div>

      @if ($externalDisciplines->isNotEmpty())
        <div class="card mb-4">
          <div class="card-header">
            <strong>Históricos escolares</strong>
          </div>
          <div class="card-body">
            <p>
              Envie um PDF para cada disciplina cursada fora da USP.
            </p>
            <form method="POST" action="{{ route('equivalencias.newreq-transcripts') }}" enctype="multipart/form-data">
              @csrf
              @foreach ($externalDisciplines as $discipline)
                <div class="form-group">
                  <label for="historico_{{ $discipline['id'] }}">
                    {{ $discipline['unidade_nome'] }} - {{ $discipline['coddis'] }}
                    <span class="text-danger">*</span>
                  </label>
                  @if (isset($transcripts[$discipline['id']]))
                    <div class="small text-success mb-1">
                      Arquivo atual: {{ $transcripts[$discipline['id']]['name'] }}
                    </div>
                  @endif
                  <input type="file" class="form-control-file" id="historico_{{ $discipline['id'] }}"
                    name="historicos[{{ $discipline['id'] }}]" accept=".pdf,application/pdf" @required(!isset($transcripts[$discipline['id']]))>
                </div>
              @endforeach
              <button type="submit" class="btn btn-primary">Salvar históricos</button>
            </form>
          </div>
        </div>
      @endif

      <div class="card">
        <div class="card-header">
          <strong>Revisão e envio</strong>
        </div>
        <div class="card-body">
          <p class="text-muted">
            O rascunho é salvo a cada etapa. Você pode sair desta página e continuar depois.
          </p>
          <form method="POST" action="{{ route('equivalencias.newreq-store') }}">
            @csrf
            <button type="submit" class="btn btn-success" @disabled(!$canSubmit)>
              Enviar requerimento
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
