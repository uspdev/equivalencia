{{-- Renderiza uma linha da tabela de disciplinas cursadas do requerimento. --}}
<tr>
  <td class="text-info ">{{ $cursada['coddis'] }}</td>
  <td class="text-warning ">{{ $cursada['nomdis'] }}</td>
  <td class="text-secondary ">{{ $cursada['semestre'] }}°</td>
  <td class="text-danger ">{{ $cursada['ano'] }}</td>
  <td class="">{{ $cursada['freq'] }}%</td>
  <td class="">{{ $cursada['nota'] }}</td>
  <td class="">{{ $cursada['creditos'] }}</td>
  <td class="">{{ $cursada['carga_hr'] }}</td>
  <td class="">{{ $cursada['ies'] }}</td>
</tr>
