use App\Models\Disciplina;
use App\Models\Aproveitamento;
use Illuminate\Support\Facades\DB;


$arquivos = glob(storage_path('app/TAA/*.json'));
if ($arquivos === false) {
    throw new RuntimeException('Falha ao listar os arquivos em storage/app/TAA.');
}

if (count($arquivos) === 0) {
    throw new RuntimeException('Nenhum arquivo JSON encontrado em storage/app/TAA.');
}

$totalArquivos = 0;
$totalRequeridas = 0;
$totalVinculosCriados = 0;
$totalGruposCriados = 0;
$totalGruposJaExistentes = 0;
$totalSemDadosReplicado = 0;
$ignoradas = 0;

// Compara duas listas de IDs sem considerar ordem.
$mesmaListaDeIds = function (array $a, array $b): bool {
    sort($a);
    sort($b);

    return $a === $b;
};
// Remove chaves com valores nulos ou string vazia, para evitar sobrescrever dados existentes com "vazio".
$dadosPreenchidos = static function (array $dados): array {
    return array_filter($dados, static fn($valor) => $valor !== null && $valor !== '');
};

// Localiza uma disciplina respeitando a identidade adotada pelo sistema:
// ies + coddis + verdis.
// Para disciplinas USP, versões diferentes podem representar disciplinas
// distintas do ponto de vista do sistema de equivalências.
// Quando uma versão é informada mas não existe cadastro correspondente,
// tenta reutilizar um registro legado sem versão definida.
$obterPorIdentidade = function (array $dados): ?Disciplina {
    if (($dados['ies'] ?? null) === null || ($dados['coddis'] ?? null) === null) {
        return null;
    }

    $consultaBase = Disciplina::query()
        ->where('ies', $dados['ies'])
        ->where('coddis', $dados['coddis']);

    if (array_key_exists('verdis', $dados) && $dados['verdis'] !== null && $dados['verdis'] !== '') {
        $comVersao = (clone $consultaBase)
            ->where('verdis', $dados['verdis'])
            ->first();

        if ($comVersao) {
            return $comVersao;
        }

        return (clone $consultaBase)
            ->whereNull('verdis')
            ->orderBy('id')
            ->first();
    }

    return $consultaBase
        ->whereNull('verdis')
        ->orderBy('id')
        ->first();
};

$obterOuCriarPorIdentidade = function (array $dados) use ($obterPorIdentidade, $dadosPreenchidos): Disciplina {
    $disciplina = $obterPorIdentidade($dados);

    if (! $disciplina) {
        return Disciplina::create($dados);
    }

    $disciplina->update($dadosPreenchidos($dados));

    return $disciplina;
};

$obterOuCriarRequerida = function (string $codigoReq) use ($obterOuCriarPorIdentidade, &$totalSemDadosReplicado): Disciplina {
    $dadosReplicado = Disciplina::dadosDaRequeridaPorCoddis($codigoReq);

    $dados = [
        'coddis' => $dadosReplicado['coddis'] ?? strtoupper($codigoReq),
        'nomdis' => $dadosReplicado['nomdis'] ?? null,
        'verdis' => $dadosReplicado['verdis'] ?? null,
        'creditos' => $dadosReplicado['creditos'] ?? null,
        'carga_horaria' => $dadosReplicado['carga_horaria'] ?? null,
        'sglund' => $dadosReplicado['sglund'] ?? null,
        'ies' => $dadosReplicado['ies'] ?? 'USP',
        'disciplina_ativa' => $dadosReplicado['disciplina_ativa'] ?? null,
    ];

    if (empty($dadosReplicado['nomdis'])) {
        $totalSemDadosReplicado++;
    }

    return $obterOuCriarPorIdentidade($dados);
};

$obterOuCriarCursada = function (string $codigoCur, ?int $periodo) use ($obterOuCriarPorIdentidade, &$totalSemDadosReplicado): Disciplina {
    $dadosFormulario = [
        'is_usp' => true,
        'coddis' => $codigoCur,
        'nome_disciplina' => null,
        'ies' => 'USP',
        'semestre' => $periodo,
    ];

    $dadosNormalizados = Disciplina::dadosDaCursadaPorFormulario($dadosFormulario);

    if (empty($dadosNormalizados['nomdis'])) {
        $totalSemDadosReplicado++;
    }

    return $obterOuCriarPorIdentidade($dadosNormalizados);
};

