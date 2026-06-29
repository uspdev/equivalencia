{{-- Form Equivalencia Scripts
Esses scripts controlam a exibição dos campos de equivalência,
alternando entre campos de disciplinas USP e de outras instituições,
e limitando o número de blocos de equivalência que podem ser exibidos.
--}}
@once
  @push('scripts')
    <script>
      (function(window, document) {
        function asArray(nodes) {
          return Array.prototype.slice.call(nodes);
        }

        function clearField(field) {
          if (field.type === 'checkbox') {
            field.checked = false;
            return;
          }

          field.value = '';

          if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && field.classList.contains(
              'disciplina-usp-select')) {
            window.jQuery(field).val(null).trigger('change');
          }
        }

        function setFieldsEnabled(container, enabled, required) {
          asArray(container.querySelectorAll('input, select, textarea')).forEach(function(field) {
            if (field.type === 'hidden') {
              return;
            }

            field.disabled = !enabled;
            field.required = Boolean(enabled && required && field.dataset.optional !== 'true');
          });
        }

        function syncBlock(block) {
          var visible = !block.classList.contains('d-none');
          var isUsp = Boolean(block.querySelector('.js-equivalencia-is-usp:checked'));
          var uspFields = block.querySelector('.js-equivalencia-usp-fields');
          var outraFields = block.querySelector('.js-equivalencia-outra-fields');

          uspFields.classList.toggle('d-none', !isUsp);
          outraFields.classList.toggle('d-none', isUsp);

          setFieldsEnabled(uspFields, visible && isUsp, true);
          setFieldsEnabled(outraFields, visible && !isUsp, true);
        }

        function visibleBlocks(form) {
          return asArray(form.querySelectorAll('[data-equivalencia-block]')).filter(function(block) {
            return !block.classList.contains('d-none');
          });
        }

        function syncForm(form) {
          var blocks = asArray(form.querySelectorAll('[data-equivalencia-block]'));
          var addButton = form.querySelector('.js-add-equivalencia');

          blocks.forEach(syncBlock);

          if (addButton) {
            addButton.disabled = visibleBlocks(form).length >= Number(form.dataset.maxDisciplinas || 3);
          }
        }

        function initializeForm(form) {
          syncForm(form);
          // Re-suncroniza o formulário quando um evento de refresh é disparado
          form.addEventListener('equivalencia:refresh', function() {
            syncForm(form);
          });

          form.addEventListener('change', function(event) {
            if (event.target.classList.contains('js-equivalencia-is-usp')) {
              syncBlock(event.target.closest('[data-equivalencia-block]'));
            }
          });

          form.addEventListener('click', function(event) {
            var addButton = event.target.closest('.js-add-equivalencia');
            var removeButton = event.target.closest('.js-remove-equivalencia');

            if (addButton) {
              var nextBlock = asArray(form.querySelectorAll('[data-equivalencia-block].d-none'))[0];
              if (nextBlock) {
                nextBlock.classList.remove('d-none');
                syncForm(form);
              }
            }

            if (removeButton) {
              var block = removeButton.closest('[data-equivalencia-block]');
              asArray(block.querySelectorAll('input, select, textarea')).forEach(clearField);
              block.classList.add('d-none');
              syncForm(form);
            }
          });
        }

        asArray(document.querySelectorAll('.equivalencia-filhas-form')).forEach(initializeForm);

      })
      (window, document);
    </script>
  @endpush
@endonce
