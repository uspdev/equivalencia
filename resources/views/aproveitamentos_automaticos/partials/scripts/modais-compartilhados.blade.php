{{-- Scripts responsáveis por preencher os modais compartilhados da página.
     A ideia é reutilizar os mesmos modais para diferentes registros, mudando
     título, action, método HTTP e valores do formulário conforme o botão clicado.

     Também trata a recuperação dos dados antigos do formulário quando ocorre
     erro de validação no backend, reabrindo o modal correto com os valores
     que o usuário tinha informado. --}}
@push('scripts')
  <script>
    jQuery(function($) {
      // Dados preparados no Blade com as informações de cada modal.
      var modalData = @json($modalData);

      // Dados vindos do old(), usados quando o formulário volta com erro de validação.
      var recovery = {
        type: @json(old('_modal_type')),
        key: @json(old('_modal_key')),
        values: @json(old())
      };

      // Padroniza valores vazios para exibir "-" nos modais de visualização.
      function displayValue(value) {
        return value === null || value === undefined || value === '' ? '-' : String(value);
      }

      // Atualiza o texto de um elemento, se ele existir.
      function setText(container, selector, value) {
        var element = container.querySelector(selector);

        if (element) {
          element.textContent = displayValue(value);
        }
      }

      // Configura o método HTTP do formulário.
      // GET e POST são usados diretamente; PUT/PATCH/DELETE usam o _method do Laravel.
      function configureMethod(form, method) {
        var dynamicMethod = form.querySelector('[data-dynamic-method]');
        var normalizedMethod = String(method || 'POST').toUpperCase();

        form.method = normalizedMethod === 'GET' ? 'GET' : 'POST';

        if (!dynamicMethod) {
          return;
        }

        dynamicMethod.disabled = normalizedMethod === 'GET' || normalizedMethod === 'POST';
        dynamicMethod.value = dynamicMethod.disabled ? '' : normalizedMethod;
      }

      // Guarda no formulário qual registro está sendo editado/criado.
      // Esse valor permite reabrir o modal correto se houver erro de validação.
      function setContext(form, key) {
        var contextKey = form.querySelector('[name="_modal_key"]');

        if (contextKey) {
          contextKey.value = key;
        }
      }

      // Preenche o select de disciplina USP e, quando existir, também o select de versão.
      function setUspSelection(select, code, name, version) {
        if (!select) {
          return;
        }

        var versionTarget = select.getAttribute('data-verdis-target');
        var versionSelect = versionTarget ? document.querySelector(versionTarget) : null;
        var value = code ? String(code) : '';

        // Remove opções antigas, mantendo apenas a opção inicial.
        while (select.options.length > 1) {
          select.remove(1);
        }

        // Adiciona a disciplina selecionada manualmente, pois o select é carregado via AJAX.
        if (value) {
          var option = document.createElement('option');
          option.value = value;
          option.textContent = value + (name ? ' - ' + name : '');
          option.selected = true;
          select.appendChild(option);
        } else {
          select.value = '';
        }

        // Prepara o select de versões para a disciplina selecionada.
        if (versionSelect) {
          versionSelect.innerHTML = '<option value="">Selecione uma versão...</option>';
          versionSelect.setAttribute('data-selected-verdis', version || '');
          versionSelect.setAttribute('data-versions-loaded-for', '');

          if (version) {
            var versionOption = document.createElement('option');
            versionOption.value = String(version);
            versionOption.textContent = 'Versão ' + version;
            versionOption.selected = true;
            versionSelect.appendChild(versionOption);
          }
        }

        // Dispara o change para manter Select2 e scripts dependentes sincronizados.
        $(select).val(value || null).trigger('change');
      }

      // Configura o modal de criação/edição de disciplina requerida.
      function configureRequiredForm(key, oldValues) {
        var config = modalData.requiredForms[key];

        if (!config) {
          return false;
        }

        var modal = document.getElementById('modalFormularioDisciplinaRequerida');
        var form = document.getElementById('formDisciplinaRequeridaCompartilhado');
        var values = oldValues || config.values || {};
        var select = form.querySelector('[name="coddis"]');

        modal.querySelector('.modal-title').textContent = config.title;
        form.action = config.action;

        configureMethod(form, config.method);
        setContext(form, key);
        setUspSelection(select, values.coddis, values.nome_disciplina, values.verdis);

        return true;
      }

      // Retorna o valor de um campo, evitando null/undefined nos inputs.
      function valueFor(values, field) {
        return values[field] === null || values[field] === undefined ? '' : values[field];
      }

      // Converte valores vindos do backend/formulário para boolean.
      function isTruthy(value, defaultValue) {
        if (value === null || value === undefined || value === '') {
          return defaultValue;
        }

        return value === true || value === 1 || value === '1' || value === 'true';
      }

      // Verifica se um bloco adicional de equivalência possui algum valor preenchido.
      function blockHasValues(values, suffix) {
        return ['coddis', 'verdis', 'nome_disciplina', 'ies'].some(function(field) {
          return valueFor(values, field + suffix) !== '';
        });
      }

      // Define se a disciplina do bloco é USP.
      // Se não houver informação explícita, assume USP como padrão.
      function isUspValue(values, suffix) {
        var explicitValue = valueFor(values, 'is_usp' + suffix);
        var institution = valueFor(values, 'ies' + suffix);

        if (explicitValue !== '') {
          return isTruthy(explicitValue, true);
        }

        return institution !== '' ? institution === 'USP' : true;
      }

      // Configura o modal de criação/edição de equivalência.
      function configureEquivalenceForm(key, oldValues) {
        var config = modalData.equivalenceForms[key];

        if (!config) {
          return false;
        }

        var modal = document.getElementById('modalFormularioEquivalencia');
        var form = document.getElementById('formEquivalenciaCompartilhado');
        var values = oldValues || config.values || {};
        var suffixes = ['', '2', '3'];

        modal.querySelector('.modal-title').textContent = config.title;
        form.action = config.action;

        configureMethod(form, config.method);
        setContext(form, key);

        // Preenche os campos gerais da equivalência.
        ['numero_reuniao', 'data_reuniao', 'observacoes'].forEach(function(field) {
          var input = form.querySelector('[name="' + field + '"]');

          if (input) {
            input.value = valueFor(values, field);
          }
        });

        // Configura cada bloco de disciplina da equivalência.
        suffixes.forEach(function(suffix, index) {
          var block = form.querySelector('[data-equivalencia-block][data-index="' + (index + 1) + '"]');

          if (!block) {
            return;
          }

          var visible = index === 0 || blockHasValues(values, suffix);
          var checkbox = block.querySelector('[name="is_usp' + suffix + '"]');
          var isUsp = isUspValue(values, suffix);

          block.classList.toggle('d-none', !visible);

          if (checkbox) {
            checkbox.checked = isUsp;
          }

          // Campos manuais só recebem valor quando a disciplina não é USP.
          ['coddis', 'nome_disciplina', 'ies'].forEach(function(field) {
            var input = block.querySelector('.js-equivalencia-outra-fields [name="' + field + suffix + '"]');

            if (input) {
              input.value = isUsp ? '' : valueFor(values, field + suffix);
            }
          });
        });

        // Atualiza a visibilidade dos campos USP/outra instituição.
        form.dispatchEvent(new CustomEvent('equivalencia:refresh'));

        // Depois do refresh, preenche os selects USP dos blocos.
        suffixes.forEach(function(suffix, index) {
          var block = form.querySelector('[data-equivalencia-block][data-index="' + (index + 1) + '"]');
          var select = block ? block.querySelector('.disciplina-usp-select') : null;
          var isUsp = isUspValue(values, suffix);

          setUspSelection(
            select,
            isUsp ? valueFor(values, 'coddis' + suffix) : '',
            isUsp ? valueFor(values, 'nome_disciplina' + suffix) : '',
            isUsp ? valueFor(values, 'verdis' + suffix) : ''
          );
        });

        form.dispatchEvent(new CustomEvent('equivalencia:refresh'));

        return true;
      }

      // Modal apenas de visualização dos dados da disciplina.
      $('#modalDadosDisciplina').on('show.bs.modal', function(event) {
        var key = $(event.relatedTarget).data('modal-key');
        var details = modalData.details[key];

        if (!details) {
          event.preventDefault();
          return;
        }

        this.querySelector('.modal-title').textContent = details.title;
        this.querySelector('[data-detail-heading]').textContent = details.heading;

        ['code', 'institution', 'unit', 'classCredits', 'workCredits', 'workload', 'version'].forEach(function(field) {
          setText(this, '[data-detail-field="' + field + '"]', details[field]);
        }, this);

        var equivalenceContainer = this.querySelector('[data-equivalence-details]');
        equivalenceContainer.classList.toggle('d-none', !details.equivalence);

        ['meetingNumber', 'meetingDate', 'notes'].forEach(function(field) {
          setText(
            this,
            '[data-equivalence-field="' + field + '"]',
            details.equivalence ? details.equivalence[field] : null
          );
        }, this);
      });

      // Modal do formulário de disciplina requerida.
      $('#modalFormularioDisciplinaRequerida').on('show.bs.modal', function(event) {
        var key = event.relatedTarget ?
          $(event.relatedTarget).data('modal-key') :
          $(this).data('modal-key');

        var oldValues = recovery.type === 'required' && recovery.key === String(key) ?
          recovery.values :
          null;

        if (!configureRequiredForm(String(key), oldValues)) {
          event.preventDefault();
        }
      });

      // Modal do formulário de equivalência.
      $('#modalFormularioEquivalencia').on('show.bs.modal', function(event) {
        var key = event.relatedTarget ?
          $(event.relatedTarget).data('modal-key') :
          $(this).data('modal-key');

        var oldValues = recovery.type === 'equivalence' && recovery.key === String(key) ?
          recovery.values :
          null;

        if (!configureEquivalenceForm(String(key), oldValues)) {
          event.preventDefault();
        }
      });

      // Reabre o modal de disciplina requerida após erro de validação.
      if (recovery.type === 'required' && recovery.key) {
        $('#modalFormularioDisciplinaRequerida')
          .data('modal-key', recovery.key)
          .modal('show');
      }

      // Reabre o modal de equivalência após erro de validação.
      if (recovery.type === 'equivalence' && recovery.key) {
        $('#modalFormularioEquivalencia')
          .data('modal-key', recovery.key)
          .modal('show');
      }
    });
  </script>
@endpush
