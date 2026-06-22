<?php

namespace Tests\Feature;

use App\Models\Disciplina;
use App\Replicado\Graduacao;
use Mockery;
use Tests\TestCase;

class DisciplinaReplicadoDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_usp_cursada_uses_official_replicado_data_instead_of_manual_name(): void
    {
        $graduacao = Mockery::mock(Graduacao::class);
        $graduacao->shouldReceive('obterDadosDisciplinaPorCodigoVersao')
            ->once()
            ->with('MAC0110', null)
            ->andReturn([
                'coddis' => 'MAC0110',
                'verdis' => 3,
                'nomdis' => 'Introdução à Computação',
                'creaul' => 4,
                'cretrb' => 1,
                'numhor' => 90,
                'sglund' => 'IME',
                'dtaatvdis' => '2020-01-01',
                'dtadtvdis' => null,
            ]);

        $this->app->instance(Graduacao::class, $graduacao);

        $dados = Disciplina::dadosDaCursadaPorFormulario([
            'is_usp' => true,
            'coddis' => 'mac0110',
            'nome_disciplina' => 'várias opções',
            'ies' => 'USP',
            'sglund' => 'PLANILHA',
        ]);

        $this->assertSame('MAC0110', $dados['coddis']);
        $this->assertSame(3, $dados['verdis']);
        $this->assertSame('Introdução à Computação', $dados['nomdis']);
        $this->assertSame(5, $dados['creditos']);
        $this->assertSame(90, $dados['carga_horaria']);
        $this->assertSame('USP', $dados['ies']);
        $this->assertSame('IME', $dados['sglund']);
        $this->assertTrue($dados['disciplina_ativa']);
    }
}
