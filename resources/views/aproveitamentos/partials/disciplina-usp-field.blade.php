@php
  $name = $name ?? 'coddis';
  $id = $id ?? $name;
  $label = $label ?? 'Disciplina USP';
  $selected = $selected ?? null;
  $selectedName = $selectedName ?? null;
  $required = $required ?? false;
  $disabled = $disabled ?? false;
  $class = trim('form-control disciplina-usp-select ' . ($class ?? ''));
@endphp

<div class="form-group">
  <label for="{{ $id }}">
    {{ $label }}
    @if ($required)
      <span class="text-danger">*</span>
    @endif
  </label>
  <select id="{{ $id }}" name="{{ $name }}" class="{{ $class }}"
    data-search-url="{{ route('form.find.disciplina') }}" @disabled($disabled) @required($required)>
    <option value="">Selecione uma disciplina...</option>
    @if ($selected)
      <option value="{{ $selected }}" selected>
        {{ $selected }}{{ $selectedName ? ' - ' . $selectedName : '' }}
      </option>
    @endif
  </select>
</div>

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
              minimumInputLength: 3,
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
              .off('select2:open.disciplinaUsp')
              .on('select2:open.disciplinaUsp', focusSearchField);
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
