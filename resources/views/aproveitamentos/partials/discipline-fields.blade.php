@php
    $unitType = old('unidade_tipo', $discipline['unidade_tipo'] ?? 'USP');
    $selectedCode = old('coddis', $discipline['coddis'] ?? null);
    $selectedName = old('nomdis', $discipline['nomdis'] ?? null);
@endphp

<div class="form-group">
    <label for="unidade_tipo">Unidade onde a disciplina foi cursada <span class="text-danger">*</span></label>
    <select class="form-control" id="unidade_tipo" name="unidade_tipo" required>
        <option value="USP" @selected($unitType === 'USP')>USP</option>
        <option value="OUTRA" @selected($unitType === 'OUTRA')>Outra</option>
    </select>
</div>

<div id="unidade_nome_group" class="form-group">
    <label for="unidade_nome">Nome da unidade ou instituição <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control"
           id="unidade_nome"
           name="unidade_nome"
           maxlength="255"
           value="{{ old('unidade_nome', $discipline['unidade_nome'] ?? '') }}">
</div>

<div id="codigo_usp_group">
    @include('aproveitamentos.partials.disciplina-usp-field', [
        'name' => 'coddis_usp',
        'id' => 'coddis_usp',
        'label' => 'Código da disciplina',
        'selected' => $unitType === 'USP' ? $selectedCode : null,
        'selectedName' => $unitType === 'USP' ? $selectedName : null,
        'required' => false,
    ])
</div>

<div id="codigo_externo_group" class="form-group">
    <label for="coddis_externo">Código da disciplina <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control"
           id="coddis_externo"
           maxlength="7"
           value="{{ $unitType === 'OUTRA' ? $selectedCode : '' }}">
</div>

<input type="hidden" id="coddis" name="coddis" value="{{ $selectedCode }}">

<div id="nome_disciplina_group" class="form-group">
    <label for="nomdis">Nome da disciplina <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control"
           id="nomdis"
           name="nomdis"
           maxlength="240"
           value="{{ old('nomdis', $discipline['nomdis'] ?? '') }}">
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="ano">Ano em que cursou <span class="text-danger">*</span></label>
        <input type="number"
               class="form-control"
               id="ano"
               name="ano"
               min="1900"
               max="{{ date('Y') }}"
               value="{{ old('ano', $discipline['ano'] ?? '') }}"
               required>
        <small class="form-text text-muted">
            Informe o ano do calendário, por exemplo 2025, e não o ano do curso.
        </small>
    </div>
    <div class="form-group col-md-6">
        <label for="semestre">Semestre em que cursou <span class="text-danger">*</span></label>
        <select class="form-control" id="semestre" name="semestre" required>
            <option value="">Selecione...</option>
            <option value="1" @selected((string) old('semestre', $discipline['semestre'] ?? '') === '1')>1º semestre</option>
            <option value="2" @selected((string) old('semestre', $discipline['semestre'] ?? '') === '2')>2º semestre</option>
        </select>
        <small class="form-text text-muted">
            Informe o semestre do calendário, e não o semestre atual do curso.
        </small>
    </div>
</div>

<div id="campos_externos">
    <div class="form-group">
        <label for="ementa">Ementa da disciplina <span class="text-danger">*</span></label>
        @if (isset($discipline['ementa']))
            <div class="small text-success mb-1">Arquivo atual: {{ $discipline['ementa']['name'] }}</div>
        @endif
        <input type="file"
               class="form-control-file"
               id="ementa"
               name="ementa"
               accept=".pdf,application/pdf">
    </div>

    <div class="form-row">
        <div class="form-group col-md-3">
            <label for="frequencia">Frequência (%) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="frequencia" name="frequencia"
                   min="0" max="100" step="0.01"
                   value="{{ old('frequencia', $discipline['frequencia'] ?? '') }}">
        </div>
        <div class="form-group col-md-3">
            <label for="nota">Nota <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="nota" name="nota"
                   min="0" max="10" step="0.01"
                   value="{{ old('nota', $discipline['nota'] ?? '') }}">
        </div>
        <div class="form-group col-md-3">
            <label for="creditos">Créditos <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="creditos" name="creditos"
                   min="1" step="1"
                   value="{{ old('creditos', $discipline['creditos'] ?? '') }}">
        </div>
        <div class="form-group col-md-3">
            <label for="carga_horaria">Carga horária <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="carga_horaria" name="carga_horaria"
                   min="1" step="1"
                   value="{{ old('carga_horaria', $discipline['carga_horaria'] ?? '') }}">
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var unit = document.getElementById('unidade_tipo');
        var uspCode = document.getElementById('coddis_usp');
        var externalCode = document.getElementById('coddis_externo');
        var code = document.getElementById('coddis');
        var externalFieldIds = [
            'unidade_nome', 'nomdis', 'ementa', 'frequencia', 'nota', 'creditos', 'carga_horaria'
        ];

        function syncCode() {
            code.value = unit.value === 'USP' ? uspCode.value : externalCode.value;
        }

        function toggleFields() {
            var isExternal = unit.value === 'OUTRA';
            var externalFields = externalFieldIds.map(function (id) {
                return document.getElementById(id);
            }).filter(Boolean);

            document.getElementById('unidade_nome_group').style.display = isExternal ? '' : 'none';
            document.getElementById('codigo_usp_group').style.display = isExternal ? 'none' : '';
            document.getElementById('codigo_externo_group').style.display = isExternal ? '' : 'none';
            document.getElementById('nome_disciplina_group').style.display = isExternal ? '' : 'none';
            document.getElementById('campos_externos').style.display = isExternal ? '' : 'none';

            uspCode.required = !isExternal;
            uspCode.disabled = isExternal;
            externalCode.required = isExternal;
            externalCode.disabled = !isExternal;
            externalFields.forEach(function (field) {
                field.disabled = !isExternal;
                field.required = isExternal && (field.id !== 'ementa' || !@json(isset($discipline['ementa'])));
            });
            syncCode();
        }

        function bindUspEvents(attempt) {
            if (window.jQuery) {
                window.jQuery(uspCode)
                    .off('.draftCodeSync')
                    .on('change.draftCodeSync select2:select.draftCodeSync select2:clear.draftCodeSync', syncCode);
                return;
            }

            if (attempt < 50) {
                window.setTimeout(function () {
                    bindUspEvents(attempt + 1);
                }, 100);
            }
        }

        unit.addEventListener('change', toggleFields);
        uspCode.addEventListener('change', syncCode);
        externalCode.addEventListener('input', syncCode);
        bindUspEvents(0);
        toggleFields();
    });
</script>
@endpush
