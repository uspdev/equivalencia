<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DisciplinaUspSelect2Test extends DuskTestCase
{
    public function test_disciplina_usp_select_fetches_results(): void
    {
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
}
