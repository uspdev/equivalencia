{{-- Exibe o cabeçalho da página de detalhes do requerimento. --}}
@php
  $disciplinaRequerida = $show_data['requerida']['coddis'] . ' - ' . $show_data['requerida']['nomdis'];
@endphp

<x-page-header
  :breadcrumbs="[
      ['label' => 'Meus requerimentos', 'url' => route('equivalencias.req-index')],
      ['label' => $disciplinaRequerida],
  ]"
/>
