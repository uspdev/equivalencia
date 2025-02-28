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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/workflows', [WorkflowController::class, 'home'])->name('workflows.index');

Route::get('/workflows/createdefinition', [WorkflowController::class, 'createDefinition'])->name('workflows.create-definition');
Route::post('/workflows/createdefinition', [WorkflowController::class, 'storeDefinition'])->name('workflows.store-definition');
Route::get('/workflows/listdefinitions', [WorkflowController::class, 'listDefinitions'])->name('workflows.list-definitions');
Route::get('/workflows/definition/{definition}', [WorkflowController::class, 'showDefinition'])->name('workflows.showDefinition');
Route::delete('/workflows/definition/{definition}', [WorkflowController::class, 'destroyDefinition'])->name('workflows.destroyDefinition');
Route::get('/workflows/editdefinition/{definition}', [WorkflowController::class, 'editDefinition'])->name('workflows.editDefinition');
Route::post('/workflows/updatedefinition/', [WorkflowController::class, 'updateDefinition'])->name('workflows.updateDefinition');

Route::get('/workflows/viewcreateobject', [WorkflowController::class, 'viewCreateObject'])->name('workflows.viewCreateObject');
Route::get('/workflows/createobject/{definitionName}', [WorkflowController::class, 'createObject'])->name('workflows.createObject');
Route::post('/workflows/createobject/{definitionName}', [WorkflowController::class, 'submitForm']);
Route::get('/workflows/object/{id}', [WorkflowController::class, 'showObject'])->name('workflows.showObject');
Route::post('/workflows/object/{id}', [WorkflowController::class, 'submitForm'])->name('workflows.showObject');
Route::get('/workflows/showuserobjects', [WorkflowController::class, 'showUserObjects'])->name('workflows.show-user-objects');
Route::post('workflows/apply-transition/{id}', [WorkflowController::class, 'applyTransition'])->name('workflows.applyTransition');
Route::delete('/workflows/delete-object/{object}', [WorkflowController::class, 'deleteObject'])->name('workflows.delete-object');

Route::put('/workflows/listdefinitions/setuser', [WorkflowController::class, 'setUser'])->name('workflows.setuser');
Route::get('/workflows/atendimentos', [WorkflowController::class, 'atendimentos'])->name('workflows.atendimentos');

// Permite usar Gate::check('user')na view 404
Route::fallback(function(){
    return view('errors.404');
 });
