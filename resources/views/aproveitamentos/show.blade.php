@extends('layouts.app')

@section('content')
  <div class="card">
    <div class="card-header-sticky card-header">
      <h3 class="">
        Requisição de equivalência &rarr;
        <span class="text-primary">
          <strong>{{ $show_data['requerida']['coddis'] . ' - ' . $show_data['requerida']['nomdis'] }}</strong>
        </span>
      </h3>
    </div>
    <div class="card-body card-body-sticky">
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
                <tr>
                  <td class="text-info ">{{ $cursada['coddis'] }}</td>
                  <td class="text-warning ">{{ $cursada['nomdis'] }}</td>
                  <td class="text-secondary ">{{ $cursada['semestre'] }}°</td>
                  <td class="text-danger ">{{ $cursada['ano'] }}</td>
                  <td class="">{{ $cursada['freq'] }}%</td>
                  <td class="">{{ $cursada['nota'] }}</td>
                  <td class="">{{ $cursada['creditos'] }}</td>
                  <td class="">{{ $cursada['carga_hr'] }}</td>
                  <td class="">{{ $cursada['ies'] }}</td>
                  {{-- <div class="col">
                                <span title="Ementa">
                                    <a href="{{ route('form-submissions.download-file', ['formDefinition' => $submission->form_definition_id, 'formSubmission' => $submission->id,'fi       eldName' => $field['name']]) }}" target="_blank">
                                        {{ Illuminate\Support\Str::limit($filename, 30) }}
                                    </a>
                                </span>
                            </div> --}}
                </tr>
                @if (!$loop->last)
                  <br>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
