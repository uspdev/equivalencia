@extends('laravel-usp-theme::master')

{{-- Blocos do laravel-usp-theme --}}
{{-- Ative ou desative cada bloco --}}

{{-- Target:card-header; class:card-header-sticky --}}
@include('laravel-usp-theme::blocos.sticky')

{{-- Target: button, a; class: btn-spinner, spinner --}}
@include('laravel-usp-theme::blocos.spinner')

{{-- Target: table; class: datatable-simples --}}
@include('laravel-usp-theme::blocos.datatable-simples')

{{-- Fim de blocos do laravel-usp-theme --}}

@section('title')
  @parent
@endsection

@section('styles')
  @parent
  <style>
    /* Rodapé sempre em baixo */
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    #skin_footer {
      /* flex-shrink -> ele não se redimensiona */
      flex-shrink: 0;
      margin-top: auto;
    }
  </style>
@endsection

@section('javascripts_bottom')
  @parent
  @stack('scripts')
  <script>
    // Seu código .js
  </script>
@endsection
