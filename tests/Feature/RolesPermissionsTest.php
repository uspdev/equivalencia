<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Aproveitamento;
use App\Models\Disciplina;
use App\Models\User;
use App\Replicado\Graduacao;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RolesPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'equivalencia.codhabs' => [1],
        ]);
        url()->forceRootUrl('http://localhost');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedBusinessPermissions();
    }

    public function test_aluno_can_create_and_view_own_requests_but_cannot_access_automatic_equivalences(): void
    {
        $aluno = $this->createUserWithRole(Role::ALUNO, 800001);

        $this->actingAs($aluno)
            ->get(route('equivalencias.newreq-create', absolute: false))
            ->assertOk();

        $this->actingAs($aluno)
            ->get(route('equivalencias.req-index', absolute: false))
            ->assertOk();

        $this->actingAs($aluno)
            ->get(route('equivalencias.index', absolute: false))
            ->assertForbidden();
    }

    public function test_svgrad_can_view_but_cannot_manage_automatic_equivalences(): void
    {
        $this->mockCourses();
        $this->createAutomaticEquivalence();
        $svgrad = $this->createUserWithRole(Role::SVGRAD, 800002);

        $this->actingAs($svgrad)
            ->get(route('equivalencias.show', [100, 1], false))
            ->assertOk()
            ->assertSee('MAC0110')
            ->assertDontSee('data-toggle="equivalencias-edit"', false)
            ->assertDontSee('Remover disciplina requerida');

        $this->actingAs($svgrad)
            ->post(route('equivalencias.store', [100, 1], false), [
                'coddis' => 'MAT0111',
            ])
            ->assertForbidden();
    }

    public function test_cg_can_view_and_manage_automatic_equivalences(): void
    {
        $cg = $this->createUserWithRole(Role::CG, 800003);
        $this->mockGraduacaoDiscipline('MAC0110', 'Introdução à Computação');

        $this->actingAs($cg)
            ->post(route('equivalencias.store', [100, 1], false), [
                'coddis' => 'MAC0110',
                'verdis' => 1,
            ])
            ->assertRedirect(route('equivalencias.show', [100, 1, 'filter' => 'MAC0110'], false));

        $this->assertDatabaseHas('disciplinas', [
            'coddis' => 'MAC0110',
            'nomdis' => 'Introdução à Computação',
            'ies' => 'USP',
        ]);
        $this->assertDatabaseHas('equivalencias', [
            'tipo' => Aproveitamento::TIPO_AUTOMATICA,
            'codcur' => 100,
            'codhab' => 1,
        ]);
    }

    public function test_admin_role_keeps_full_access(): void
    {
        $admin = $this->createUserWithRole(Role::ADMIN, 800004);
        $this->mockGraduacaoDiscipline('MAC0110', 'Introdução à Computação');

        $this->actingAs($admin)
            ->get(route('equivalencias.newreq-create', absolute: false))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('equivalencias.store', [100, 1], false), [
                'coddis' => 'MAC0110',
                'verdis' => 1,
            ])
            ->assertRedirect(route('equivalencias.show', [100, 1, 'filter' => 'MAC0110'], false));
    }

    public function test_missing_senhaunica_admin_permission_does_not_break_menu_rendering(): void
    {
        Permission::query()
            ->where('guard_name', 'senhaunica')
            ->where('name', 'admin')
            ->delete();

        $aluno = $this->createUserWithRole(Role::ALUNO, 800005);

        $this->actingAs($aluno)
            ->get(route('equivalencias.req-index', absolute: false))
            ->assertOk();
    }

    private function createUserWithRole(Role $role, int $codpes): User
    {
        $user = User::create([
            'name' => "Usuário {$role->value}",
            'email' => "{$role->value}{$codpes}@example.com",
            'codpes' => $codpes,
        ]);
        $user->assignRole($role->value);

        return $user;
    }

    private function mockCourses(): void
    {
        $replicadoDb = Mockery::mock('alias:Uspdev\Replicado\DB');
        $replicadoDb->shouldReceive('fetchAll')
            ->andReturn([[
                'codcur' => 100,
                'codhab' => 1,
                'nomcur' => 'Curso de Teste',
                'nomhab' => 'Habilitação de Teste',
            ]]);
    }

    private function mockGraduacaoDiscipline(string $coddis, string $nomdis): void
    {
        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldReceive('obterDadosDisciplinaPorCodigoVersao')
            ->with($coddis, 1)
            ->andReturn([
                'coddis' => $coddis,
                'verdis' => 1,
                'nomdis' => $nomdis,
                'sglund' => 'IME',
            ]);
        $this->app->instance(Graduacao::class, $graduacao);
    }

    private function createAutomaticEquivalence(): void
    {
        $required = Disciplina::create([
            'coddis' => 'MAC0110',
            'nomdis' => 'Introdução à Computação',
            'ies' => 'USP',
        ]);
        $equivalent = Disciplina::create([
            'coddis' => 'EXT100',
            'nomdis' => 'Programação',
            'ies' => 'Universidade Externa',
        ]);

        Aproveitamento::create([
            'grupo' => 1,
            'requerida_id' => $required->id,
            'cursada_id' => $required->id,
            'tipo' => Aproveitamento::TIPO_AUTOMATICA,
            'codcur' => 100,
            'codhab' => 1,
        ]);

        Aproveitamento::create([
            'grupo' => 1,
            'requerida_id' => $required->id,
            'cursada_id' => $equivalent->id,
            'tipo' => Aproveitamento::TIPO_AUTOMATICA,
            'codcur' => 100,
            'codhab' => 1,
        ]);
    }
}
