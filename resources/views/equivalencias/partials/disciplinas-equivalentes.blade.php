@php
    $equivalenciasPorGrupo = $disciplina->equivalentes->groupBy('grupo');
@endphp
@forelse ($equivalenciasPorGrupo as $grupo => $equivalenciasDoGrupo)
    @php
        $equivalenciaRepresentante = $equivalenciasDoGrupo->first();
    @endphp

    <div class="disciplina-equivalente d-flex align-items-center flex-nowrap mb-2">
        <p class="mb-0 text-truncate">
            @foreach ($equivalenciasDoGrupo as $e)
                <span title="{{ $e->cursada->nome_disciplina }}">
                    {{ $e->cursada->coddis }} -
                    @limitarTexto($e->cursada->nome_disciplina)
                </span>

                @notLast('|')
            @endforeach
        </p>
        @can('svgrad')
            @if ($equivalenciaRepresentante)
            <div class="js-edit-only d-inline-flex align-items-center">
                @include('equivalencias.partials.modal-edit-equivalencia', [
                    'equivalencia' => $equivalenciaRepresentante,
                ])
                @include('equivalencias.partials.form-remove-equivalencia')
            </div>
            @endif
        @endcan
    </div>
@empty
    -
@endforelse
