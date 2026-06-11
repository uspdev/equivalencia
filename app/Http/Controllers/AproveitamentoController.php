<?php

namespace App\Http\Controllers;

use App\Enums\EquivalenciaTipo;
use App\Http\Requests\SaveAproveitamentoRequest;
use App\Models\Arquivo;
use App\Models\Disciplina;
use App\Models\Aproveitamento;
use App\Models\AproveitamentoRascunho;
use App\Replicado\Graduacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Uspdev\Forms\Form;
use Uspdev\Forms\Models\FormSubmission;
use Illuminate\Contracts\View\View;

class AproveitamentoController extends Controller
{
    // TODO - Fazer CRUD, index, show e etc.

    public function __construct(private Graduacao $graduacao)
    {
    }

    public function create(): View
    {
        $draft = $this->currentDraft();
        $disciplines = collect($draft->disciplinas ?? []);
        $transcripts = $draft->historicos ?? [];

        return view('aproveitamentos.createReq', [
            'draft' => $draft,
            'requiredDisciplineName' => $this->uspDisciplineName($draft->requerida_coddis),
            'disciplines' => $disciplines,
            'transcripts' => $transcripts,
            'transcriptGroups' => $this->externalDisciplineGroups($disciplines, $transcripts),
        ]);
    }

    public function saveRequiredDiscipline(Request $request)
    {
        $validated = $request->validate([
            'requerida_coddis' => ['required', 'string', 'regex:/^[A-Za-z0-9]{3,7}$/'],
        ], [
            'requerida_coddis.required' => 'Selecione a disciplina para a qual deseja equivalência.',
            'requerida_coddis.regex' => 'Selecione uma disciplina USP válida.',
        ]);

        $code = Str::upper(trim($validated['requerida_coddis']));
        if (! $this->graduacao->disciplinaExiste($code)) {
            throw ValidationException::withMessages([
                'requerida_coddis' => 'A disciplina USP selecionada não foi encontrada.',
            ]);
        }

        $draft = $this->currentDraft();
        $draft->update(['requerida_coddis' => $code]);

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina desejada salva no rascunho.');
    }

    public function createDiscipline(): View
    {
        $draft = $this->currentDraft();
        abort_if(count($draft->disciplinas ?? []) >= 3, 422, 'O limite de três disciplinas foi atingido.');

        return view('aproveitamentos.discipline', [
            'discipline' => null,
            'formAction' => route('equivalencias.newreq-discipline-store'),
            'formMethod' => 'POST',
            'requiredDisciplineCode' => $draft->requerida_coddis,
        ]);
    }

    public function storeDiscipline(Request $request)
    {
        $draft = $this->currentDraft();
        abort_if(count($draft->disciplinas ?? []) >= 3, 422, 'O limite de três disciplinas foi atingido.');

        $request->session()->flash('discipline_modal', 'create');
        $requiredCode = $request->filled('requerida_coddis')
            ? $this->validatedRequiredDisciplineCode($request)
            : null;
        $discipline = $this->validatedDraftDiscipline($request, $draft);
        $requiredCode ??= $this->validatedRequiredDisciplineCode($request);

        $disciplines = $draft->disciplinas ?? [];
        $disciplines[] = $discipline;
        $draft->update([
            'requerida_coddis' => $requiredCode,
            'disciplinas' => $disciplines,
        ]);
        $request->session()->forget('discipline_modal');

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina adicionada ao rascunho.');
    }

    public function editDiscipline(string $disciplineId): View
    {
        $draft = $this->currentDraft();
        $discipline = $this->findDraftDiscipline($draft, $disciplineId);

        return view('aproveitamentos.discipline', [
            'discipline' => $discipline,
            'formAction' => route('equivalencias.newreq-discipline-update', $disciplineId),
            'formMethod' => 'PUT',
            'requiredDisciplineCode' => $draft->requerida_coddis,
        ]);
    }

