{{-- Exibe o cabeçalho da página de detalhes do requerimento. --}}
@php
  $disciplinaRequerida =
      $show_data['requerida']['coddis'] .
      (!empty($show_data['requerida']['verdis']) ? ' v' . $show_data['requerida']['verdis'] : '') .
      ' - ' .
      $show_data['requerida']['nomdis'];
@endphp

<x-page-header
  :breadcrumbs="[
      ['label' => 'Meus requerimentos', 'url' => route('equivalencias.req-index')],
      ['label' => $disciplinaRequerida],
  ]"
>
  <x-slot:actions>
    <span class="badge badge-warning">{{ $show_data['estado'] ?: 'Enviado' }}</span>
  </x-slot:actions>
</x-page-header>
