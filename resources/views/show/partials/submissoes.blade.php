{{-- Seção de submissões de formulário. --}}

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Submissões de Formulário</h4>
    <span class="badge bg-secondary fs-6">
        {{ count($workflowObjectData['formSubmissions']) }}
        {{ count($workflowObjectData['formSubmissions']) === 1 ? 'submissão' : 'submissões' }}
    </span>
</div>
@forelse (collect($workflowObjectData['formSubmissions'])->reverse() as $submissao)
    @php
        $nomeUsuario = \App\Models\User::find($submissao->user_id)?->name ?? 'Usuário não encontrado';
        $dadosSubmissao = collect($submissao->data ?? []);
        $arquivo = $dadosSubmissao->get('arquivo');
        $arquivo = is_array($arquivo) ? $arquivo : null;
        $campos = $dadosSubmissao->except('arquivo');
        $camposApenasAdmin = ['place', 'definition_name', 'transition'];
        $camposVisiveisUsuario = ['coddis', 'obs'];
    @endphp

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-light border-0 py-2">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="fw-semibold text-dark">Submissão #{{ $submissao->id }}</div>
                <small class="text-muted">
                    Criado em:
                    {{ optional($submissao->created_at)->format('d/m/Y H:i') ?? $submissao->created_at }}
                </small>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-2 mb-3">
                @can('admin')
                    <div class="col-sm-6 col-lg-3">
                    <div class="border rounded p-2 h-100 bg-white">
                        <small class="text-muted d-block text-uppercase">Workflow</small>
                        <span class="fw-semibold">{{ $submissao->key }}</span>
                    </div>
                </div>
              
                <div class="col-sm-6 col-lg-3">
                    <div class="border rounded p-2 h-100 bg-white">
                        <small class="text-muted d-block text-uppercase">Formulário</small>
                        <span class="fw-semibold">{{ $submissao->form_definition_id }}</span>
                    </div>
                </div>
                  @endcan
                <div class="col-sm-6 col-lg-3">
                    <div class="border rounded p-2 h-100 bg-white">
                        <small class="text-muted d-block text-uppercase">Usuário</small>
                        <span class="fw-semibold">{{ $nomeUsuario }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="border rounded p-2 h-100 bg-white">
                        <small class="text-muted d-block text-uppercase">ID</small>
                        <span class="fw-semibold">{{ $submissao->id }}</span>
                    </div>
                </div>
            </div>

            @if ($campos->isNotEmpty())
                <h6 class="fw-bold text-secondary mb-2">Conteúdo enviado</h6>
                <div class="row g-2 mb-3">
                    @foreach ($campos as $key => $value)
                        @if (in_array($key, $camposApenasAdmin, true))
                            @can('admin')
                                <div class="col-md-6">
                                    <div class="border rounded p-2 h-100 bg-light">
                                        <small class="text-muted d-block text-uppercase">
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                        </small>
                                        <span class="fw-semibold">
                                            {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}
                                        </span>
                                    </div>
                                </div>
                            @endcan
                        @elseif (in_array($key, $camposVisiveisUsuario, true) || auth()->user()?->can('admin'))
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100 bg-light">
                                    <small class="text-muted d-block text-uppercase">
                                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                                    </small>
                                    <span class="fw-semibold">
                                        {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($arquivo && !empty($arquivo['stored_path']))
                <h6 class="fw-bold text-secondary mb-2">Anexo</h6>
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex justify-content-center align-items-center rounded bg-dark text-white mr-2"
                                style="width: 56px; height: 56px;">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $arquivo['original_name'] ?? 'Arquivo enviado' }}</div>
                                <small class="text-muted">
                                    Tipo:
                                    {{ strtoupper(pathinfo($arquivo['original_name'] ?? '', PATHINFO_EXTENSION)) ?: 'N/A' }}
                                </small>
                            </div>
                        </div>

                        <a href="{{ asset('storage/' . $arquivo['stored_path']) }}"
                            class="btn btn-sm btn-primary"
                            target="_blank"
                            download>
                            Baixar arquivo
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-body">
            <h5 class="mb-0 text-muted">Nenhuma submissão encontrada.</h5>
        </div>
    </div>
@endforelse

