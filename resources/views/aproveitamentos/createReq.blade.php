@extends('layouts.app')

@section('content')
  @php
    $openDisciplineModal = session('discipline_modal');
    $selectedRequiredCode = $openDisciplineModal
        ? old('requerida_coddis', $draft->requerida_coddis)
        : $draft->requerida_coddis;
    $canSubmit =
        $draft->requerida_coddis &&
        $disciplines->isNotEmpty() &&
        $transcriptGroups->every(fn($group) => isset($group['file']));
  @endphp

  <div class="card">
    <div class="card-header card-header-sticky">
      <h3 class="mb-0">Novo requerimento de aproveitamento de estudos</h3>
    </div>

    <div class="card-body">
      @if ($errors->any() && !$openDisciplineModal)
        <div class="alert alert-danger">
          <strong>Revise os dados do requerimento.</strong>
          <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="card mb-4">
        <div class="card-header">
          <strong>Disciplina desejada</strong>
        </div>
        <div class="card-body">
          @include('aproveitamentos.partials.disciplina-usp-field', [
              'name' => 'requerida_coddis_selecionada',
              'id' => 'requerida_coddis',
              'label' => ' ',
              'selected' => $selectedRequiredCode,
              'selectedName' => $requiredDisciplineName,
              'required' => true,
          ])
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <strong>Disciplinas cursadas</strong>
          @if ($disciplines->count() < 3)
            <button type="button" id="add-discipline-button"
              class="btn btn-sm btn-primary {{ $selectedRequiredCode ? '' : 'disabled' }}" data-toggle="modal"
              data-target="#create-discipline-modal" @disabled(!$selectedRequiredCode)
              aria-disabled="{{ $selectedRequiredCode ? 'false' : 'true' }}">
              Adicionar disciplina
            </button>
          @endif
        </div>
        <div class="card-body">
          @if ($disciplines->isEmpty())
            <p class="text-muted mb-0">Nenhuma disciplina adicionada. É necessário adicionar ao menos uma.</p>
          @else
            <div class="list-group">
              @foreach ($disciplines as $discipline)
                <div class="list-group-item d-flex align-items-center justify-content-between">
                  <button type="button" class="btn btn-link flex-grow-1 p-0 text-left" data-toggle="modal"
                    data-target="#edit-discipline-modal-{{ $discipline['id'] }}">
                    <strong>{{ $discipline['unidade_nome'] }}</strong>
                    <span class="ml-2">{{ $discipline['coddis'] }}</span>
                  </button>
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

      @if ($transcriptGroups->isNotEmpty())
        <div class="card mb-4">
          <div class="card-header">
            <strong>Histórico Escolar</strong>
          </div>
          <div class="card-body">
            <p>
              Envie um PDF para cada unidade ou instituição externa. Disciplinas da mesma unidade utilizam o mesmo
              histórico.
            </p>
            <form method="POST" action="{{ route('equivalencias.newreq-transcripts') }}" enctype="multipart/form-data">
              @csrf
              @foreach ($transcriptGroups as $group)
                <div class="form-group">
                  <label for="historico_{{ $group['key'] }}">
                    {{ $group['unit_name'] }}
                    <span class="text-danger">*</span>
                  </label>
                  <div class="small text-muted mb-1">
                    Disciplinas:
                    {{ $group['disciplines']->map(fn($discipline) => $discipline['coddis'] . ' - ' . $discipline['nomdis'])->join('; ') }}
                  </div>
                  @if (isset($group['file']))
                    <div class="small text-success mb-1">
                      Arquivo atual: {{ $group['file']['name'] }}
                    </div>
                  @endif
                  <input type="file" class="form-control-file" id="historico_{{ $group['key'] }}"
                    name="historicos[{{ $group['key'] }}]" accept=".pdf,application/pdf" @required(!isset($group['file']))>
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

  @if ($disciplines->count() < 3)
    <div class="modal fade" id="create-discipline-modal" tabindex="-1" aria-labelledby="create-discipline-modal-label"
      aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="POST" action="{{ route('equivalencias.newreq-discipline-store') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" class="js-required-discipline-code" name="requerida_coddis"
              value="{{ $selectedRequiredCode }}">
            <div class="modal-header">
              <h5 class="modal-title" id="create-discipline-modal-label">Adicionar disciplina cursada</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              @if ($openDisciplineModal === 'create' && $errors->any())
                <div class="alert alert-danger">
                  <strong>Revise os dados da disciplina.</strong>
                  <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              @include('aproveitamentos.partials.discipline-fields', [
                  'discipline' => null,
                  'fieldPrefix' => 'create',
                  'useOldInput' => $openDisciplineModal === 'create',
              ])
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Salvar disciplina</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

  @foreach ($disciplines as $discipline)
    @php
      $editModalId = 'edit-discipline-modal-' . $discipline['id'];
      $editFieldPrefix = 'edit_' . str_replace('-', '_', $discipline['id']);
      $isOpenEditModal = $openDisciplineModal === $discipline['id'];
    @endphp
    <div class="modal fade" id="{{ $editModalId }}" tabindex="-1" aria-labelledby="{{ $editModalId }}-label"
      aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="POST" action="{{ route('equivalencias.newreq-discipline-update', $discipline['id']) }}"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" class="js-required-discipline-code" name="requerida_coddis"
              value="{{ $selectedRequiredCode }}">
            <div class="modal-header">
              <h5 class="modal-title" id="{{ $editModalId }}-label">Editar disciplina cursada</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              @if ($isOpenEditModal && $errors->any())
                <div class="alert alert-danger">
                  <strong>Revise os dados da disciplina.</strong>
                  <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              @include('aproveitamentos.partials.discipline-fields', [
                  'discipline' => $discipline,
                  'fieldPrefix' => $editFieldPrefix,
                  'useOldInput' => $isOpenEditModal,
              ])
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Salvar disciplina</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var requiredDiscipline = document.getElementById('requerida_coddis');
        var addButton = document.getElementById('add-discipline-button');
        var modalToOpen = @json(
            $openDisciplineModal === 'create'
                ? '#create-discipline-modal'
                : ($openDisciplineModal
                    ? '#edit-discipline-modal-' . $openDisciplineModal
                    : null));

        function syncRequiredDiscipline() {
          var code = requiredDiscipline.value;

          document.querySelectorAll('.js-required-discipline-code').forEach(function(field) {
            field.value = code;
          });

          if (addButton) {
            addButton.disabled = !code;
            addButton.classList.toggle('disabled', !code);
            addButton.setAttribute('aria-disabled', code ? 'false' : 'true');
          }
        }

        requiredDiscipline.addEventListener('change', syncRequiredDiscipline);
        if (window.jQuery) {
          window.jQuery(requiredDiscipline)
            .on('change.requiredDiscipline select2:select.requiredDiscipline select2:clear.requiredDiscipline',
              syncRequiredDiscipline);
        }

        document.querySelectorAll('[data-toggle="modal"]').forEach(function(button) {
          button.addEventListener('click', syncRequiredDiscipline);
        });

        syncRequiredDiscipline();

        if (modalToOpen && window.jQuery) {
          window.jQuery(modalToOpen).modal('show');
        }
      });
    </script>
  @endpush
@endsection
