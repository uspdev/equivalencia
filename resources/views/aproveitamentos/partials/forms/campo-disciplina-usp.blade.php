{{-- Renderiza um select AJAX para busca e seleção de disciplinas USP. --}}
@php
  $name = $name ?? 'coddis';
  $verdisName = $verdisName ?? 'verdis';
  $id = $id ?? $name;
  $label = $label ?? 'Disciplina USP';
  $selected = $selected ?? null;
  $selectedVerdis = $selectedVerdis ?? null;
  $selectedName = $selectedName ?? null;
  $required = $required ?? false;
  $disabled = $disabled ?? false;
  $showVersionSelector = $showVersionSelector ?? true;
  $class = trim('form-control disciplina-usp-select ' . ($class ?? ''));
  $verdisId = $id . '-verdis';
@endphp

<div class="form-group">
  <label for="{{ $id }}">
    {{ $label }}
    @if ($required)
      <span class="text-danger">*</span>
    @endif
  </label>
  <select id="{{ $id }}" name="{{ $name }}" class="{{ $class }}"
    data-search-url="{{ route('form.find.disciplina') }}"
    @if ($showVersionSelector) data-verdis-target="#{{ $verdisId }}" @endif
    data-versions-url="{{ route('equivalencias.disciplina-versoes') }}" @disabled($disabled) @required($required)>
    <option value="">Selecione uma disciplina...</option>
    @if ($selected)
      <option value="{{ $selected }}" selected>
        {{ $selected }}{{ $selectedName ? ' - ' . $selectedName : '' }}
      </option>
    @endif
  </select>
</div>

@if ($showVersionSelector)
  <div class="form-group">
    <label for="{{ $verdisId }}">
      Versão da disciplina
      @if ($required)
        <span class="text-danger">*</span>
      @endif
    </label>
    <select id="{{ $verdisId }}" name="{{ $verdisName }}" class="form-control disciplina-usp-verdis-select"
      data-selected-verdis="{{ $selectedVerdis }}" @disabled($disabled || !$selected) @required($required)>
      <option value="">Selecione uma versão...</option>
      @if ($selectedVerdis)
        <option value="{{ $selectedVerdis }}" selected>Versão {{ $selectedVerdis }}</option>
      @endif
    </select>
  </div>
@endif

@once
  @push('scripts')
    <script>
      (function(window, document) {
        var attempts = 0;
        var maxAttempts = 50;
        var initializedKey = 'disciplinaUspInitialized';

        function focusSearchField() {
          var field = document.querySelector('.select2-container--open .select2-search__field');
          if (field) {
            field.focus();
          }
        }

        function dispatchIdentityChanged(select) {
          select.dispatchEvent(new CustomEvent('disciplina-usp:identity', {
            bubbles: true
          }));
        }

        function populateVersionSelect(versionSelect, versions, selectedVerdis, disabled) {
          versionSelect.innerHTML = '<option value="">Selecione uma versão...</option>';

          versions.forEach(function(version) {
            var option = document.createElement('option');
            option.value = version.id;
            option.textContent = version.text;
            if (String(version.id) === String(selectedVerdis || '')) {
              option.selected = true;
            }
            versionSelect.appendChild(option);
          });

          versionSelect.disabled = Boolean(disabled) || versions.length === 0;
        }

        function loadVersions(select) {
          var versionTarget = select.getAttribute('data-verdis-target');
          var versionSelect = versionTarget ? document.querySelector(versionTarget) : null;
          var code = select.value || '';

          if (!versionSelect) {
            dispatchIdentityChanged(select);
            return;
          }

          if (!code) {
            populateVersionSelect(versionSelect, [], null, true);
            dispatchIdentityChanged(select);
            return;
          }

          var selectedVerdis = versionSelect.getAttribute('data-selected-verdis') || versionSelect.value;
          var url = select.getAttribute('data-versions-url') + '?coddis=' + encodeURIComponent(code);

          versionSelect.disabled = true;

          window.fetch(url, {
              headers: {
                'Accept': 'application/json'
              }
            })
            .then(function(response) {
              return response.ok ? response.json() : {
                results: []
              };
            })
            .then(function(response) {
              var versions = response && Array.isArray(response.results) ? response.results : [];
              populateVersionSelect(versionSelect, versions, selectedVerdis, select.disabled);
              versionSelect.setAttribute('data-selected-verdis', '');
              dispatchIdentityChanged(select);
            })
            .catch(function() {
              populateVersionSelect(versionSelect, [], null, true);
              dispatchIdentityChanged(select);
            });
        }

        function initialize() {
          attempts++;

          if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            if (attempts < maxAttempts) {
              window.setTimeout(scheduleInitialization, 100);
            }
            return;
          }

          var $ = window.jQuery;
          $('.disciplina-usp-select').each(function() {
            var $select = $(this);
            if ($select.data(initializedKey)) {
              return;
            }

            // Remove uma inicializacao anterior que nao possua a busca AJAX.
            if ($select.data('select2')) {
              $select.select2('destroy');
            }

            var $modal = $select.closest('.modal');
            var options = {
              ajax: {
                url: $select.attr('data-search-url'),
                dataType: 'json',
                delay: 500,
                data: function(params) {
                  return {
                    term: params.term || ''
                  };
                },
                processResults: function(response) {
                  return {
                    results: response && Array.isArray(response.results) ?
                      response.results :
                      []
                  };
                },
                cache: true
              },
              allowClear: true,
              placeholder: 'Digite o código da disciplina...',
              theme: 'bootstrap4',
              width: '100%',
              language: 'pt-BR'
            };

            if ($modal.length) {
              options.dropdownParent = $modal;
            }

            $select
              .select2(options)
              .data(initializedKey, true)
              .off('change.disciplinaUspVersion select2:select.disciplinaUspVersion select2:clear.disciplinaUspVersion')
              .on('select2:select.disciplinaUspVersion select2:clear.disciplinaUspVersion change.disciplinaUspVersion',
                function() {
                  loadVersions(this);
                })
              .off('change.disciplinaUspVersionSelect')
              .each(function() {
                var versionTarget = this.getAttribute('data-verdis-target');
                var versionSelect = versionTarget ? document.querySelector(versionTarget) : null;
                if (versionSelect) {
                  versionSelect.removeEventListener('change', versionSelect._disciplinaUspChangeHandler || function() {});
                  versionSelect._disciplinaUspChangeHandler = dispatchIdentityChanged.bind(null, this);
                  versionSelect.addEventListener('change', versionSelect._disciplinaUspChangeHandler);
                }
              })
              .off('select2:open.disciplinaUsp')
              .on('select2:open.disciplinaUsp', focusSearchField);

            loadVersions(this);
          });
        }

        function scheduleInitialization() {
          if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            attempts++;
            if (attempts < maxAttempts) {
              window.setTimeout(scheduleInitialization, 100);
            }
            return;
          }

          // O tema tambem agenda uma inicializacao global no jQuery.ready.
          // Rodar depois dela evita que o span gerado pelo Select2 seja
          // tratado como um novo campo sem configuracao AJAX.
          window.jQuery(function() {
            window.setTimeout(initialize, 0);
          });
        }

        scheduleInitialization();
      })
      (window, document);
    </script>
  @endpush
@endonce
