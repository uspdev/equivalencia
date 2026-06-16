{{-- Renderiza a tabela com as disciplinas cursadas de um requerimento enviado. --}}
<div class="card card-header card-header-sticky mb-0">
  <div style="overflow-x: auto;">
    <table class="table mb-0 mt-0 text-center">
      <thead>
        <tr>
          <th class="text-info"><strong>Código</strong></th>
          <th class="text-warning"><strong>Nome</strong></th>
          <th class="text-secondary "><strong>Semestre</strong></th>
          <th class="text-danger "><strong>Ano</strong></th>
          <th class=""><strong>Frequência</strong></th>
          <th class=""><strong>Nota</strong></th>
          <th class=""><strong>Créditos</strong></th>
          <th class=""><span class="text-nowrap"><strong>Carga horária</strong></span></th>
          <th class=""><strong>IES</strong></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($show_data['cursadas'] as $cursada)
          @include('aproveitamentos.partials.display.disciplina-do-requerimento')
        @endforeach
      </tbody>
    </table>
  </div>
</div>
