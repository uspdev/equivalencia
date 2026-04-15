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
                @includeWhen($canManageEquivalencias, 'equivalencias.partials.toggle-edit-button-and-modal')
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
@endsection

@section('javascripts_bottom')
    @parent
    <script>
        jQuery(function($) {
            var $table = $('#equivalencias-table');

            if (!$table.length) {
                return;
            }

            var $card = $table.closest('.card');
            var wrapperSelector = '#' + $table.attr('id') + '_wrapper';
            var saveStateUrl = @json(route('equivalencias.save-edit-mode-state'));
            var canManageEquivalencias = @json((bool) $canManageEquivalencias);
            var editModeEnabled = canManageEquivalencias ? @json((bool) $editModeEnabled) : false;


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
                $card.toggleClass('equivalencias-edit-enabled', enabled);

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

                $toggle.on('click', function() {
                    editModeEnabled = !$card.hasClass('equivalencias-edit-enabled');
                    applyEditModeState(editModeEnabled, $toggle);
                    persistEditModeState(editModeEnabled, $toggle);
                });
            };

            attachEditToggle();
        });
    </script>
@endsection
