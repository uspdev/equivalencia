```blade
{{-- Controla a alternância entre campos USP e campos de disciplina externa. --}}
@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.js-discipline-fields').forEach(function(container) {
          var unit = container.querySelector('.js-unit-type');
          var uspCode = container.querySelector('.disciplina-usp-select');
          var uspVersion = container.querySelector('.js-usp-code-group .disciplina-usp-verdis-select');
          var externalCode = container.querySelector('.js-external-code');
          var code = container.querySelector('.js-discipline-code');
          var version = container.querySelector('.js-discipline-version');
          var codtur = container.querySelector('.js-codtur-mask');
          var codturValue = container.querySelector('.js-codtur-value');
          var hasSyllabus = container.dataset.hasSyllabus === 'true';

          if (!unit || !uspCode || !externalCode || !code) {
            return;
          }

          // Aplica a máscara do código da turma e sincroniza o valor limpo no campo hidden.
          if (codtur && codturValue) {
            function syncCodtur() {
              var typedSlashAfterYear = /^\d{4}\/$/.test(codtur.value);
              var digits = codtur.value.replace(/\D/g, '').slice(0, 5);

              if (digits.length > 4) {
                digits = digits.slice(0, 4) + digits.slice(4, 5).replace(/[^12]/g, '');
              }

              codtur.value = digits.length > 4 || typedSlashAfterYear ?
                digits.slice(0, 4) + '/' + digits.slice(4) :
                digits;

              codturValue.value = digits;
            }

            codtur.addEventListener('input', syncCodtur);
            codtur.form.addEventListener('submit', syncCodtur);
            syncCodtur();
          } else if (codtur) {
            // Aplica apenas a máscara visual quando não existe campo hidden.
            codtur.addEventListener('input', function() {
              var typedSlashAfterYear = /^\d{4}\/$/.test(codtur.value);
              var digits = codtur.value.replace(/\D/g, '').slice(0, 5);

              codtur.value = digits.length > 4 || typedSlashAfterYear ?
                digits.slice(0, 4) + '/' + digits.slice(4) :
                digits;
            });
          }

          // Define o código e a versão que serão enviados no formulário.
          function syncCode() {
            code.value = unit.value === 'USP' ? uspCode.value : externalCode.value;

            if (version) {
              version.value = unit.value === 'USP' && uspVersion ? uspVersion.value : '';
            }
          }

          // Alterna exibição, obrigatoriedade e habilitação dos campos conforme o tipo da disciplina.
          function toggleFields() {
            var isExternal = unit.value === 'OUTRA';

            container.querySelector('.js-external-unit-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-usp-code-group').style.display = isExternal ? 'none' : '';
            container.querySelector('.js-external-code-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-external-name-group').style.display = isExternal ? '' : 'none';
            container.querySelector('.js-external-fields').style.display = isExternal ? '' : 'none';

            uspCode.required = !isExternal;
            uspCode.disabled = isExternal;

            if (uspVersion) {
              uspVersion.required = !isExternal;
              uspVersion.disabled = isExternal || !uspCode.value;
            }

            externalCode.required = isExternal;
            externalCode.disabled = !isExternal;

            container.querySelectorAll('.js-external-field').forEach(function(field) {
              field.disabled = !isExternal;
              field.required = isExternal &&
                !field.classList.contains('js-optional-external-field') &&
                (!field.classList.contains('js-syllabus-field') || !hasSyllabus);
            });

            syncCode();
          }

          unit.addEventListener('change', toggleFields);
          uspCode.addEventListener('change', syncCode);
          uspCode.addEventListener('disciplina-usp:identity', syncCode);

          if (uspVersion) {
            uspVersion.addEventListener('change', syncCode);
          }
          externalCode.addEventListener('input', syncCode);

          // Mantém a sincronização funcionando também com Select2.
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
```
