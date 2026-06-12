<?php

namespace Tests\Feature;

use App\Models\AproveitamentoRascunho;
use App\Models\User;
use App\Replicado\Graduacao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Uspdev\Replicado\Replicado;
use Mockery;

class AproveitamentoMultistepTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'replicado.fake' => true,
        ]);
        DB::purge();
        url()->forceRootUrl('http://localhost');
        Artisan::call('migrate:fresh', ['--force' => true]);
        Replicado::setConfig(['fake' => true]);

        Storage::fake('local');

        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldReceive('buscarDisciplina')
            ->with('MAC0110')
            ->andReturn(['coddis' => 'MAC0110', 'nomdis' => 'Introdução à Computação'])
            ->byDefault();
        $graduacao->shouldReceive('buscarDisciplina')
            ->with('MAT0111')
            ->andReturn(['coddis' => 'MAT0111', 'nomdis' => 'Cálculo Diferencial'])
            ->byDefault();
        $graduacao->shouldReceive('disciplinaExiste')
            ->with('MAC0110')
            ->andReturnTrue()
            ->byDefault();
        $graduacao->shouldReceive('disciplinaExiste')
            ->with('MAT0111')
            ->andReturnTrue()
            ->byDefault();
        $this->app->instance(Graduacao::class, $graduacao);
    }

    public function test_external_discipline_draft_can_be_completed(): void
    {
        $user = User::create([
            'name' => 'Aluno',
            'email' => 'aluno@example.com',
            'codpes' => 123456,
        ]);
        $user->criarPermissoesPadrao();
        $user->givePermissionTo(Permission::findByName('admin', 'senhaunica'));

        $this->actingAs($user)
            ->get(route('equivalencias.newreq-create', absolute: false))
            ->assertOk()
            ->assertDontSee('Salvar e continuar')
            ->assertDontSee('Salvar históricos')
            ->assertSee('id="create-discipline-modal"', false)
            ->assertSee('disciplina-usp-select', false)
            ->assertSee(route('equivalencias.disciplinas-usp.search'), false)
            ->assertSee('minimumInputLength: 3', false)
            ->assertSee("term: params.term || ''", false)
            ->assertSee('processResults: function(response)', false);

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-discipline-store', absolute: false), [
                'requerida_coddis' => 'MAC0110',
                'unidade_tipo' => 'OUTRA',
                'unidade_nome' => 'Universidade Externa',
                'coddis' => 'EXT100',
                'nomdis' => 'Programação',
                'ano' => 2025,
                'semestre' => 1,
                'ementa' => UploadedFile::fake()->create('ementa.pdf', 100, 'application/pdf'),
                'frequencia' => 90,
                'nota' => 8.5,
                'creditos' => 4,
                'carga_horaria' => 60,
            ])
            ->assertRedirect(route('equivalencias.newreq-create', absolute: false));

        $draft = AproveitamentoRascunho::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('MAC0110', $draft->requerida_coddis);
        $transcriptKey = $this->transcriptKey('Universidade Externa');

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-store', absolute: false), [
                'historicos' => [
                    $transcriptKey => UploadedFile::fake()
                        ->create('historico.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('equivalencias.req-index', absolute: false))
            ->assertSessionHas('alert-success');

        $transcriptPath = DB::table('arquivos')
            ->where('tipo', 'historico')
            ->value('path');

        $this->actingAs($user)
            ->get(route('equivalencias.req-index', absolute: false))
            ->assertOk()
            ->assertSee('Minhas requisições')
            ->assertSee('MAC0110')
            ->assertDontSee('Editar');

        $this->assertDatabaseCount('disciplinas', 2);
        $this->assertDatabaseCount('equivalencias', 1);
        $this->assertDatabaseCount('arquivos', 2);
        $this->assertDatabaseMissing('aproveitamento_rascunhos', ['user_id' => $user->id]);

        $this->actingAs($user)
            ->from(route('equivalencias.req-index', absolute: false))
            ->get(route('equivalencias.req-destroy', ['group' => 1], false))
            ->assertRedirect(route('equivalencias.req-index', absolute: false));

        $this->assertDatabaseCount('equivalencias', 0);
        $this->assertDatabaseCount('arquivos', 0);
        Storage::assertMissing($transcriptPath);
    }

    public function test_disciplines_from_same_normalized_unit_share_transcript(): void
    {
        $user = $this->createAuthorizedUser(654320);
        $disciplineData = [
            'requerida_coddis' => 'MAC0110',
            'unidade_tipo' => 'OUTRA',
            'nomdis' => 'Programação',
            'ano' => 2025,
            'semestre' => 1,
            'frequencia' => 90,
            'nota' => 8.5,
            'creditos' => 4,
            'carga_horaria' => 60,
        ];

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-discipline-store', absolute: false), array_merge($disciplineData, [
                'unidade_nome' => 'Universidade São Paulo',
                'coddis' => 'EXT100',
                'ementa' => UploadedFile::fake()->create('ementa-1.pdf', 100, 'application/pdf'),
            ]))
            ->assertRedirect(route('equivalencias.newreq-create', absolute: false));

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-discipline-store', absolute: false), array_merge($disciplineData, [
                'unidade_nome' => ' universidade sao   paulo ',
                'coddis' => 'EXT200',
                'nomdis' => 'Algoritmos',
                'ementa' => UploadedFile::fake()->create('ementa-2.pdf', 100, 'application/pdf'),
            ]))
            ->assertRedirect(route('equivalencias.newreq-create', absolute: false));

        $transcriptKey = $this->transcriptKey('Universidade São Paulo');
        $this->actingAs($user)
            ->get(route('equivalencias.newreq-create', absolute: false))
            ->assertOk()
            ->assertSee('Universidade São Paulo')
            ->assertSee('EXT100 - Programação')
            ->assertSee('EXT200 - Algoritmos')
            ->assertSee("name=\"historicos[{$transcriptKey}]\"", false);

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-store', absolute: false), [
                'historicos' => [
                    $transcriptKey => UploadedFile::fake()
                        ->create('historico-compartilhado.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('equivalencias.req-index', absolute: false));

        $this->assertDatabaseCount('equivalencias', 2);
        $this->assertDatabaseCount('arquivos', 3);
        $this->assertDatabaseHas('arquivos', [
            'equivalencia_id' => null,
            'grupo' => 1,
            'tipo' => 'historico',
            'nome' => 'historico-compartilhado.pdf',
        ]);
        $this->assertSame(1, DB::table('arquivos')->where('tipo', 'historico')->count());
    }

    public function test_optional_additional_transcript_is_saved_with_submission(): void
    {
        $user = $this->createAuthorizedUser(654317);
        $transcriptKey = $this->transcriptKey('Universidade Externa');

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-discipline-store', absolute: false), [
                'requerida_coddis' => 'MAC0110',
                'unidade_tipo' => 'OUTRA',
                'unidade_nome' => 'Universidade Externa',
                'coddis' => 'EXT100',
                'nomdis' => 'Programação',
                'ano' => 2025,
                'semestre' => 1,
                'ementa' => UploadedFile::fake()->create('ementa.pdf', 100, 'application/pdf'),
                'frequencia' => 90,
                'nota' => 8.5,
                'creditos' => 4,
                'carga_horaria' => 60,
            ])
            ->assertRedirect(route('equivalencias.newreq-create', absolute: false));

        $this->actingAs($user)
            ->get(route('equivalencias.newreq-create', absolute: false))
            ->assertOk()
            ->assertSee('Histórico escolar adicional')
            ->assertSee('(opcional)')
            ->assertSee('name="historico_adicional"', false);

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-store', absolute: false), [
                'historicos' => [
                    $transcriptKey => UploadedFile::fake()
                        ->create('historico.pdf', 100, 'application/pdf'),
                ],
                'historico_adicional' => UploadedFile::fake()
                    ->create('historico-completo.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('equivalencias.req-index', absolute: false));

        $this->assertDatabaseCount('arquivos', 3);
        $this->assertSame(2, DB::table('arquivos')->where('tipo', 'historico')->count());
        $this->assertDatabaseHas('arquivos', [
            'equivalencia_id' => null,
            'grupo' => 1,
            'tipo' => 'historico',
            'nome' => 'historico-completo.pdf',
        ]);
    }

    public function test_additional_transcript_must_be_a_pdf_when_provided(): void
    {
        $user = $this->createAuthorizedUser(654316);
        $transcriptKey = $this->transcriptKey('Universidade Externa');
        AproveitamentoRascunho::create([
            'user_id' => $user->id,
            'requerida_coddis' => 'MAC0110',
            'disciplinas' => [$this->externalDraftDiscipline('discipline-1', 'EXT100')],
        ]);

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-store', absolute: false), [
                'historicos' => [
                    $transcriptKey => UploadedFile::fake()
                        ->create('historico.pdf', 100, 'application/pdf'),
                ],
                'historico_adicional' => UploadedFile::fake()
                    ->create('historico.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('historico_adicional');
    }

    public function test_transcript_is_required_only_when_submitting_request(): void
    {
        $user = $this->createAuthorizedUser(654319);
        AproveitamentoRascunho::create([
            'user_id' => $user->id,
            'requerida_coddis' => 'MAC0110',
            'disciplinas' => [$this->externalDraftDiscipline('discipline-1', 'EXT100')],
        ]);

        $this->actingAs($user)
            ->from(route('equivalencias.newreq-create', absolute: false))
            ->post(route('equivalencias.newreq-store', absolute: false))
            ->assertRedirect(route('equivalencias.newreq-create', absolute: false))
            ->assertSessionHasErrors("historicos.{$this->transcriptKey('Universidade Externa')}");

        $transcriptKey = $this->transcriptKey('Universidade Externa');
        $this->actingAs($user)
            ->post(route('equivalencias.newreq-store', absolute: false), [
                'historicos' => [
                    $transcriptKey => UploadedFile::fake()
                        ->create('historico.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('equivalencias.req-index', absolute: false));
    }

    public function test_add_button_starts_disabled_without_required_discipline(): void
    {
        $user = $this->createAuthorizedUser(654321);

        $this->actingAs($user)
            ->get(route('equivalencias.newreq-create', absolute: false))
            ->assertOk()
            ->assertSee('id="add-discipline-button"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('disabled', false);
    }

    public function test_required_discipline_is_updated_with_discipline_edit(): void
    {
        $user = $this->createAuthorizedUser(654322);
        $draft = AproveitamentoRascunho::create([
            'user_id' => $user->id,
            'requerida_coddis' => 'MAC0110',
            'disciplinas' => [[
                'id' => 'discipline-1',
                'unidade_tipo' => 'OUTRA',
                'unidade_nome' => 'Universidade Externa',
                'coddis' => 'EXT100',
                'nomdis' => 'Programação',
                'ano' => 2025,
                'semestre' => 1,
                'frequencia' => 90.0,
                'nota' => 8.5,
                'creditos' => 4,
                'carga_horaria' => 60,
                'ementa' => [
                    'name' => 'ementa.pdf',
                    'path' => 'aproveitamentos/1/ementas/ementa.pdf',
                ],
            ]],
        ]);

        $this->actingAs($user)
            ->put(route('equivalencias.newreq-discipline-update', 'discipline-1', absolute: false), [
                'requerida_coddis' => 'MAT0111',
                'unidade_tipo' => 'OUTRA',
                'unidade_nome' => 'Universidade Externa Atualizada',
                'coddis' => 'EXT101',
                'nomdis' => 'Programação II',
                'ano' => 2026,
                'semestre' => 2,
                'frequencia' => 95,
                'nota' => 9,
                'creditos' => 4,
                'carga_horaria' => 60,
            ])
            ->assertRedirect(route('equivalencias.newreq-create', absolute: false));

        $draft->refresh();
        $this->assertSame('MAT0111', $draft->requerida_coddis);
        $this->assertSame('EXT101', $draft->disciplinas[0]['coddis']);
        $this->assertSame('Programação II', $draft->disciplinas[0]['nomdis']);

        $this->actingAs($user)
            ->get(route('equivalencias.newreq-create', absolute: false))
            ->assertOk()
            ->assertSee('id="edit-discipline-modal-discipline-1"', false);
    }

    public function test_validation_error_reopens_originating_modal(): void
    {
        $user = $this->createAuthorizedUser(654323);
        $createUrl = route('equivalencias.newreq-create', absolute: false);

        $this->actingAs($user)
            ->from($createUrl)
            ->post(route('equivalencias.newreq-discipline-store', absolute: false), [
                'requerida_coddis' => 'MAC0110',
                'unidade_tipo' => 'OUTRA',
            ])
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors(['unidade_nome', 'coddis', 'nomdis', 'ano', 'semestre', 'ementa'])
            ->assertSessionHas('discipline_modal', 'create');

        $this->actingAs($user)
            ->get($createUrl)
            ->assertOk()
            ->assertSee('Revise os dados da disciplina.')
            ->assertSee('var modalToOpen = "#create-discipline-modal"', false);
    }

    private function createAuthorizedUser(int $codpes): User
    {
        $user = User::create([
            'name' => 'Aluno',
            'email' => "aluno{$codpes}@example.com",
            'codpes' => $codpes,
        ]);
        $user->criarPermissoesPadrao();
        $user->givePermissionTo(Permission::findByName('admin', 'senhaunica'));

        return $user;
    }

    private function transcriptKey(string $unitName): string
    {
        return hash('sha256', Str::of($unitName)->ascii()->lower()->squish()->value());
    }

    private function externalDraftDiscipline(string $id, string $code): array
    {
        return [
            'id' => $id,
            'unidade_tipo' => 'OUTRA',
            'unidade_nome' => 'Universidade Externa',
            'coddis' => $code,
            'nomdis' => "Disciplina {$code}",
            'ano' => 2025,
            'semestre' => 1,
            'frequencia' => 90.0,
            'nota' => 8.5,
            'creditos' => 4,
            'carga_horaria' => 60,
        ];
    }
}
