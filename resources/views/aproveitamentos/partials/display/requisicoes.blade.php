{{-- Exibe a tabela de requerimentos de aproveitamento do usuário --}}
<div class="card shadow-sm border-0">
  <div class="card-header bg-light">
    <h5 class="mb-0 font-weight-bold">
      Requerimentos de Aproveitamento
    </h5>
  </div>

  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="thead-light">
        <tr>
          <th class="text-center align-middle">
            Disciplina requerida
          </th>
          <th class="text-center align-middle" style="width: 180px;">
            Estado atual
          </th>
          <th class="text-center align-middle" style="width: 150px;">
            Ações
          </th>
        </tr>
      </thead>

      <tbody>
        @forelse ($requisicoes as $reqinfo)
          @include('aproveitamentos.partials.display.requisicao')
        @empty
          <tr>
            <td colspan="3" class="text-center text-muted py-4">
              Nenhum requerimento encontrado.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