foreach ($arquivos as $caminho) {
    $json = file_get_contents($caminho);
    if ($json === false) {
        echo "Falha ao ler o arquivo: {$caminho}\n";
        $ignoradas++;

        continue;
    }

    $dados = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'JSON inválido em ' . basename($caminho) . ': ' . json_last_error_msg() . "\n";
        $ignoradas++;

        continue;
    }

    if (! is_array($dados) || count($dados) === 0) {
        echo 'JSON vazio ou fora do formato esperado em ' . basename($caminho) . "\n";
        $ignoradas++;

        continue;
    }

    // Primeiro item: { "curso": "Engenharia Aeronáutica (18070/0)" }
    $primeiroItem = is_array($dados[0] ?? null) ? $dados[0] : [];

    $cursoRaw = null;

    // Tenta encontrar a chave "curso" mesmo com BOM/variações de capitalização.
    foreach ($primeiroItem as $chave => $valor) {
        if (! is_string($chave)) {
            continue;
        }

        $chaveNormalizada = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/u', '', $chave)));
        if ($chaveNormalizada === 'curso' && is_string($valor) && trim($valor) !== '') {
            $cursoRaw = trim($valor);
            break;
        }
    }

    // Fallback: usa o primeiro valor string que pareça "Nome do Curso (codcur/codhab)".
    if ($cursoRaw === null) {
        foreach ($primeiroItem as $valor) {
            if (is_string($valor) && preg_match('/\(\s*\d+\s*\/\s*\d+\s*\)/u', $valor)) {
                $cursoRaw = trim($valor);
                break;
            }
        }
    }

    if (! is_string($cursoRaw) || trim($cursoRaw) === '') {
        echo 'Primeiro item inválido em ' . basename($caminho) . ": curso ausente.\n";
        $ignoradas++;

        continue;
    }

    if (! preg_match('/^\s*(.*?)\s*\(\s*(\d+)\s*\/\s*(\d+)\s*\)\s*$/u', $cursoRaw, $mCurso)) {
        echo 'Formato de curso inválido em ' . basename($caminho) . ": {$cursoRaw}\n";
        $ignoradas++;

        continue;
    }

    $nomcur = trim($mCurso[1]);
    $codcur = (int) $mCurso[2];
    $codhab = (int) $mCurso[3];

    // Ignora o primeiro item (curso) e processa apenas disciplinas
    $linhas = array_slice($dados, 1);

    foreach ($linhas as $i => $linha) {
        if (! is_array($linha)) {
            echo 'Erro em ' . basename($caminho) . ' (linha ' . ($i + 2) . ": formato inválido.\n";
            $ignoradas++;

            continue;
        }

        // Extrair período de algo como "1º Período"
        $periodoTexto = $linha['periodo'] ?? null;
        $periodo = null;
        if (is_string($periodoTexto) || is_numeric($periodoTexto)) {
            if (preg_match('/(\d+)/u', (string) $periodoTexto, $mPeriodo)) {
                $periodo = (int) $mPeriodo[1];
            }
        }

        $codigoReq = isset($linha['codigo_disciplina_requerida'])
            ? preg_replace('/\s+/', '', (string) $linha['codigo_disciplina_requerida'])
            : '';

        if ($codigoReq === '') {
            echo 'Erro em ' . basename($caminho) . ' (linha ' . ($i + 2) . ": código requerido vazio.\n";
            $ignoradas++;

            continue;
        }

        if (strlen($codigoReq) > 7) {
            echo 'Erro em ' . basename($caminho) . ' (linha ' . ($i + 2) . ": código requerido inválido ({$codigoReq}).\n";
            $ignoradas++;

            continue;
        }

        $requerida = $obterOuCriarRequerida($codigoReq);
        $totalRequeridas++;

        $codigosCursadaRaw = (string) ($linha['codigo_disciplina_cursada'] ?? '');
        // Uma mesma equivalência pode ser formada por mais de uma disciplina.
        // Exemplos aceitos:
        // SCC0210 + SCC0211
        // SCC0210 e SCC0211
        // SCC0210 ou SCC0211
        // Todos os códigos encontrados serão associados ao mesmo grupo.
        $codigosCursada = preg_split('/\s*(?:\+|\be\b|\bou\b|\/|,|;)\s*/ui', $codigosCursadaRaw, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($codigosCursada)) {
            $codigosCursada = [];
        }

        $cursadasIdsDoGrupo = [];

        // Cada disciplina cursada gera um vínculo dentro do grupo.
        // Exemplo:
        // Grupo 10:
        //   SCC0210 -> MAT1001
        //   SCC0211 -> MAT1001
        // Os registros compartilham o mesmo número de grupo.
        foreach ($codigosCursada as $codigoCur) {
            $codigoCur = preg_replace('/\s+/', '', trim((string) $codigoCur));

            if ($codigoCur === '') {
                continue;
            }

            if (strlen($codigoCur) > 7) {
                echo 'Erro em ' . basename($caminho) . ' (linha ' . ($i + 2) . ": código cursado inválido ignorado ({$codigoCur}).\n";
                $ignoradas++;

                continue;
            }

            $cursada = $obterOuCriarCursada($codigoCur, $periodo);
            $cursadasIdsDoGrupo[] = (int) $cursada->id;
        }

        $cursadasIdsDoGrupo = array_values(array_unique($cursadasIdsDoGrupo));

        if (count($cursadasIdsDoGrupo) === 0) {
            continue;
        }

        DB::transaction(function () use (
            $requerida,
            $codcur,
            $codhab,
            $cursadasIdsDoGrupo,
            $mesmaListaDeIds,
            &$totalGruposJaExistentes,
            &$totalGruposCriados,
            &$totalVinculosCriados
        ) {
            $gruposExistentes = Aproveitamento::query()
                ->where('requerida_id', $requerida->id)
                ->where('codcur', $codcur)
                ->where('codhab', $codhab)
                ->where('tipo', Aproveitamento::TIPO_AUTOMATICA)
                ->pluck('grupo')
                ->unique()
                ->values();

            foreach ($gruposExistentes as $grupoExistente) {
                $idsDoGrupoExistente = Aproveitamento::query()
                    ->where('requerida_id', $requerida->id)
                    ->where('codcur', $codcur)
                    ->where('codhab', $codhab)
                    ->where('grupo', (int) $grupoExistente)
                    ->pluck('cursada_id')
                    ->map(static fn($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if ($mesmaListaDeIds($idsDoGrupoExistente, $cursadasIdsDoGrupo)) {
                    $totalGruposJaExistentes++;

                    return;
                }
            }

            $novoGrupo = Aproveitamento::proximoGrupo();

            foreach ($cursadasIdsDoGrupo as $cursadaId) {
                Aproveitamento::create([
                    'grupo' => $novoGrupo,
                    'requerida_id' => $requerida->id,
                    'cursada_id' => $cursadaId,
                    'tipo' => Aproveitamento::TIPO_AUTOMATICA,
                    'codcur' => $codcur,
                    'codhab' => $codhab,
                ]);

                $totalVinculosCriados++;
            }

            $totalGruposCriados++;
        });
    }

    $totalArquivos++;
    echo 'Arquivo processado: ' . basename($caminho) . "\n";
}

echo "Importação concluída.\n";
echo "Arquivos processados: {$totalArquivos}\n";
echo "Requeridas processadas: {$totalRequeridas}\n";
echo "Grupos criados: {$totalGruposCriados}\n";
echo "Grupos já existentes (ignorados): {$totalGruposJaExistentes}\n";
echo "Vínculos cursada-requerida criados: {$totalVinculosCriados}\n";
echo "Disciplinas sem dados oficiais no Replicado: {$totalSemDadosReplicado}\n";
echo "Registros ignorados/avisos: {$ignoradas}\n";
