<?php

use App\Http\Controllers\EquivalenciaController;
use App\Http\Controllers\WorkflowController;
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

Route::get('/', [WorkflowController::class, 'home'])->name('workflows.index');

Route::middleware(['auth'])->prefix('equivalencias')->group(function () {
    Route::middleware('can:equivalencias')->group(function () {
        Route::get('/', [EquivalenciaController::class, 'index'])
            ->name('equivalencias.index');
        Route::get('/{codcur}/{codhab}', [EquivalenciaController::class, 'show'])
            ->name('equivalencias.show');
        Route::post('/equivalencia/estado-edicao', [EquivalenciaController::class, 'saveEditModeState'])
            ->name('equivalencias.save-edit-mode-state');
        Route::post('/{codcur}/{codhab}', [EquivalenciaController::class, 'store'])
            ->name('equivalencias.store');
        Route::put('/{codcur}/{codhab}/{equivalencia}', [EquivalenciaController::class, 'update'])
            ->name('equivalencias.update');
        Route::delete('/{codcur}/{codhab}/{equivalencia}', [EquivalenciaController::class, 'destroy'])
            ->name('equivalencias.destroy');
        Route::post('/{codcur}/{codhab}/{equivalencia}/equivalencias', [EquivalenciaController::class, 'addEquivalencia'])
            ->name('equivalencias.add-equivalencia');
        Route::put('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}', [EquivalenciaController::class, 'updateEquivalencia'])
            ->name('equivalencias.update-equivalencia');
        Route::delete('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}', [EquivalenciaController::class, 'destroyEquivalencia'])
            ->name('equivalencias.destroy-equivalencia');
        Route::delete('/{codcur}/{codhab}/{equivalencia}/equivalencias/{equivalenciaFilha}/grupo', [EquivalenciaController::class, 'destroyEquivalenciaGrupo'])
            ->name('equivalencias.destroy-equivalencia-grupo');

        Route::get('/newreq', [AproveitamentoController::class, 'create'])->name('equivalencias.newreq-create');
        Route::post('/newreq', [AproveitamentoController::class, 'store'])->name('equivalencias.newreq-store');
        Route::get('/index',[AproveitamentoController::class, 'index'])->name('equivalencias.req-index');
        Route::get('/req/show/{group}',[AproveitamentoController::class, 'show'])->name('equivalencias.req-show');
        Route::get('/req/destroy/{group}',[AproveitamentoController::class, 'destroy'])->name('equivalencias.req-destroy');
    });

    Route::get('/createdefinition', [WorkflowController::class, 'createDefinition'])->name('workflows.create-definition');
    Route::post('/createdefinition', [WorkflowController::class, 'storeDefinition'])->name('workflows.store-definition');
    Route::get('/listdefinitions', [WorkflowController::class, 'listDefinitions'])->name('workflows.list-definitions');
    Route::get('/definition/{definition}', [WorkflowController::class, 'showDefinition'])->name('workflows.showDefinition');
    Route::delete('/definition/{definition}', [WorkflowController::class, 'destroyDefinition'])->name('workflows.destroyDefinition');
    Route::get('/editdefinition/{definition}', [WorkflowController::class, 'editDefinition'])->name('workflows.editDefinition');
    Route::post('/updatedefinition/', [WorkflowController::class, 'updateDefinition'])->name('workflows.updateDefinition');
    Route::get('/exportdefinition/{definitionName}',[WorkflowController::class,'exportDefinition'])->name('workflows.exportDefinition');

    Route::get('/viewcreateobject', [WorkflowController::class, 'viewCreateObject'])->name('workflows.viewCreateObject');
    Route::get('/createobject/{definitionName}', [WorkflowController::class, 'createObject'])->name('workflows.createObject');
    Route::post('/createobject/{definitionName}', [WorkflowController::class, 'submitForm']);
    Route::get('/object/{id}', [WorkflowController::class, 'showObject'])->name('workflows.showObject');
    Route::get('/object/{id}/form/{transition}', [WorkflowController::class, 'showForm'])->name('workflows.showForm');
    Route::post('/object/{id}', [WorkflowController::class, 'submitForm'])->name('workflows.showObject');
    Route::get('/showuserobjects', [WorkflowController::class, 'showUserObjects'])->name('workflows.show-user-objects');
    Route::post('/apply-transition/{id}', [WorkflowController::class, 'applyTransition'])->name('workflows.applyTransition');
    Route::delete('/delete-object/{object}', [WorkflowController::class, 'deleteObject'])->name('workflows.delete-object');

    Route::put('/listdefinitions/setuser', [WorkflowController::class, 'setUser'])->name('workflows.setuser');
    Route::get('/atendimentos', [WorkflowController::class, 'atendimentos'])->name('workflows.atendimentos');
});

// Permite usar Gate::check('user')na view 404
Route::fallback(function () {
    return view('errors.404');
});
