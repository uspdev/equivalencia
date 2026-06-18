<?php

namespace Tests\Feature;

use App\Models\User;
use App\Replicado\Graduacao;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DisciplinaUspValidationTest extends TestCase
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

    public function test_invalid_usp_code_is_rejected_and_preserved_as_old_input(): void
    {
        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldReceive('existeDisciplinaAtivaPorCodigo')
            ->once()
            ->with('BAD000')
            ->andReturnFalse();
        $this->app->instance(Graduacao::class, $graduacao);

        $this->actingAs($this->authorizedUser)
            ->from(route('equivalencias.newreq-discipline-create', absolute: false))
            ->post(route('equivalencias.newreq-discipline-store', absolute: false), [
                'unidade_tipo' => 'USP',
                'coddis' => 'BAD000',
                'codtur' => '20251',
            ])
            ->assertRedirect(route('equivalencias.newreq-discipline-create', absolute: false))
            ->assertSessionHasErrors('coddis')
            ->assertSessionHasInput('coddis', 'BAD000');
    }
}
