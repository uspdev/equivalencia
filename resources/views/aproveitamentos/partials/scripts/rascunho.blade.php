{{-- Sincroniza a disciplina desejada com os modais e reabre modais após validação. --}}
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

      if (!requiredDiscipline) {
        return;
      }

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
