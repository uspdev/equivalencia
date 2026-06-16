@extends('layouts.app')

@section('content')

  <div class="card aproveitamentos-automaticos-edit-scope">
    <x-page-header
      :breadcrumbs="[
          ['label' => 'Aproveitamentos automáticos', 'url' => route('equivalencias.index')],
          ['label' => $nomeCurso . ' (' . $codcur . '/' . $codhab . ')'],
      ]"
    >
      <x-slot:actions>
        {{-- Include when não funciona na extensão de ir para o arquivo --}}
        @if ($canManageEquivalencias)
          @include('aproveitamentos_automaticos.partials.buttons.toggle-edit-button-and-modal')
        @endif
      </x-slot:actions>
    </x-page-header>

    <div class="card-body">
      @if ($disciplinas->isEmpty())
        <p class="mb-0">Nenhuma disciplina requerida cadastrada.</p>
      @else
        <table id="equivalencias-table" class="table table-striped table-bordered datatable-simples dt-state-save dt-buttons">
          <thead>
            <tr>
              <th>Disciplina requerida</th>
              <th>Disciplinas cursadas (IES)</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($disciplinas as $disciplina)
              <tr>
                <td>@include('aproveitamentos_automaticos.partials.display.disciplina-requerida')</td>
                <td>@include('aproveitamentos_automaticos.partials.display.disciplinas-equivalentes')</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
@endsection

@push('styles')
  <style>
    .js-edit-only {
      display: none !important;
    }

    .equivalencias-edit-enabled .js-edit-only {
      display: inline-flex !important;
    }

    #equivalencias-table_wrapper .dataTables_filter {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0;
    }

    #equivalencias-table_wrapper .dataTables_info {
      display: inline-flex;
      align-items: center;
    }

    #equivalencias-table_wrapper .equivalencias-toggle-edit {
      margin-left: 0.5rem;
      white-space: nowrap;
    }
  </style>
@endpush

@push('scripts')
  <script>
    jQuery(function($) {
      var $scope = $('.aproveitamentos-automaticos-edit-scope');
      var saveStateUrl = @json(route('equivalencias.save-edit-mode-state'));
      var canManageEquivalencias = @json((bool) $canManageEquivalencias);
      var editModeEnabled = canManageEquivalencias ? @json((bool) $editModeEnabled) : false;

      if (!$scope.length) {
        return;
      }

      var persistEditModeState = function(enabled, $toggle) {
        $.ajax({
          url: saveStateUrl,
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}',
            enabled: enabled ? 1 : 0
          },
          error: function(xhr) {
            editModeEnabled = !enabled;
            applyEditModeState(editModeEnabled, $toggle);

            console.error('Erro ao salvar estado [' + xhr.status + ']:', xhr
              .responseText);

          }
        });
      };

      var applyEditModeState = function(enabled, $toggle) {
        $scope.toggleClass('equivalencias-edit-enabled', enabled);

        if ($toggle && $toggle.length) {
          var $textSpan = $toggle.find('.js-edit-toggle-text');
          var $textElement = $textSpan.length ? $textSpan : $toggle;
          $textElement.text(enabled ? 'Desabilitar edição' : 'Habilitar edição');
        }
      };

      applyEditModeState(editModeEnabled);

      var attachEditToggle = function() {
        if (!canManageEquivalencias) {
          applyEditModeState(false);

          return;
        }

        var $toggle = $('.equivalencias-toggle-edit');

        if (!$toggle.length) {
          return;
        }

        applyEditModeState(editModeEnabled, $toggle);

        $(document).off('click.equivalenciasToggleEdit', '.equivalencias-toggle-edit')
          .on('click.equivalenciasToggleEdit', '.equivalencias-toggle-edit', function(event) {
            event.preventDefault();

            editModeEnabled = !$scope.hasClass('equivalencias-edit-enabled');
            applyEditModeState(editModeEnabled, $toggle);
            persistEditModeState(editModeEnabled, $toggle);
          });
      };

      attachEditToggle();
    });
  </script>
@endpush
