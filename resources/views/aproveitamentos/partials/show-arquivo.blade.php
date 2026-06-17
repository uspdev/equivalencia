<div class="d-flex align-items-center border rounded p-2 mb-2">
  <i class="fas fa-file-alt text-muted mr-2" aria-hidden="true"></i>
  <a href="{{ route('equivalencias.req-file', ['group' => $group, 'arquivo' => $arquivo['id']]) }}"
    target="_blank" rel="noopener" class="text-break">
    {{ $arquivo['name'] }}
  </a>
</div>
