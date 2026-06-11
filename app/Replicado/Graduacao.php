<?php

namespace App\Replicado;

use Illuminate\Support\Str;
use Uspdev\Replicado\DB;
use Uspdev\Replicado\Graduacao as GraduacaoReplicado;

class Graduacao extends GraduacaoReplicado
{
    public function buscarDisciplinas(string $term, int $limit = 50): array
    {
        $term = Str::upper(trim($term));

        if (mb_strlen($term) < 3 || ! preg_match('/^[A-Z0-9]+$/', $term) || ! hasReplicado()) {
            return [];
        }

        $limit = max(1, min($limit, 50));
        $query = "SELECT D1.*
                    FROM DISCIPLINAGR D1
                    INNER JOIN (
                        SELECT coddis, MAX(verdis) AS verdis
                        FROM DISCIPLINAGR
                        GROUP BY coddis
                    ) D2 ON D1.coddis = D2.coddis AND D1.verdis = D2.verdis
                    WHERE D1.coddis LIKE :coddis
                    AND D1.dtadtvdis IS NULL
                    AND D1.dtaatvdis IS NOT NULL
                    ORDER BY D1.coddis ASC
                    OFFSET 0 ROWS FETCH NEXT {$limit} ROWS ONLY";

        $disciplinas = DB::fetchAll($query, ['coddis' => $term.'%']);

        return is_array($disciplinas) ? $disciplinas : [];
    }

    public function buscarDisciplina(string $code): ?array
    {
        $code = Str::upper(trim($code));

        if ($code === '') {
            return null;
        }

        foreach ($this->buscarDisciplinas($code) as $disciplina) {
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
