{{-- Renderiza uma linha da tabela de requerimentos do usuário. --}}
<tr>
  <td class="text-center">
    <a href="{{ route('equivalencias.req-show', ['aproveitamento' => $reqinfo['id']]) }}">
      {{ $reqinfo['nomdis'] }}
    </a>
  </td>
  <td class="text-center">
    <span class="badge badge-warning">{{ $reqinfo['estado'] ?? 'Sem estado' }}</span>
  </td>
  <td class="text-center">
    <a href="{{ route('equivalencias.req-destroy', ['aproveitamento' => $reqinfo['id']]) }}" class="btn btn-sm btn-danger">
      Remover
    </a>
  </td>
</tr>
