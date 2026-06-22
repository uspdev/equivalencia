{{-- Renderiza o período no padrão do prefixo de codtur do Replicado. --}}
@php
  $codturDefault = $discipline['codtur'] ?? null;
  $codturValue = $value('codtur', $codturDefault);
  $codturMaskedValue = preg_replace('/^(\d{4})([12])$/', '$1/$2', (string) $codturValue);
@endphp

<div class="form-row">
  <div class="form-group col-md-12">
    <label for="{{ $fieldId('codtur') }}">Ano e semestre em que cursou <span class="text-danger">*</span></label>
    <small class="form-text text-muted">
      Informe o ano e semestre do calendário, por exemplo: "2025/1" se a disciplina foi cursada no 1º semestre de 2025.
    </small>
    <input type="hidden" class="js-codtur-value" name="codtur" value="{{ $codturValue }}">
    <input type="text" class="form-control js-codtur-mask" id="{{ $fieldId('codtur') }}" inputmode="numeric"
      autocomplete="off" maxlength="6" pattern="\d{4}/[12]" placeholder="2025/1" value="{{ $codturMaskedValue }}"
      required>
  </div>
</div>
