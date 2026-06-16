{{-- Controla a alternância entre campos USP e campos de disciplina externa. --}}
@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.js-discipline-fields').forEach(function(container) {
          var unit = container.querySelector('.js-unit-type');
          var uspCode = container.querySelector('.disciplina-usp-select');
          var externalCode = container.querySelector('.js-external-code');
          var code = container.querySelector('.js-discipline-code');
          var hasSyllabus = container.dataset.hasSyllabus === 'true';

          if (!unit || !uspCode || !externalCode || !code) {
            return;
          }

          function syncCode() {
            code.value = unit.value === 'USP' ? uspCode.value : externalCode.value;
          }

          function toggleFields() {
            var isExternal = unit.value === 'OUTRA';

            container.querySelector('.js-external-unit-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-usp-code-group').style.display = isExternal ? 'none' : '';
            container.querySelector('.js-external-code-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-external-name-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-external-fields').style.display = isExternal ? '' : 'none';

            uspCode.required = !isExternal;
            uspCode.disabled = isExternal;
            externalCode.required = isExternal;
            externalCode.disabled = !isExternal;
            container.querySelectorAll('.js-external-field').forEach(function(field) {
              field.disabled = !isExternal;
              field.required = isExternal && (!field.classList.contains('js-syllabus-field') || !hasSyllabus);
            });
            syncCode();
          }

          unit.addEventListener('change', toggleFields);
          uspCode.addEventListener('change', syncCode);
          externalCode.addEventListener('input', syncCode);

          if (window.jQuery) {
            window.jQuery(uspCode)
              .off('.draftCodeSync')
              .on('change.draftCodeSync select2:select.draftCodeSync select2:clear.draftCodeSync', syncCode);
          }

          toggleFields();
        });
      });
    </script>
  @endpush
@endonce
