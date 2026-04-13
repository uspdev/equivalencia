@extends('layouts.app')

@section('content')

  <div class="card">
    <div class="card-header d-flex align-items-center">
        <h4 class="mb-0">
            <a href="{{ route('equivalencias.index') }}">Cursos</a>
            <i class="fas fa-angle-right mx-2"></i>
            {{ $nomeCurso }} ({{ $codcur }}/{{ $codhab }})
        </h4>

        <div class="pt-2">
          @include('equivalencias.partials.modal-create')
        </div>
    </div>

    <div class="card-body">
      @if ($disciplinas->isEmpty())
        <p class="mb-0">Nenhuma disciplina requerida cadastrada.</p>
      @else
        <table id="equivalencias-table" class="table table-striped table-bordered datatable-simples dt-state-save">
          <thead>
            <tr>
              <th>Disciplina requerida</th>
              <th>Disciplinas cursadas (IES)</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($disciplinas as $disciplina)
              <tr>
                <td>
                  @include('equivalencias.partials.disciplina-requerida')
                </td>
                <td>
                  @include('equivalencias.partials.disciplinas-equivalentes')
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
@endsection

@section('styles')
  @parent
  <style>
    #equivalencias-table .js-edit-only {
      display: none !important;
    }

    .equivalencias-edit-enabled #equivalencias-table .js-edit-only {
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
@endsection

@section('javascripts_bottom')
  @parent
  <script>
    jQuery(function($) {
      var $table = $('#equivalencias-table');

      if (! $table.length) {
        return;
      }

      var $card = $table.closest('.card');
      var wrapperSelector = '#' + $table.attr('id') + '_wrapper';

      var attachEditToggle = function(retries) {
        var $wrapper = $(wrapperSelector);
        var $info = $wrapper.find('.dataTables_info');
        var $infoContainer = $info.closest('.border.rounded.border-info');

        if (! $wrapper.length || ! $info.length || ! $infoContainer.length) {
          if (retries > 0) {
            setTimeout(function() {
              attachEditToggle(retries - 1);
            }, 100);
          }
          return;
        }

        if ($wrapper.find('.equivalencias-toggle-edit').length) {
          return;
        }

        var $toggle = $('<button>', {
          type: 'button',
          class: 'btn btn-sm btn-outline-primary equivalencias-toggle-edit ml-2',
          text: 'Habilitar edição'
        });

        $toggle.on('click', function() {
          var enabled = ! $card.hasClass('equivalencias-edit-enabled');
          $card.toggleClass('equivalencias-edit-enabled', enabled);
          $toggle.text(enabled ? 'Desabilitar edição' : 'Habilitar edição');
        });

        $toggle.insertAfter($infoContainer);
      };

      attachEditToggle(20);
    });
  </script>
@endsection
