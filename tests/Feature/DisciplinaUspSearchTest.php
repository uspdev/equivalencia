<?php

namespace Tests\Feature;

use App\Models\User;
use App\Replicado\Graduacao;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DisciplinaUspSearchTest extends TestCase
{
    private User $authorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge();
        url()->forceRootUrl('http://localhost');
        Artisan::call('migrate:fresh', ['--force' => true]);

        $this->authorizedUser = User::create([
            'name' => 'Usuário autorizado',
            'email' => 'autorizado@example.com',
            'codpes' => 654321,
        ]);
        $this->authorizedUser->criarPermissoesPadrao();
        $this->authorizedUser->givePermissionTo(Permission::findByName('admin', 'senhaunica'));
    }

    public function test_empty_or_short_term_returns_empty_results(): void
    {
        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldNotReceive('buscarDisciplinas');
        $this->app->instance(Graduacao::class, $graduacao);

        $this->actingAs($this->authorizedUser)
            ->getJson(route('equivalencias.disciplinas-usp.search', absolute: false))
            ->assertOk()
            ->assertExactJson(['results' => []]);

        $this->actingAs($this->authorizedUser)
            ->getJson(route('equivalencias.disciplinas-usp.search', ['term' => 'ma'], false))
            ->assertOk()
            ->assertExactJson(['results' => []]);

        $this->actingAs($this->authorizedUser)
            ->getJson(route('equivalencias.disciplinas-usp.search', ['term' => 'MAC%'], false))
            ->assertOk()
            ->assertExactJson(['results' => []]);
    }

    public function test_search_normalizes_term_and_returns_select2_contract_with_limit(): void
    {
        $disciplines = [];
        for ($index = 0; $index < 51; $index++) {
            $code = 'MAC'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $disciplines[] = ['coddis' => $code, 'nomdis' => "Disciplina {$index}"];
        }

        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldReceive('buscarDisciplinas')
            ->once()
            ->with('MAC', 50)
            ->andReturn($disciplines);
        $this->app->instance(Graduacao::class, $graduacao);

        $response = $this->actingAs($this->authorizedUser)
            ->getJson(route('equivalencias.disciplinas-usp.search', ['term' => '  mac  '], false))
            ->assertOk()
            ->assertJsonPath('results.0.id', 'MAC0000')
            ->assertJsonPath('results.0.text', 'MAC0000 - Disciplina 0');

        $this->assertCount(50, $response->json('results'));
    }

    public function test_unauthorized_user_cannot_search(): void
    {
        $user = User::create([
            'name' => 'Sem acesso',
            'email' => 'sem-acesso@example.com',
            'codpes' => 111111,
        ]);

        $this->actingAs($user)
            ->getJson(route('equivalencias.disciplinas-usp.search', ['term' => 'MAC'], false))
            ->assertForbidden();
    }

    public function test_replicado_failure_returns_service_unavailable(): void
    {
        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldReceive('buscarDisciplinas')
            ->once()
            ->with('MAC', 50)
            ->andThrow(new \RuntimeException('Falha simulada'));
        $this->app->instance(Graduacao::class, $graduacao);

        $this->actingAs($this->authorizedUser)
            ->getJson(route('equivalencias.disciplinas-usp.search', ['term' => 'MAC'], false))
            ->assertServiceUnavailable()
            ->assertExactJson(['results' => []]);
    }

    public function test_invalid_usp_code_is_rejected_and_preserved_as_old_input(): void
    {
        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldReceive('buscarDisciplina')
            ->once()
            ->with('BAD000')
            ->andReturnNull();
        $this->app->instance(Graduacao::class, $graduacao);

        $this->actingAs($this->authorizedUser)
            ->from(route('equivalencias.newreq-discipline-create', absolute: false))
            ->post(route('equivalencias.newreq-discipline-store', absolute: false), [
                'unidade_tipo' => 'USP',
                'coddis' => 'BAD000',
                'ano' => 2025,
                'semestre' => 1,
            ])
            ->assertRedirect(route('equivalencias.newreq-discipline-create', absolute: false))
            ->assertSessionHasErrors('coddis')
            ->assertSessionHasInput('coddis', 'BAD000');
    }
}