    public function updateDiscipline(Request $request, string $disciplineId)
    {
        $draft = $this->currentDraft();
        $current = $this->findDraftDiscipline($draft, $disciplineId);

        $request->session()->flash('discipline_modal', $disciplineId);
        $requiredCode = $this->validatedRequiredDisciplineCode($request);
        $updated = $this->validatedDraftDiscipline($request, $draft, $current);

        $disciplines = collect($draft->disciplinas ?? [])
            ->map(fn (array $discipline) => $discipline['id'] === $disciplineId ? $updated : $discipline)
            ->values()
            ->all();

        $histories = $this->removeOrphanedTranscripts(
            $draft->historicos ?? [],
            collect($disciplines)
        );

        $draft->update([
            'requerida_coddis' => $requiredCode,
            'disciplinas' => $disciplines,
            'historicos' => $histories,
        ]);
        $request->session()->forget('discipline_modal');

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina atualizada no rascunho.');
    }

    public function destroyDiscipline(string $disciplineId)
    {
        $draft = $this->currentDraft();
        $discipline = $this->findDraftDiscipline($draft, $disciplineId);

        if (isset($discipline['ementa']['path'])) {
            Storage::delete($discipline['ementa']['path']);
        }

        $disciplines = collect($draft->disciplinas ?? [])
            ->reject(fn (array $item) => $item['id'] === $disciplineId)
            ->values()
            ->all();
        $histories = $this->removeOrphanedTranscripts(
            $draft->historicos ?? [],
            collect($disciplines)
        );

        $draft->update([
            'disciplinas' => $disciplines,
            'historicos' => $histories,
        ]);

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Disciplina removida do rascunho.');
    }

    public function saveTranscripts(Request $request)
    {
        $draft = $this->currentDraft();
        $groups = $this->externalDisciplineGroups(
            collect($draft->disciplinas ?? []),
            $draft->historicos ?? []
        );

        abort_if($groups->isEmpty(), 422, 'Não há disciplinas externas no rascunho.');

        $rules = [];
        foreach ($groups as $group) {
            $rules["historicos.{$group['key']}"] = [
                isset($group['file']) ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ];
        }

        $request->validate($rules, [
            'historicos.*.required' => 'Envie um histórico escolar para cada disciplina externa.',
            'historicos.*.mimes' => 'O histórico escolar deve ser um arquivo PDF.',
            'historicos.*.max' => 'Cada histórico escolar pode ter no máximo 10 MB.',
        ]);

        $histories = $draft->historicos ?? [];
        foreach ($groups as $group) {
            $file = $request->file("historicos.{$group['key']}");
            if (! $file) {
                continue;
            }

            if (isset($histories[$group['key']]['path'])) {
                Storage::delete($histories[$group['key']]['path']);
            }

            $histories[$group['key']] = $this->storeDraftFile($draft, $file, 'historicos');
        }

        $draft->update(['historicos' => $histories]);

        return redirect()
            ->route('equivalencias.newreq-create')
            ->with('alert-success', 'Histórico escolare salvos no rascunho.');
    }

