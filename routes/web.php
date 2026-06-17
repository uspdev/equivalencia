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
Route::get('/', [AproveitamentoController::class, 'home'])->name('workflows.index');

Route::middleware(['auth'])->prefix('equivalencias')->group(function () {
    Route::middleware('can:equivalencias')->group(function () {
        Route::get('/newreq', [AproveitamentoController::class, 'create'])->name('equivalencias.newreq-create');
        Route::post('/newreq/requerida', [AproveitamentoController::class, 'saveRequiredDiscipline'])
            ->name('equivalencias.newreq-required');
        Route::get('/newreq/disciplinas/create', [AproveitamentoController::class, 'createDiscipline'])
            ->name('equivalencias.newreq-discipline-create');
        Route::post('/newreq/disciplinas', [AproveitamentoController::class, 'storeDiscipline'])
            ->name('equivalencias.newreq-discipline-store');
        Route::get('/newreq/disciplinas/{disciplineId}/edit', [AproveitamentoController::class, 'editDiscipline'])
            ->name('equivalencias.newreq-discipline-edit');
        Route::put('/newreq/disciplinas/{disciplineId}', [AproveitamentoController::class, 'updateDiscipline'])
            ->name('equivalencias.newreq-discipline-update');
        Route::delete('/newreq/disciplinas/{disciplineId}', [AproveitamentoController::class, 'destroyDiscipline'])
            ->name('equivalencias.newreq-discipline-destroy');
        Route::post('/newreq', [AproveitamentoController::class, 'store'])->name('equivalencias.newreq-store');

        Route::get('/', [AproveitamentoAutomaticoController::class, 'index'])
            ->name('equivalencias.index');
        Route::get('/{codcur}/{codhab}', [AproveitamentoAutomaticoController::class, 'show'])
            ->name('equivalencias.show')
            ->whereNumber('codcur')
            ->whereNumber('codhab');
        Route::post('/equivalencia/estado-edicao', [AproveitamentoAutomaticoController::class, 'saveEditModeState'])
            ->name('equivalencias.save-edit-mode-state');
        Route::post('/{codcur}/{codhab}', [AproveitamentoAutomaticoController::class, 'store'])
            ->name('equivalencias.store')
            ->whereNumber('codcur')
            ->whereNumber('codhab');
        Route::put('/{codcur}/{codhab}/{equivalencia}', [AproveitamentoAutomaticoController::class, 'update'])
            ->name('equivalencias.update')->whereNumber('codcur')->whereNumber('codhab');
        Route::delete('/{codcur}/{codhab}/{equivalencia}', [AproveitamentoAutomaticoController::class, 'destroy'])
            ->name('equivalencias.destroy');
        Route::post('/{codcur}/{codhab}/{equivalencia}/equivalencias', [AproveitamentoAutomaticoController::class, 'addEquivalencia'])
            ->name('equivalencias.add-equivalencia');
        Route::put('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}', [AproveitamentoAutomaticoController::class, 'updateEquivalencia'])
            ->name('equivalencias.update-equivalencia');
        Route::delete('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}', [AproveitamentoAutomaticoController::class, 'destroyEquivalencia'])
            ->name('equivalencias.destroy-equivalencia');
        Route::delete('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}/grupo', [AproveitamentoAutomaticoController::class, 'destroyEquivalenciaGrupo'])
            ->name('equivalencias.destroy-equivalencia-grupo');

        Route::get('/index',[AproveitamentoController::class, 'index'])->name('equivalencias.req-index');
        Route::get('/req/show/{group}',[AproveitamentoController::class, 'show'])->name('equivalencias.req-show');
        Route::get('/req/show/{group}/arquivos/{arquivo}', [AproveitamentoController::class, 'showFile'])
            ->name('equivalencias.req-file');
        Route::get('/req/destroy/{group}',[AproveitamentoController::class, 'destroy'])->name('equivalencias.req-destroy');
    });
});

// Permite usar Gate::check('user')na view 404
Route::fallback(function () {
    return view('errors.404');
});
