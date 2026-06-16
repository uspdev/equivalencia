{{--
  Componente reutilizável de cabeçalho de página com suporte a breadcrumbs
e ações customizadas.
  Recebe uma lista de breadcrumbs (ou utiliza o título como último item),
normaliza os dados para o formato esperado pela view e renderiza uma
navegação hierárquica acessível. Também permite exibir ações no cabeçalho
através do slot `$actions` e habilitar/desabilitar comportamento sticky.

Uso:
<x-page-header
  :breadcrumbs="[
        ['label' => 'Title1', 'url' => route('Title1.route' )],
        ['label' => 'Title2', 'url' => route('Title2.route' )],
        ['label' => $pageTitle]
]">

  <x-slot:actions>
    <div class="atendimentos-summary">
      <span class="badge badge-outline-primary mr-2">Action item 1</span>
      <span class="badge badge-outline-success">Action item 2</span>
    </div>
  </x-slot:actions>
</x-page-header>
/ --}}

@props([
    'title' => null,
    'breadcrumbs' => [],
    'sticky' => true,
])

@php
  // $breadcrumbs = ['Usuários', 'Editar'] -> collect(['Usuários', 'Editar']);
  $breadcrumbItems = collect($breadcrumbs ?: ($title ? [$title] : []))
      ->map(function ($item) {
          if (is_string($item)) {
              return ['label' => $item, 'url' => null];
          }

          return [
              'label' => $item['label'] ?? null,
              'url' => $item['url'] ?? null,
          ];
      })
      // label n pode ser null
      ->filter(fn($item) => filled($item['label']))
      ->values();

  $hasActions = isset($actions) && trim((string) $actions) !== '';
@endphp
<div {{ $attributes->class(['card-header', 'card-header-sticky' => $sticky, 'page-header']) }}>
  <div class="d-flex flex-column w-100">
    <div class="d-flex flex-column flex-md-row align-items-center w-100">
      @if ($breadcrumbItems->isNotEmpty())
        <nav class="min-w-0" aria-label="Navegação estrutural">
          <ol class="breadcrumb bg-transparent p-0 mb-0 page-header__breadcrumb-list">
            @foreach ($breadcrumbItems as $item)
              @php
                $isCurrent = $loop->last;
                $label = $item['label'];
                $url = $item['url'];
              @endphp

              <li class="breadcrumb-item {{ $isCurrent ? 'active' : '' }}"
                @if ($isCurrent) aria-current="page" @endif>
                @if ($url && !$isCurrent)
                  <a href="{{ $url }}">{{ $label }}</a>
                @else
                  {{ $label }}
                @endif
              </li>
            @endforeach
          </ol>
        </nav>
      @endif

      @if ($hasActions)
        <div class="d-flex flex-wrap align-items-center ml-md-3 page-header__actions">
          {{ $actions }}
        </div>
      @endif
    </div>
  </div>
</div>

@push('styles')
  <style>
    .page-header {
      background-color: #fff;
      border-bottom: 1px solid rgba(0, 0, 0, .125);
    }

    .page-header__breadcrumb-list {
      font-size: 1.5rem;
      font-weight: 500;
      line-height: 1.2;
    }

    .page-header .breadcrumb-item+.breadcrumb-item::before {
      color: #6c757d;
    }

    .page-header__actions {
      gap: .5rem;
    }

    .page-header__actions .equivalencias-toggle-edit {
      margin-left: 0 !important;
    }

    .min-w-0 {
      min-width: 0;
    }
  </style>
@endpush