    /**
     * Função que persiste os dados de uma requisição de aproveitamento
     * na tabela de disciplinas e também na tabela de equivalência
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $draft = $this->currentDraft();
        $disciplines = collect($draft->disciplinas ?? []);
        $externalDisciplines = $disciplines->where('unidade_tipo', 'OUTRA');
        $histories = $draft->historicos ?? [];

        $errors = [];
        if (! $draft->requerida_coddis) {
            $errors['requerida_coddis'] = 'Selecione a disciplina para a qual deseja equivalência.';
        }
        if ($disciplines->isEmpty()) {
            $errors['disciplinas'] = 'Adicione ao menos uma disciplina cursada.';
        }
        foreach ($this->externalDisciplineGroups($disciplines, $histories) as $group) {
            if (! isset($group['file'])) {
                $errors["historicos.{$group['key']}"] =
                    "Envie o histórico escolar da unidade {$group['unit_name']}.";
            }
        }

        if ($errors) {
            return redirect()
                ->route('equivalencias.newreq-create')
                ->withErrors($errors);
        }

        $userId = (int) $request->user()->id;
        $result = DB::transaction(function () use ($draft, $disciplines, $histories, $userId) {
            $requiredData = Disciplina::dadosDaRequeridaPorCoddis($draft->requerida_coddis);
            $requiredData['nomdis'] ??= $draft->requerida_coddis;
            $requiredData['ies'] = 'USP';
            $requiredData['criado_por_id'] = $userId;
            $requiredData['alterado_por_id'] = $userId;
            $required = Disciplina::create($requiredData);
            $group = Aproveitamento::proximoGrupo();

            foreach ($disciplines as $disciplineData) {
                $courseData = [
                    'is_usp' => $disciplineData['unidade_tipo'] === 'USP',
                    'coddis' => $disciplineData['coddis'],
                    'nome_disciplina' => $disciplineData['nomdis'],
                    'ies' => $disciplineData['unidade_tipo'] === 'USP'
                        ? 'USP'
                        : $disciplineData['unidade_nome'],
                    'ano' => $disciplineData['ano'],
                    'semestre' => $disciplineData['semestre'],
                    'frequencia' => $disciplineData['frequencia'],
                    'nota' => $disciplineData['nota'],
                    'creditos' => $disciplineData['creditos'],
                    'carga_horaria' => $disciplineData['carga_horaria'],
                ];
                $courseData = Disciplina::dadosDaCursadaPorFormulario($courseData);
                $courseData['criado_por_id'] = $userId;
                $courseData['alterado_por_id'] = $userId;
                $course = Disciplina::create($courseData);

                $equivalence = Aproveitamento::create([
                    'grupo' => $group,
                    'requerida_id' => $required->id,
                    'cursada_id' => $course->id,
                    'tipo' => EquivalenciaTipo::REQUERIDA,
                    'criado_por_id' => $userId,
                    'alterado_por_id' => $userId,
                ]);

                if (isset($disciplineData['ementa'])) {
                    Arquivo::create([
                        'equivalencia_id' => $equivalence->id,
                        'tipo' => Arquivo::TIPO_EMENTA,
                        'nome' => $disciplineData['ementa']['name'],
                        'path' => $disciplineData['ementa']['path'],
                    ]);
                }

                $transcriptKey = $disciplineData['unidade_tipo'] === 'OUTRA'
                    ? $this->transcriptKey($disciplineData['unidade_nome'])
                    : null;
                if ($transcriptKey && isset($histories[$transcriptKey])) {
                    Arquivo::create([
                        'equivalencia_id' => $equivalence->id,
                        'tipo' => Arquivo::TIPO_HISTORICO,
                        'nome' => $histories[$transcriptKey]['name'],
                        'path' => $histories[$transcriptKey]['path'],
                    ]);
                }
            }

            $draft->delete();

            return ['group' => $group, 'name' => $required->nomdis];
        });

        return redirect()
            ->route('equivalencias.req-show', ['group' => $result['group']])
            ->with('alert-success', "Requerimento para {$result['name']} criado com sucesso!");
    }

    /**
     * Função index, apresenta todas as requisições feitas pelo usuário
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        $requisitions = Aproveitamento::requerimentosDoUsuario(Auth::id());

        return view('aproveitamentos.index',['requisicoes' => $requisitions]);
    }

    // TODO - implementar a função show

    /**
     * Função para exibição de um pedido de aproveitamento - Placeholder
     * @param int $group
     */
    public function show(int $group): View
    {
        $show_data = Aproveitamento::dadosDeExibicaoDoRequerimento($group, Auth::id());

        return view('aproveitamentos.show', ['show_data' => $show_data]);
    }

    /**
     * Remove um pedido de aproveitamento do banco de dados
     * @param int $group
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $group)
    {   
        $req_name = Aproveitamento::removerRequerimentoDoUsuario($group, Auth::id());

        return redirect()->back()->with('alert-success','Requerimento de equivalência para '. $req_name . ' removido com sucesso.');
    }

    /**
     * Função que possibilita a edição de uma requisição de equivalência
     * @param int $group
     * @return View
     */
    public function edit(int $group): View
    {
        $submission = $this->submissionFromGrupo($group);
        
        // Gera o html para a edição do formulário
        $formHtml = (new Form(['method' => 'PUT']))->generateHtml(config('app.initial_form'), $submission);

        return view('aproveitamentos.edit',['formHtml' => $formHtml, 'submission' => $submission]);
    }

