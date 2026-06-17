<?php

namespace App\Replicado;

use Illuminate\Support\Str;
use Uspdev\Forms\Replicado\Graduacao as GraduacaoForms;
use Uspdev\Replicado\DB;
use Uspdev\Replicado\Graduacao as GraduacaoReplicado;

class Graduacao extends GraduacaoReplicado
{
    public function buscarDisciplina(string $code): ?array
    {
        $code = Str::upper(trim($code));

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            return null;
        }

        foreach (GraduacaoForms::procurarDisciplinas($code, 50) as $disciplina) {
            if (Str::upper(trim((string) ($disciplina['coddis'] ?? ''))) === $code) {
                return $disciplina;
            }
        }

        return null;
    }

    public function disciplinaExiste(string $code): bool
    {
        return $this->buscarDisciplina($code) !== null;
    }

    /**
     * Busca os dados cadastrais mais recentes da disciplina no Replicado.
     * Complementa a busca do select, retornando campos de programa, créditos e situação da disciplina.
     * Retorna null se o código for inválido, a consulta falhar ou nenhuma disciplina compatível for encontrada.
     */
    public function buscarDadosDisciplina(string $code): ?array
    {
        $code = Str::upper(trim($code));

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            return null;
        }

        try {
            $disciplinas = static::obterDisciplinas([$code]) ?? [];
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($disciplinas as $disciplina) {
            if (! is_array($disciplina)) {
                continue;
            }

            if (Str::upper(trim((string) ($disciplina['coddis'] ?? ''))) === $code) {
                return $disciplina;
            }
        }

        return null;
    }

    /**
     * Localiza uma disciplina cursada no histórico escolar do aluno para um período específico.
     * Retorna dados de nota/frequência do HISTESCOLARGR junto com ementa e créditos da DISCIPLINAGR.
     * Retorna null se os parâmetros forem inválidos ou não houver matrícula para aluno, disciplina, ano e semestre.
     */
    public function buscarDisciplinaCursadaNoHistorico(int $codpes, string $coddis, int $ano, int $semestre): ?array
    {
        $coddis = Str::upper(trim($coddis));

        if ($codpes <= 0 || ! preg_match('/^[A-Z0-9]+$/', $coddis) || ! in_array($semestre, [1, 2], true)) {
            return null;
        }

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
                D.dtadtvdis,
                D.objdis,
                D.pgmdis,
                D.pgmrsudis
            FROM HISTESCOLARGR H
            INNER JOIN DISCIPLINAGR D ON H.coddis = D.coddis AND H.verdis = D.verdis
            WHERE H.codpes = convert(int, :codpes)
                AND H.coddis = :coddis
                AND SUBSTRING(H.codtur, 1, 4) = :ano
                AND SUBSTRING(H.codtur, 5, 1) = :semestre
            ORDER BY H.codpgm DESC, H.verdis DESC, H.dtacrihst DESC";

        return DB::fetch($query, [
            'codpes' => $codpes,
            'coddis' => $coddis,
            'ano' => (string) $ano,
            'semestre' => (string) $semestre,
        ]) ?: null;
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
