<?php

use App\Http\Controllers\AproveitamentoAutomaticoController;
use App\Http\Controllers\AproveitamentoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ==========================================
// BLOCO 1: PAGINA INICIAL
// ==========================================
// Direciona a raiz da aplicacao para a tela inicial do fluxo de equivalencias.
Route::get('/', [AproveitamentoController::class, 'home'])->name('workflows.index');

// ==========================================
// BLOCO 2: EQUIVALENCIAS
// ==========================================
// Agrupa as rotas protegidas por autenticacao e permissao de acesso ao modulo.
Route::middleware(['auth', 'can:equivalencias'])
    ->prefix('equivalencias')
    ->name('equivalencias.')
    ->group(function () {

        // ==========================================
        // BLOCO 2.1: NOVO REQUERIMENTO
        // ==========================================
        // Mantem o fluxo de criacao de requerimentos, disciplinas e historico.
        Route::controller(AproveitamentoController::class)->group(function () {
            Route::get('/find/disciplinas/versoes', 'versoesDisciplina')->name('disciplina-versoes');
            Route::get('/newreq', 'create')->name('newreq-create');
            Route::post('/newreq/requerida', 'saveRequiredDiscipline')->name('newreq-required');
            Route::get('/newreq/disciplinas/create', 'createDiscipline')->name('newreq-discipline-create');
            Route::post('/newreq/disciplinas', 'storeDiscipline')->name('newreq-discipline-store');
            Route::get('/newreq/disciplinas/{disciplineId}/edit', 'editDiscipline')->name('newreq-discipline-edit');
            Route::put('/newreq/disciplinas/{disciplineId}', 'updateDiscipline')->name('newreq-discipline-update');
            Route::delete('/newreq/disciplinas/{disciplineId}', 'destroyDiscipline')->name('newreq-discipline-destroy');
            Route::post('/newreq/historico', 'saveHistory')->name('newreq-history');
            Route::post('/newreq', 'store')->name('newreq-store');
        });

        // ==========================================
        // BLOCO 2.2: APROVEITAMENTO AUTOMATICO
        // ==========================================
        // Rotas de listagem, exibicao e persistencia das equivalencias automaticas.
        Route::controller(AproveitamentoAutomaticoController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{codcur}/{codhab}', 'show')
                ->name('show')
                ->whereNumber('codcur')
                ->whereNumber('codhab');
            Route::post('/equivalencia/estado-edicao', 'saveEditModeState')->name('save-edit-mode-state');
            Route::post('/{codcur}/{codhab}', 'store')
                ->name('store')
                ->whereNumber('codcur')
                ->whereNumber('codhab');
            Route::put('/{codcur}/{codhab}/{equivalencia}', 'update')
                ->name('update')
                ->whereNumber('codcur')
                ->whereNumber('codhab');
            Route::delete('/{codcur}/{codhab}/{equivalencia}', 'destroy')->name('destroy');
            Route::post('/{codcur}/{codhab}/{equivalencia}/equivalencias', 'addEquivalencia')
                ->name('add-equivalencia');
            Route::put('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}', 'updateEquivalencia')
                ->name('update-equivalencia');
            Route::delete('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}', 'destroyEquivalencia')
                ->name('destroy-equivalencia');
            Route::delete('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}/grupo', 'destroyEquivalenciaGrupo')
                ->name('destroy-equivalencia-grupo');
        });

        // ==========================================
        // BLOCO 2.3: REQUERIMENTOS
        // ==========================================
        // Rotas de consulta, visualizacao de arquivos e remocao de requerimentos.
        Route::controller(AproveitamentoController::class)->group(function () {
            Route::get('/index', 'index')->name('req-index');
            Route::get('/req/show/{group}', 'show')->name('req-show');
            Route::get('/req/show/{group}/arquivos/{arquivo}', 'showFile')->name('req-file');
            Route::get('/req/destroy/{group}', 'destroy')->name('req-destroy');
        });
    });

// ==========================================
// BLOCO 3: FALLBACK
// ==========================================
// Permite usar Gate::check('user') na view 404.
Route::fallback(function () {
    return view('errors.404');
});
