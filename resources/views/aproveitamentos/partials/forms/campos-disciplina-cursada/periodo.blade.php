{{-- Renderiza o período no padrão do prefixo de codtur do Replicado. --}}
@php
  $codturDefault = $discipline['codtur'] ?? null;
@endphp

<div class="form-row">
  <div class="form-group col-md-6">
    <label for="{{ $fieldId('codtur') }}">Ano e semestre em que cursou <span class="text-danger">*</span></label>
    <input type="text" class="form-control js-codtur-mask" id="{{ $fieldId('codtur') }}" name="codtur"
      inputmode="numeric" autocomplete="off" maxlength="5" pattern="\d{4}[12]"
      placeholder="20251" value="{{ $value('codtur', $codturDefault) }}" required>
  </div>
  <small class="form-text text-muted">
    Informe o ano e semestre do calendário, por exemplo: "20251" para 2025/1º semestre.
  </small>
</div>
