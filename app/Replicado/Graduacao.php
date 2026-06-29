<?php

namespace App\Replicado;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Uspdev\Replicado\DB;
use Uspdev\Replicado\Estrutura;
use Uspdev\Replicado\Graduacao as GraduacaoReplicado;

class Graduacao extends GraduacaoReplicado
{
    private array $unidadesPorCoddis = [];

    /**
     * Obtém os dados de uma disciplina ativa.
     * A busca por prefixo vem do uspdev/forms, mas este método só retorna correspondência exata de código.
     */
    public function obterDadosDisciplinaAtivaPorCodigo(string $code): array
    {
        $disciplina = $this->obterDadosDisciplinaPorCodigo($code);
        // Se a disciplina não estiver ativa, retorna array vazio.
        return ! empty($disciplina['dtaatvdis'] ?? null) && empty($disciplina['dtadtvdis'] ?? null)
            ? $disciplina
            : [];
    }

    /**
     * Verifica se há uma disciplina ativa com o código informado.
     * Usado nas validações para rejeitar códigos que não correspondam a uma opção USP válida.
     */
    public function existeDisciplinaAtivaPorCodigo(string $code): bool
    {
        return ! empty($this->obterDadosDisciplinaAtivaPorCodigo($code));
    }

    /**
     * Busca os dados cadastrais mais recentes da disciplina no Replicado.
     * Complementa a busca do select, retornando créditos e situação da disciplina.
     * Retorna array vazio se o código for inválido, a consulta falhar ou nenhuma disciplina compatível for encontrada.
     */
    public function obterDadosDisciplinaPorCodigo(string $code): array
    {
        $code = Str::upper(trim($code));

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            return [];
        }
        // Ordena as versões de forma que as mais recentes e ativas venham primeiro.
        $query = "SELECT TOP 1 D1.*
                    FROM DISCIPLINAGR D1
                    WHERE D1.coddis = :coddis
                    ORDER BY
                    CASE WHEN D1.dtadtvdis IS NULL AND D1.dtaatvdis IS NOT NULL THEN 0 ELSE 1 END,
                    CASE WHEN D1.dtadtvdis IS NULL THEN 0 ELSE 1 END,
                    CASE WHEN D1.dtaatvdis IS NOT NULL THEN 0 ELSE 1 END,
                    D1.dtaatvdis DESC,
                    D1.verdis DESC";

