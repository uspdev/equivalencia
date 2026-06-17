{{-- Renderiza o upload do histórico escolar exigido para o requerimento. --}}
@if ($canSubmit)
  <div class="card mb-4">
    <div class="card-header">
      <strong>Histórico Escolar</strong>
    </div>
    <div class="card-body">
      <p>
        Envie um único PDF de histórico escolar para este requerimento. O mesmo arquivo será utilizado para todas
        as disciplinas vinculadas, sejam elas USP ou externas.
      </p>
      <p id="historico-salvo" class="text-success mb-2 {{ $history ? '' : 'd-none' }}">
        Histórico escolar enviado: <strong>{{ $history?->nome }}</strong>
      </p>
      <p id="historico-ajuda" class="small text-muted {{ $history ? '' : 'd-none' }}">
        Envie outro arquivo somente se quiser substituir o histórico escolar.
      </p>
      <button type="button" id="historico-editar"
        class="btn btn-sm btn-outline-primary mb-3 {{ $history ? '' : 'd-none' }}">
        Editar arquivo
      </button>
      <div id="historico-input-wrapper" class="form-group {{ $history ? 'd-none' : '' }}">
        <label for="historico">
          Histórico escolar
          <span id="historico-obrigatorio" class="text-danger {{ $history ? 'd-none' : '' }}">
            *
          </span>
        </label>
        <input type="file" class="form-control-file" id="historico" name="historico" accept=".pdf,application/pdf"
          data-upload-url="{{ route('equivalencias.newreq-history') }}" @required(!$history)>
        <div id="historico-status" class="small mt-2" aria-live="polite"></div>
        <button type="button" id="historico-cancelar"
          class="btn btn-outline-danger btn-sm mt-2 {{ $history ? '' : 'd-none' }}">
          Cancelar
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('historico');

        if (!input || !window.fetch || !window.FormData) {
          return;
        }

        var form = input.closest('form');
        var status = document.getElementById('historico-status');
        var saved = document.getElementById('historico-salvo');
        var help = document.getElementById('historico-ajuda');
        var requiredMark = document.getElementById('historico-obrigatorio');
        var editButton = document.getElementById('historico-editar');
        var cancelButton = document.getElementById('historico-cancelar');
        var inputWrapper = document.getElementById('historico-input-wrapper');
        var submitButton = form ? form.querySelector('button[type="submit"]') : null;

        // Função auxiliar para atualizar a mensagem de status exibida abaixo do campo de input, com a classe de estilo apropriada
        function setStatus(message, className) {
          if (!status) {
            return;
          }

          status.className = 'small mt-2 ' + className;
          status.textContent = message;
        }

        // Desabilita o campo de input e o botão de submit para evitar múltiplos envios enquanto uma requisição está em andamento
        function setSubmittingState(disabled) {
          if (submitButton) {
            submitButton.disabled = disabled;
          }

          input.disabled = disabled;
        }

        // Exibe o estado "salvo", ocultando o input e os botões de ação relacionados, e mostrando a mensagem de ajuda
        function showSavedState() {
          inputWrapper.classList.add('d-none');
          editButton.classList.remove('d-none');
          cancelButton.classList.add('d-none');
          help.classList.remove('d-none');
          requiredMark.classList.add('d-none');
          input.required = false;
        }

        // Exibe o estado de edição, mostrando o input e os botões de ação relacionados, e ocultando a mensagem de ajuda
        function showEditState() {
          inputWrapper.classList.remove('d-none');
          editButton.classList.add('d-none');
          cancelButton.classList.remove('d-none');
          setStatus('', '');
          input.focus();
        }

        editButton.addEventListener('click', showEditState);
        cancelButton.addEventListener('click', function() {
          input.value = '';
          showSavedState();
        });

        input.addEventListener('change', function() {
          if (!input.files.length || !form) {
            return;
          }

          var data = new FormData();
          // Inclui o token CSRF para que a requisição seja autenticada e processada corretamente pelo Laravel
          data.append('_token', form.querySelector('input[name="_token"]').value);
          data.append('historico', input.files[0]);

          setSubmittingState(true);
          setStatus('Enviando histórico escolar...', 'text-muted');

          fetch(input.dataset.uploadUrl, {
              method: 'POST',
              body: data,
              headers: {
                'Accept': 'application/json',
              },
              credentials: 'same-origin',
            })
            .then(function(response) {
              return response.json().then(function(payload) {
                if (!response.ok) {
                  throw payload;
                }

                return payload;
              });
            })
            .then(function(payload) {
              input.value = '';
              saved.classList.remove('d-none');
              saved.querySelector('strong').textContent = payload.fileName;
              showSavedState();
              setStatus(payload.message, 'text-success');
            })
            .catch(function(error) {
              // Mensagem genérica de erro, caso a resposta não siga o formato esperado
              var message = error.message ||
                'Não foi possível enviar o histórico agora. O arquivo será enviado junto com o requerimento.';

              if (error.errors && error.errors.historico && error.errors.historico.length) {
                message = error.errors.historico[0];
              }

              setStatus(message, 'text-danger');
            })
            .finally(function() {
              setSubmittingState(false);
            });
        });
      });
    </script>
  @endpush
@endif
