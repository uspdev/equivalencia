{{-- Renderiza uma linha da tabela de requerimentos do usuário. --}}
<tr>
  <td class="text-center">
    <a href="{{ route('equivalencias.req-show', ['group' => $reqinfo['grupo']]) }}">
      {{ $reqinfo['nomdis'] }}
    </a>
  </td>
  <td class="text-center">
    <span class="badge badge-warning">{{ $reqinfo['estado'] ?? 'PLACEHOLDERS_NULO' }}</span>
  </td>
  <td class="text-center">{{ $reqinfo['grupo'] }}</td>
  <td class="text-center">
    <a href="{{ route('equivalencias.req-destroy', ['group' => $reqinfo['grupo']]) }}" class="btn btn-sm btn-danger">
      Remover
    </a>
  </td>
</tr>
