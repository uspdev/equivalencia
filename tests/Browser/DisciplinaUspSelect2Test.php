<?php

namespace Tests\Browser;

use App\Enums\EquivalenciaEstado;
use App\Models\Aproveitamento;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DisciplinaUspSelect2Test extends DuskTestCase
{
    public function test_disciplina_usp_select_fetches_results(): void
    {
        $user = self::getUser('admin');
        Aproveitamento::where('criado_por_id', $user->id)
            ->where('estado', EquivalenciaEstado::RASCUNHO->value)
            ->delete();

        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs(self::getUser('admin'))
                ->visitRoute('equivalencias.newreq-create')
                ->waitFor('.select2-container', 10)
                ->click('.select2-container')
                ->waitFor('.select2-search__field', 5)
                ->type('.select2-search__field', 'STT')
                ->waitForText('STT0177 - Geomática Aplicada', 10)
                ->assertSee('STT0177 - Geomática Aplicada');
        });
    }

    public function test_disciplina_usp_select_works_inside_create_modal(): void
    {
        $user = self::getUser('admin');
        Aproveitamento::where('criado_por_id', $user->id)
            ->where('estado', EquivalenciaEstado::RASCUNHO->value)
            ->delete();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visitRoute('equivalencias.newreq-create')
                ->waitFor('.select2-container', 10)
                ->click('.select2-container')
                ->waitFor('.select2-search__field', 5)
                ->type('.select2-search__field', 'STT')
                ->waitForText('STT0177 - Geomática Aplicada', 10)
                ->click('.select2-results__option')
                ->waitUntil('!document.getElementById("add-discipline-button").disabled', 5)
                ->click('#add-discipline-button')
                ->waitFor('#create-discipline-modal.show', 5)
                ->click('#create-discipline-modal .select2-container')
                ->waitFor('#create-discipline-modal .select2-search__field', 5)
                ->type('#create-discipline-modal .select2-search__field', 'STT')
                ->waitForText('STT0177 - Geomática Aplicada', 10)
                ->assertPresent('#create-discipline-modal .select2-dropdown');
        });
    }
}