        try {
            return $this->adicionarUnidadeDaDisciplina(DB::fetch($query, ['coddis' => $code]) ?: []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Lista todas as versões cadastradas para uma disciplina.
     * Junto de sua data de ativação e desativação.
     */
    public function listarVersoesDisciplina(string $code): array
    {
        $code = Str::upper(trim($code));

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            return [];
        }

        // Ordena as versões de forma que as mais recentes e ativas venham primeiro.
        $query = "SELECT D1.coddis, D1.verdis, D1.dtaatvdis, D1.dtadtvdis
                    FROM DISCIPLINAGR D1
                    WHERE D1.coddis = :coddis
                    ORDER BY
                    CASE WHEN D1.dtadtvdis IS NULL AND D1.dtaatvdis IS NOT NULL THEN 0 ELSE 1 END,
                    CASE WHEN D1.dtadtvdis IS NULL THEN 0 ELSE 1 END,
                    CASE WHEN D1.dtaatvdis IS NOT NULL THEN 0 ELSE 1 END,
                    D1.dtaatvdis DESC,
                    D1.verdis DESC";

        try {
            return DB::fetchAll($query, ['coddis' => $code]) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
    /**
     * Obtém as versões de uma disciplina USP e as formata para uso em campos
     * de seleção da interface.
     *
     * @param string $coddis Código da disciplina USP.
     * @return array<int, array{
     *     id:int,
     *     verdis:int,
     *     data_ativacao:?string,
     *     data_desativacao:?string,
     *     vigencia:?string,
     *     text:string,
     *     label:string
     * }>
     */
    public function listarVersoesDisciplinaParaSelect(string $coddis): array
    {
        return collect($this->listarVersoesDisciplina($coddis))
            ->filter(fn($disciplina) => is_array($disciplina) && isset($disciplina['verdis']))
            ->map(fn(array $disciplina) => $this->formatarVersaoDisciplinaParaSelect($disciplina))
            ->values()
            ->all();
    }

    private function formatarVersaoDisciplinaParaSelect(array $disciplina): array
    {
        $verdis = (int) $disciplina['verdis'];

        $dataAtivacao = $this->formatarDataVigencia($disciplina['dtaatvdis'] ?? null);
        $dataDesativacao = $this->formatarDataVigencia($disciplina['dtadtvdis'] ?? null);
        $vigencia = $this->formatarVigencia($dataAtivacao, $dataDesativacao);
        $text = $this->formatarTextoVersao($verdis, $vigencia);

        return [
            'id' => $verdis,
            'verdis' => $verdis,
            'data_ativacao' => $dataAtivacao,
            'data_desativacao' => $dataDesativacao,
            'vigencia' => $vigencia,
            'text' => $text,
            'label' => $text,
        ];
    }

    private function formatarDataVigencia(mixed $data): ?string
    {
        return filled($data)
            ? Carbon::parse($data)->format('d/m/Y')
            : null;
    }

    private function formatarVigencia(?string $dataAtivacao, ?string $dataDesativacao): ?string
    {
        return match (true) {
            $dataAtivacao && $dataDesativacao => "{$dataAtivacao} até {$dataDesativacao}",
            (bool) $dataAtivacao => "desde {$dataAtivacao}",
            (bool) $dataDesativacao => "até {$dataDesativacao}",
            default => null,
        };
    }

    private function formatarTextoVersao(int $verdis, ?string $vigencia): string
    {
        return "Versão {$verdis}" . ($vigencia ? " — {$vigencia}" : '');
    }

    /**
     * Busca os dados cadastrais de uma disciplina por código e, quando informado, versão.
     */
    public function obterDadosDisciplinaPorCodigoVersao(string $code, ?int $verdis = null): array
    {
        $code = Str::upper(trim($code));

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            return [];
        }

        if ($verdis === null) {
            return $this->obterDadosDisciplinaPorCodigo($code);
        }

        $query = "SELECT TOP 1 D1.*
                    FROM DISCIPLINAGR D1
                    WHERE D1.coddis = :coddis
                        AND D1.verdis = convert(int, :verdis)";

        try {
            return $this->adicionarUnidadeDaDisciplina(DB::fetch($query, [
                'coddis' => $code,
                'verdis' => $verdis,
            ]) ?: []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Verifica se há uma disciplina com o código e a versão informados.
     */
    public function existeDisciplinaPorCodigoVersao(string $code, ?int $verdis = null): bool
    {
        return ! empty($this->obterDadosDisciplinaPorCodigoVersao($code, $verdis));
    }

    /**
     * Complementa os dados cadastrais da disciplina com a unidade responsável.
     *
     * A biblioteca já expõe Estrutura::obterUnidade($codund), mas a busca de
     * disciplinas não retorna a unidade. Por isso obtemos o codclg no vínculo
     * DISCIPGRCODIGO e usamos a API da própria lib para carregar a sigla.
     */
    private function adicionarUnidadeDaDisciplina(array $disciplina): array
    {
        $coddis = Str::upper(trim((string) ($disciplina['coddis'] ?? '')));

        if ($coddis === '') {
            return $disciplina;
        }

        $unidade = $this->unidadeDaDisciplina($coddis);

        if (! $unidade) {
            return $disciplina;
        }
        // Filtra valores nulos ou vazios para evitar sobrescrever dados válidos com informações incompletas.
        return array_merge($disciplina, array_filter([
            'codclg' => $unidade['codund'] ?? null,
            'sglund' => $unidade['sglund'] ?? null,
        ], static fn($valor) => $valor !== null && $valor !== ''));
    }

    private function unidadeDaDisciplina(string $coddis): array
    {
        if (array_key_exists($coddis, $this->unidadesPorCoddis)) {
            return $this->unidadesPorCoddis[$coddis];
        }

        try {
            $vinculo = DB::fetch(
                'SELECT TOP 1 codclg FROM DISCIPGRCODIGO WHERE coddis = :coddis ORDER BY codclg',
                ['coddis' => $coddis]
            ) ?: [];

            $codund = (int) ($vinculo['codclg'] ?? 0);

            $this->unidadesPorCoddis[$coddis] = $codund > 0
                ? (Estrutura::obterUnidade($codund) ?: [])
                : [];
        } catch (\Throwable $e) {
            $this->unidadesPorCoddis[$coddis] = [];
        }

        return $this->unidadesPorCoddis[$coddis];
    }

    /**
     * Localiza uma disciplina cursada no histórico escolar do aluno para um período específico.
     * Retorna dados de nota/frequência do HISTESCOLARGR junto com créditos da DISCIPLINAGR.
     * Retorna array vazio se a consulta falhar ou não houver matrícula para aluno, disciplina e período.
     */
    public function obterDisciplinaCursadaPorAlunoEmPeriodoCodtur(
        int $codpes,
        string $coddis,
        string $codtur,
        ?int $verdis = null
    ): array {
        $coddis = Str::upper(trim($coddis));
        $codtur = trim($codtur);

        $query = "SELECT TOP 1
                H.codpes,
                H.codpgm,
                H.coddis,
                H.verdis,
                H.codtur,
                H.notfim,
                H.notfim2,
                H.frqfim,
                H.rstfim,
                D.nomdis,
                D.creaul,
                D.cretrb,
                D.dtaatvdis,
                D.dtadtvdis
            FROM HISTESCOLARGR H
            INNER JOIN DISCIPLINAGR D ON H.coddis = D.coddis AND H.verdis = D.verdis
            WHERE H.codpes = convert(int, :codpes)
                AND H.coddis = :coddis
                AND H.codtur LIKE :codtur";

        $params = [
            'codpes' => $codpes,
            'coddis' => $coddis,
            'codtur' => $codtur . '%',
        ];

        if ($verdis !== null) {
            $query .= "
                AND H.verdis = convert(int, :verdis)";
            $params['verdis'] = $verdis;
        }

        $query .= "
            ORDER BY H.codpgm DESC, H.verdis DESC, H.dtacrihst DESC";

        try {
            return DB::fetch($query, $params) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @deprecated Use obterDisciplinaCursadaPorAlunoEmPeriodoCodtur().
     */
    public function obterDisciplinaCursadaPorAlunoEmPeriodo(int $codpes, string $coddis, int $ano, int $semestre): array
    {
        return $this->obterDisciplinaCursadaPorAlunoEmPeriodoCodtur(
            $codpes,
            $coddis,
            sprintf('%04d%d', $ano, $semestre)
        );
    }

    /**
     * Lista os cursos e habilitações da unidade
     *
     * Refatorado de obterCursosHabilitacoes
     *
     * Código copiado do Datagrad
     *
     * @return array
     *
     * @author Masaki K Neto, em 9/5/2023
     */
    public static function listarCursosHabilitacoes()
    {

        $codhabs = config('equivalencia.codhabs');
        $condicaoCodhab = '';
        if (count($codhabs) == 1) {
            $condicaoCodhab = 'H.codhab = ' . $codhabs[0]; // EESC: Colocado aqui para remover os cursos de dupla formação com IAU.
        } else {
            for ($i = 0; $i < count($codhabs); $i++) {
                $condicaoCodhab .= 'RIGHT(H.codhab, 1) = ' . $codhabs[$i] . ' OR '; // ECA: Colocado aqui para considerar outras habilitações.
            }
            $condicaoCodhab = substr($condicaoCodhab, 0, strlen($condicaoCodhab) - 3);
        }

        $query = " SELECT C.*, H.* FROM CURSOGR C
        INNER JOIN HABILITACAOGR H ON C.codcur = H.codcur
        WHERE C.codclg IN (__codundclgs__)
            AND ( (C.dtaatvcur IS NOT NULL) AND (C.dtadtvcur IS NULL) ) -- curso ativo
            AND ( (H.dtaatvhab IS NOT NULL) AND (H.dtadtvhab IS NULL) ) -- habilitação ativa
            AND ($condicaoCodhab)
        ORDER BY C.nomcur, H.nomhab ASC";

        return DB::fetchAll($query);
    }

    /**
     * Obtém um curso/habilitação específico pelo contexto informado.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     * @return array|null
     */
    public static function obterCursoHabilitacao(int $codcur, int $codhab): ?array
    {
        foreach (static::listarCursosHabilitacoes() as $curso) {
            if ((int) ($curso['codcur'] ?? 0) === $codcur && (int) ($curso['codhab'] ?? 0) === $codhab) {
                return $curso;
            }
        }

        return null;
    }
}
