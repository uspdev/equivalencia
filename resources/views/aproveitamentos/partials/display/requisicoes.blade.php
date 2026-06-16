{{-- Exibe a tabela de requerimentos de aproveitamento do usuário. --}}
<div class="card">
  <table class="table mb-0">
    <thead>
      <tr>
        <th class="text-center">Disciplina requerida</th>
        <th class="text-center">Estado atual</th>
        <th class="text-center">Grupo</th>
        <th class="text-center">Ações</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($requisicoes as $reqinfo)
        @include('aproveitamentos.partials.display.requisicao')
      @endforeach
    </tbody>
  </table>
</div>
