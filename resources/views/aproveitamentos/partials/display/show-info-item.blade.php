{{-- Exibe um par rótulo/valor usado nas telas de detalhe. --}}
<div class="h-100">
  <span class="d-block text-muted small mb-1">{{ $label }}</span>
  <span class="d-block font-weight-bold text-break">{{ filled($value) ? $value : ($fallback ?? 'Não informado') }}</span>
</div>
