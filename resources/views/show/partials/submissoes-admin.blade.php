{{-- Seção de submissões de formulário visível apenas para administradores.
     Exibe detalhes completos de cada submissão incluindo conteúdo e arquivos. --}}
@can('admin')
    <div class="col-md-8">
        <h4 class="mb-3">Submissões de Formulário</h4>

        @forelse ($workflowObjectData['formSubmissions'] as $submissao)
            @php $nomeUsuario = \App\Models\User::find($submissao->user_id)?->name ?? 'Usuário não encontrado'; @endphp

            <div class="card mb-2">
                <div class="submission-details card-body">
                    <p>
                        <strong>Id do workflow:</strong> {{ $submissao->key }} |
                        <strong>ID da submissão:</strong> {{ $submissao->id }} |
                        <strong>Criado:</strong> {{ $submissao->created_at }} |
                    </p>
                    <p><strong>ID do formulário:</strong> {{ $submissao->form_definition_id }}</p>
                    <p><strong>Nome do usuário:</strong> {{ $nomeUsuario }}</p>

                    <h4>Conteúdo:</h4>
                    <p>
                        @foreach ($submissao->data as $key => $value)
                            @if ($key === 'arquivo')
                                <div class="d-flex">
                                    <div class="card d-flex justify-content-center align-items-center mb-1"
                                        style="width: 132px; height: 75px; overflow: hidden; background: #000; margin-right: 10px;">
                                        <a href="{{ asset('storage/' . $value['stored_path']) }}"
                                            target="_blank" download
                                            style="color: white; text-decoration: none;">
                                            <i class="fas fa-file-alt fa-2x"></i>
                                            <div style="font-size: 12px;">
                                                {{ strtoupper(pathinfo($value['original_name'], PATHINFO_EXTENSION)) }}
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <strong>{{ ucfirst($key) }}:</strong> {{ $value }} |
                            @endif
                        @endforeach
                    </p>
                </div>
            </div>
        @empty
            <div class="card mb-2">
                <div class="card-body">
                    <h4>Nenhuma submissão encontrada.</h4>
                </div>
            </div>
        @endforelse
    </div>
@endcan
