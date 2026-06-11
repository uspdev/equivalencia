<?php

namespace Tests\Feature;

use App\Models\AproveitamentoRascunho;
use App\Models\User;
use App\Replicado\Graduacao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $graduacao->shouldReceive('disciplinaExiste')
            ->with('MAC0110')
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
            ->assertSee('disciplina-usp-select', false)
            ->assertSee(route('equivalencias.disciplinas-usp.search'), false)
            ->assertSee('minimumInputLength: 3', false)
            ->assertSee("term: params.term || ''", false)
            ->assertSee('processResults: function (response)', false);

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-required', absolute: false), [
                'requerida_coddis' => 'MAC0110',
            ])
            ->assertRedirect(route('equivalencias.newreq-create', absolute: false));

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-discipline-store', absolute: false), [
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
        $disciplineId = $draft->disciplinas[0]['id'];

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-transcripts', absolute: false), [
                'historicos' => [
                    $disciplineId => UploadedFile::fake()
                        ->create('historico.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('equivalencias.newreq-create', absolute: false));

        $this->actingAs($user)
            ->post(route('equivalencias.newreq-store', absolute: false))
            ->assertRedirect(route('equivalencias.req-show', ['group' => 1], false));

        $this->assertDatabaseCount('disciplinas', 2);
        $this->assertDatabaseCount('equivalencias', 1);
        $this->assertDatabaseCount('arquivos', 2);
        $this->assertDatabaseMissing('aproveitamento_rascunhos', ['user_id' => $user->id]);
    }
}
