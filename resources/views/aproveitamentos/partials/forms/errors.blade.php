{{-- Exibe mensagens de validação com título opcional. --}}
@php
  $show = $show ?? $errors->any();
  $title = $title ?? null;
@endphp

@if ($show)
  <div class="alert alert-danger">
    @if ($title)
      <strong>{{ $title }}</strong>
    @endif
    <ul class="mb-0 {{ $title ? 'mt-2' : '' }}">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