    /**
     * Atualiza os registros de equivalencia de acordo com as modificações do usuário
     * @param SaveAproveitamentoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SaveAproveitamentoRequest $request, int $group)
    {
        $submission = (new Form(['editable' => true]))->handleSubmission($request);
        $data = Aproveitamento::atualizarRequerimentoDoFormulario(
            $group,
            $request->input(),
            $submission->data,
            $request->user()->id
        );

        if(empty($data))
        {
            return redirect()->back()->with('alert-danger', 'Nao há equivalências para a submissão editada');
        }

        return redirect()
            ->route('equivalencias.req-show', ['group' => $data['eq_group']])
            ->with('alert-success','Requerimento para '. $data['req_name'] . ' ' . $data['modo'] . ' com sucesso !');
    }

    private function currentDraft(): AproveitamentoRascunho
    {
        return AproveitamentoRascunho::firstOrCreate(
            ['user_id' => Auth::id()],
            ['disciplinas' => [], 'historicos' => []]
        );
    }

    private function validatedRequiredDisciplineCode(Request $request): string
    {
        $validated = $request->validate([
            'requerida_coddis' => ['required', 'string', 'regex:/^[A-Za-z0-9]{3,7}$/'],
        ], [
            'requerida_coddis.required' => 'Selecione a disciplina para a qual deseja equivalência.',
            'requerida_coddis.regex' => 'Selecione uma disciplina USP válida.',
        ]);

        $code = Str::upper(trim($validated['requerida_coddis']));
        if (! $this->graduacao->disciplinaExiste($code)) {
            throw ValidationException::withMessages([
                'requerida_coddis' => 'A disciplina USP selecionada não foi encontrada.',
            ]);
        }

        return $code;
    }

    private function validatedDraftDiscipline(
        Request $request,
        AproveitamentoRascunho $draft,
        ?array $current = null
    ): array {
        $isExternal = $request->input('unidade_tipo') === 'OUTRA';
        $needsSyllabus = $isExternal && ! isset($current['ementa']);

        $validated = $request->validate([
            'unidade_tipo' => ['required', Rule::in(['USP', 'OUTRA'])],
            'unidade_nome' => [$isExternal ? 'required' : 'nullable', 'string', 'max:255'],
            'coddis' => [
                'required',
                'string',
                $isExternal
                    ? 'regex:/^[A-Za-z0-9]{1,7}$/'
                    : 'regex:/^[A-Za-z0-9]{3,7}$/',
            ],
            'nomdis' => [$isExternal ? 'required' : 'nullable', 'string', 'max:240'],
            'ano' => ['required', 'integer', 'min:1900', 'max:'.date('Y')],
            'semestre' => ['required', 'integer', Rule::in([1, 2])],
            'ementa' => [$needsSyllabus ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:10240'],
            'frequencia' => [$isExternal ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'nota' => [$isExternal ? 'required' : 'nullable', 'numeric', 'min:0', 'max:10'],
            'creditos' => [$isExternal ? 'required' : 'nullable', 'integer', 'min:1'],
            'carga_horaria' => [$isExternal ? 'required' : 'nullable', 'integer', 'min:1'],
        ], [
            'unidade_nome.required' => 'Informe o nome da unidade ou instituição.',
            'nomdis.required' => 'Informe o nome da disciplina externa.',
            'ano.max' => 'Informe o ano do calendário, que não pode ser posterior ao ano atual.',
            'semestre.in' => 'O semestre deve ser 1 ou 2.',
            'ementa.required' => 'Envie a ementa da disciplina externa.',
            'ementa.mimes' => 'A ementa deve ser um arquivo PDF.',
            'ementa.max' => 'A ementa pode ter no máximo 10 MB.',
            'coddis.regex' => 'Informe um código de disciplina válido, com até 7 letras ou números.',
        ]);

        $code = Str::upper(trim($validated['coddis']));
        $uspDiscipline = null;
        if (! $isExternal) {
            $uspDiscipline = $this->graduacao->buscarDisciplina($code);
            if (! $uspDiscipline) {
                throw ValidationException::withMessages([
                    'coddis' => 'A disciplina USP selecionada não foi encontrada.',
                ]);
            }
        }

        $discipline = [
            'id' => $current['id'] ?? (string) Str::uuid(),
            'unidade_tipo' => $validated['unidade_tipo'],
            'unidade_nome' => $isExternal ? trim($validated['unidade_nome']) : 'USP',
            'coddis' => $code,
            'nomdis' => $isExternal
                ? trim($validated['nomdis'])
                : trim((string) $uspDiscipline['nomdis']),
            'ano' => (int) $validated['ano'],
            'semestre' => (int) $validated['semestre'],
            'frequencia' => $isExternal ? (float) $validated['frequencia'] : null,
            'nota' => $isExternal ? (float) $validated['nota'] : null,
            'creditos' => $isExternal ? (int) $validated['creditos'] : null,
            'carga_horaria' => $isExternal ? (int) $validated['carga_horaria'] : null,
        ];

        if ($isExternal && isset($current['ementa'])) {
            $discipline['ementa'] = $current['ementa'];
        }

        if ($request->hasFile('ementa')) {
            if (isset($current['ementa']['path'])) {
                Storage::delete($current['ementa']['path']);
            }
            $discipline['ementa'] = $this->storeDraftFile($draft, $request->file('ementa'), 'ementas');
        } elseif (! $isExternal && isset($current['ementa']['path'])) {
            Storage::delete($current['ementa']['path']);
        }

        return $discipline;
    }

    private function findDraftDiscipline(AproveitamentoRascunho $draft, string $disciplineId): array
    {
        $discipline = collect($draft->disciplinas ?? [])
            ->first(fn (array $item) => ($item['id'] ?? null) === $disciplineId);

        abort_if(! $discipline, 404);

        return $discipline;
    }

    private function externalDisciplineGroups(
        \Illuminate\Support\Collection $disciplines,
        array $histories
    ): \Illuminate\Support\Collection {
        return $disciplines
            ->where('unidade_tipo', 'OUTRA')
            ->groupBy(fn (array $discipline) => $this->transcriptKey($discipline['unidade_nome']))
            ->map(function (\Illuminate\Support\Collection $group, string $key) use ($histories) {
                return [
                    'key' => $key,
                    'unit_name' => $group->first()['unidade_nome'],
                    'disciplines' => $group->values(),
                    'file' => $histories[$key] ?? null,
                ];
            })
            ->values();
    }

    private function removeOrphanedTranscripts(
        array $histories,
        \Illuminate\Support\Collection $disciplines
    ): array {
        $validKeys = $this->externalDisciplineGroups($disciplines, $histories)
            ->pluck('key')
            ->all();

        foreach ($histories as $key => $history) {
            if (in_array($key, $validKeys, true)) {
                continue;
            }

            if (isset($history['path'])) {
                Storage::delete($history['path']);
            }
            unset($histories[$key]);
        }

        return $histories;
    }

    private function transcriptKey(string $unitName): string
    {
        $normalized = Str::of($unitName)
            ->ascii()
            ->lower()
            ->squish()
            ->value();

        return hash('sha256', $normalized);
    }

    private function storeDraftFile(
        AproveitamentoRascunho $draft,
        \Illuminate\Http\UploadedFile $file,
        string $directory
    ): array {
        return [
            'name' => $file->getClientOriginalName(),
            'path' => $file->store("aproveitamentos/{$draft->id}/{$directory}"),
        ];
    }

    private function uspDisciplineName(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return $this->graduacao->buscarDisciplina($code)['nomdis'] ?? null;
    }

    private function submissionFromGrupo(int $group): FormSubmission
    {
        $submission = new FormSubmission();
        $submission->data = Aproveitamento::dadosParaFormularioDoRequerimento($group, Auth::id());

        return $submission;
    }
}
