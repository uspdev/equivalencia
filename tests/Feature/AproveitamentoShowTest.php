<?php

namespace Tests\Feature;

use App\Models\Aproveitamento;
use App\Models\Arquivo;
use App\Models\Disciplina;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AproveitamentoShowTest extends TestCase
{
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
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_user_can_view_request_data_and_files_with_null_status_fallback(): void
    {
        Carbon::setTestNow('2026-06-15 14:30:00');
        $user = $this->createAuthorizedUser(700001);
        $request = $this->createRequest($user, 10, [
            'estado' => null,
            'ies' => 'Universidade Externa',
            'coddis' => 'EXT100',
            'nomdis' => 'Programação',
            'ano' => 2025,
            'semestre' => 1,
            'frequencia' => 90,
            'nota' => 8.5,
            'creditos' => 4,
            'carga_horaria' => 60,
        ]);
        $syllabus = $this->createFile([
            'equivalencia_id' => $request->id,
            'tipo' => Arquivo::TIPO_EMENTA,
            'nome' => 'ementa-programacao.pdf',
            'path' => 'requerimentos/ementa-programacao.pdf',
        ]);
        $transcript = $this->createFile([
            'grupo' => 10,
            'tipo' => Arquivo::TIPO_HISTORICO,
            'nome' => 'historico-escolar.pdf',
            'path' => 'requerimentos/historico-escolar.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('equivalencias.req-show', ['group' => 10], false))
            ->assertOk()
            ->assertSee('MAC0110 - Introdução à Computação')
            ->assertSee('15/06/2026 14:30')
            ->assertSee('Enviado')
            ->assertSee('Universidade Externa')
            ->assertSee('EXT100 - Programação')
            ->assertSee('90.00%')
            ->assertSee('8.50')
            ->assertSee('60 horas')
            ->assertSee('ementa-programacao.pdf')
            ->assertSee('historico-escolar.pdf')
            ->assertSee(route('equivalencias.req-file', [
                'group' => 10,
                'arquivo' => $syllabus->id,
            ], false), false)
            ->assertSee(route('equivalencias.req-file', [
                'group' => 10,
                'arquivo' => $transcript->id,
            ], false), false);
    }

    public function test_explicit_status_is_displayed(): void
    {
        $user = $this->createAuthorizedUser(700002);
        $this->createRequest($user, 11, ['estado' => 'Em análise']);

        $this->actingAs($user)
            ->get(route('equivalencias.req-show', ['group' => 11], false))
            ->assertOk()
            ->assertSee('Em análise')
            ->assertDontSee('Enviado');
    }

    public function test_usp_discipline_without_syllabus_is_displayed(): void
    {
        $user = $this->createAuthorizedUser(700003);
        $this->createRequest($user, 12, [
            'ies' => 'USP',
            'coddis' => 'MAT0111',
            'nomdis' => 'Cálculo Diferencial',
            'ano' => 2024,
            'semestre' => 2,
            'frequencia' => null,
            'nota' => null,
            'creditos' => null,
            'carga_horaria' => null,
        ]);

        $this->actingAs($user)
            ->get(route('equivalencias.req-show', ['group' => 12], false))
            ->assertOk()
            ->assertSee('MAT0111 - Cálculo Diferencial')
            ->assertSee('Nenhuma ementa enviada.')
            ->assertSee('Nenhum histórico escolar foi enviado.');
    }

    public function test_user_cannot_view_another_users_request_or_files(): void
    {
        $owner = $this->createAuthorizedUser(700004);
        $otherUser = $this->createAuthorizedUser(700005);
        $request = $this->createRequest($owner, 13);
        $file = $this->createFile([
            'equivalencia_id' => $request->id,
            'tipo' => Arquivo::TIPO_EMENTA,
            'nome' => 'ementa.pdf',
            'path' => 'requerimentos/ementa.pdf',
        ]);

        $this->actingAs($otherUser)
            ->get(route('equivalencias.req-show', ['group' => 13], false))
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->get(route('equivalencias.req-file', [
                'group' => 13,
                'arquivo' => $file->id,
            ], false))
            ->assertNotFound();
    }

    public function test_request_files_open_inline_for_the_owner(): void
    {
        $user = $this->createAuthorizedUser(700006);
        $request = $this->createRequest($user, 14);
        $file = $this->createFile([
            'equivalencia_id' => $request->id,
            'tipo' => Arquivo::TIPO_EMENTA,
            'nome' => 'ementa.pdf',
            'path' => 'requerimentos/ementa.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('equivalencias.req-file', [
                'group' => 14,
                'arquivo' => $file->id,
            ], false))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=ementa.pdf');
    }

    public function test_file_from_another_group_or_unknown_file_returns_not_found(): void
    {
        $user = $this->createAuthorizedUser(700007);
        $firstRequest = $this->createRequest($user, 15);
        $this->createRequest($user, 16);
        $file = $this->createFile([
            'equivalencia_id' => $firstRequest->id,
            'tipo' => Arquivo::TIPO_EMENTA,
            'nome' => 'ementa.pdf',
            'path' => 'requerimentos/ementa.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('equivalencias.req-file', [
                'group' => 16,
                'arquivo' => $file->id,
            ], false))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('equivalencias.req-file', [
                'group' => 15,
                'arquivo' => 999999,
            ], false))
            ->assertNotFound();
    }

    public function test_missing_physical_file_returns_not_found(): void
    {
        $user = $this->createAuthorizedUser(700008);
        $request = $this->createRequest($user, 17);
        $file = Arquivo::create([
            'equivalencia_id' => $request->id,
            'tipo' => Arquivo::TIPO_EMENTA,
            'nome' => 'arquivo-ausente.pdf',
            'path' => 'requerimentos/arquivo-ausente.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('equivalencias.req-file', [
                'group' => 17,
                'arquivo' => $file->id,
            ], false))
            ->assertNotFound();
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

    private function createRequest(User $user, int $group, array $courseOverrides = []): Aproveitamento
    {
        $required = Disciplina::create([
            'coddis' => 'MAC0110',
            'nomdis' => 'Introdução à Computação',
            'ies' => 'USP',
            'sglund' => 'IME',
            'criado_por_id' => $user->id,
            'alterado_por_id' => $user->id,
        ]);
        $course = Disciplina::create(array_merge([
            'coddis' => 'EXT100',
            'nomdis' => 'Programação',
            'ies' => 'Universidade Externa',
            'ano' => 2025,
            'semestre' => 1,
            'frequencia' => 90,
            'nota' => 8.5,
            'creditos' => 4,
            'carga_horaria' => 60,
            'criado_por_id' => $user->id,
            'alterado_por_id' => $user->id,
        ], collect($courseOverrides)->except('estado')->all()));

        return Aproveitamento::create([
            'grupo' => $group,
            'estado' => $courseOverrides['estado'] ?? null,
            'requerida_id' => $required->id,
            'cursada_id' => $course->id,
            'criado_por_id' => $user->id,
            'alterado_por_id' => $user->id,
        ]);
    }

    private function createFile(array $data): Arquivo
    {
        Storage::put($data['path'], '%PDF-1.4 test');

        return Arquivo::create($data);
    }
}
