<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkflowController;

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

Route::middleware(['auth'])->group(function () {
    Route::get('/createdefinition', [WorkflowController::class, 'createDefinition'])->name('workflows.create-definition');
    Route::post('/createdefinition', [WorkflowController::class, 'storeDefinition'])->name('workflows.store-definition');
    Route::get('/listdefinitions', [WorkflowController::class, 'listDefinitions'])->name('workflows.list-definitions');
    Route::get('/definition/{definition}', [WorkflowController::class, 'showDefinition'])->name('workflows.showDefinition');
    Route::delete('/definition/{definition}', [WorkflowController::class, 'destroyDefinition'])->name('workflows.destroyDefinition');
    Route::get('/editdefinition/{definition}', [WorkflowController::class, 'editDefinition'])->name('workflows.editDefinition');
    Route::post('/updatedefinition/', [WorkflowController::class, 'updateDefinition'])->name('workflows.updateDefinition');

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
