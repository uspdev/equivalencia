<?php

namespace App\Replicado;

use Illuminate\Support\Str;
use Uspdev\Forms\Replicado\Graduacao as GraduacaoForms;
use Uspdev\Replicado\DB;
use Uspdev\Replicado\Graduacao as GraduacaoReplicado;

class Graduacao extends GraduacaoReplicado
{
    /**
     * Obtém os dados de uma disciplina ativa.
     * A busca por prefixo vem do uspdev/forms, mas este método só retorna correspondência exata de código.
     */
    public function obterDadosDisciplinaAtivaPorCodigo(string $code): array
    {
        $code = Str::upper(trim($code));

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            return [];
        }

        foreach (GraduacaoForms::procurarDisciplinas($code, 50) as $disciplina) {
            if (Str::upper(trim((string) ($disciplina['coddis'] ?? ''))) === $code) {
                return $disciplina;
            }
        }

        return [];
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

        try {
            $disciplinas = static::obterDisciplinas([$code]) ?? [];
        } catch (\Throwable $e) {
            return [];
        }

        foreach ($disciplinas as $disciplina) {
            if (! is_array($disciplina)) {
                continue;
            }

            if (Str::upper(trim((string) ($disciplina['coddis'] ?? ''))) === $code) {
                return $disciplina;
            }
        }

        return [];
    }

    /**
     * Lista todas as versões cadastradas para uma disciplina.
     */
    public function listarVersoesDisciplina(string $code): array
    {
        $code = Str::upper(trim($code));

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            return [];
        }

        $query = "SELECT D1.coddis, D1.verdis
                    FROM DISCIPLINAGR D1
                    WHERE D1.coddis = :coddis
                    ORDER BY D1.verdis DESC";

        try {
            return DB::fetchAll($query, ['coddis' => $code]) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
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
            return DB::fetch($query, [
                'coddis' => $code,
                'verdis' => $verdis,
            ]) ?: [];
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
     * Localiza uma disciplina cursada no histórico escolar do aluno para um período específico.
     * Retorna dados de nota/frequência do HISTESCOLARGR junto com créditos da DISCIPLINAGR.
     * Retorna array vazio se a consulta falhar ou não houver matrícula para aluno, disciplina e período.
     */
    public function obterDisciplinaCursadaPorAlunoEmPeriodoCodtur(
        int $codpes,
        string $coddis,
        string $codtur,
        ?int $verdis = null
    ): array
    {
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
            $condicaoCodhab = 'H.codhab = '.$codhabs[0]; // EESC: Colocado aqui para remover os cursos de dupla formação com IAU.
        } else {
            for ($i = 0; $i < count($codhabs); $i++) {
                $condicaoCodhab .= 'RIGHT(H.codhab, 1) = '.$codhabs[$i].' OR '; // ECA: Colocado aqui para considerar outras habilitações.
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
