<?php

namespace Tests\Unit;

use App\Models\Aproveitamento;
use App\Models\Disciplina;
use App\Replicado\Graduacao;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class DisciplinaVigenciaVersaoTest extends TestCase
{
    public function test_it_maps_the_selected_version_validity_to_completed_usp_discipline(): void
    {
        $cursada = new Disciplina([
            'coddis' => 'MAC0110',
            'verdis' => 3,
            'ies' => 'USP',
        ]);
        $cursada->id = 20;

        $equivalencia = new Aproveitamento();
        $equivalencia->setRelation('cursada', $cursada);

        $requerida = new Disciplina();
        $requerida->setRelation('equivalentes', new Collection([$equivalencia]));

        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldReceive('listarVersoesDisciplinaParaSelect')
            ->once()
            ->with('MAC0110')
            ->andReturn([
                ['verdis' => 4, 'vigencia' => 'desde 01/01/2026'],
                ['verdis' => 3, 'vigencia' => '01/01/2020 até 31/12/2025'],
            ]);
        $this->app->instance(Graduacao::class, $graduacao);

        $this->assertSame(
            [20 => '01/01/2020 até 31/12/2025'],
            Disciplina::vigenciasDasVersoesDasCursadas(new Collection([$requerida]))
        );
    }
}
