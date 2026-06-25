
use App\Enums\DisciplinaRole;
use App\Enums\EquivalenciaTipo;
use App\Models\Aproveitamento;
use App\Models\Disciplina;
use Illuminate\Support\Facades\DB;


// Busca todos os arquivos que serão importados.
$arquivos = glob(storage_path('app/TAA/*.json'));
if ($arquivos === false) {
    throw new RuntimeException('Falha ao listar os arquivos em storage/app/TAA.');
}

if (count($arquivos) === 0) {
    throw new RuntimeException('Nenhum arquivo JSON encontrado em storage/app/TAA.');
}

// Contadores exibidos no resumo final.
$totalArquivos = 0;
$totalConjuntosProcessados = 0;
$totalAproveitamentosCriados = 0;
$totalAproveitamentosJaExistentes = 0;
$totalDisciplinasCriadas = 0;
$totalSemDadosReplicado = 0;
$ignoradas = 0;

// Remove chaves com valores nulos ou string vazia, para não apagar dados já
// preenchidos quando o Replicado estiver temporariamente indisponível.
$dadosPreenchidos = static function (array $dados): array {
    return array_filter($dados, static fn($valor) => $valor !== null && $valor !== '');
};

// Padroniza os códigos para facilitar validações e comparações.
$normalizarCodigo = static function (string $codigo): string {
    return strtoupper((string) preg_replace('/\s+/u', '', trim($codigo)));
};

// A identidade é usada somente para tornar a importação idempotente. No novo
// modelo, cada disciplina continua sendo uma linha própria do aproveitamento.
$chaveDisciplina = static function (array|Disciplina $disciplina) use ($normalizarCodigo): string {
    $ies = $disciplina instanceof Disciplina ? $disciplina->ies : ($disciplina['ies'] ?? null);
    $coddis = $disciplina instanceof Disciplina ? $disciplina->coddis : ($disciplina['coddis'] ?? null);
    $verdis = $disciplina instanceof Disciplina ? $disciplina->verdis : ($disciplina['verdis'] ?? null);

    return implode('|', [
        strtoupper(trim((string) $ies)),
        $normalizarCodigo((string) $coddis),
        $verdis === null || $verdis === '' ? '' : (string) (int) $verdis,
    ]);
};

// Compara dois conjuntos de cursadas sem considerar a ordem.
$mesmoConjuntoDeDisciplinas = static function (
    iterable $disciplinasExistentes,
    array $dadosDisciplinas
) use ($chaveDisciplina): bool {
    $existentes = [];
    foreach ($disciplinasExistentes as $disciplina) {
        $existentes[] = $chaveDisciplina($disciplina);
    }

    $importadas = array_map($chaveDisciplina, $dadosDisciplinas);

    sort($existentes);
    sort($importadas);

    return $existentes === $importadas;
};

// Busca os dados oficiais da disciplina requerida no Replicado.
// Se a consulta falhar, usa o nome informado no JSON.
$dadosDaRequerida = function (
    string $codigo,
    ?string $nomeFallback = null
) use (&$totalSemDadosReplicado, $normalizarCodigo): array {
    $codigo = $normalizarCodigo($codigo);
    $dados = Disciplina::dadosDaRequeridaPorCoddis($codigo);

    if (empty($dados['nomdis'])) {
        $totalSemDadosReplicado++;
        $dados['nomdis'] = filled($nomeFallback) ? trim($nomeFallback) : null;
    }

    $dados['coddis'] = $dados['coddis'] ?? $codigo;
    $dados['ies'] = $dados['ies'] ?? 'USP';

    return $dados;
};

// Busca e normaliza os dados de uma disciplina cursada da USP.
// O nome do JSON serve como alternativa quando não houver retorno oficial.
$dadosDaCursada = function (
    string $codigo,
    ?string $nomeFallback = null
) use (&$totalSemDadosReplicado, $normalizarCodigo): array {
    $codigo = $normalizarCodigo($codigo);
    $dados = Disciplina::dadosDaCursadaPorFormulario([
        'is_usp' => true,
        'coddis' => $codigo,
        'nome_disciplina' => filled($nomeFallback) ? trim($nomeFallback) : null,
        'ies' => 'USP',
    ]);

    if (empty($dados['nomdis'])) {
        $totalSemDadosReplicado++;
        $dados['nomdis'] = filled($nomeFallback) ? trim($nomeFallback) : null;
    }

    $dados['coddis'] = $dados['coddis'] ?? $codigo;
    $dados['ies'] = $dados['ies'] ?? 'USP';

    return $dados;
};

// Cada arquivo representa as equivalências de um curso e habilitação.
foreach ($arquivos as $caminho) {
    // Lê e valida o conteúdo JSON antes de processar os registros.
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

        $chaveNormalizada = strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/u', '', $chave)));
        if ($chaveNormalizada === 'curso' && is_string($valor) && trim($valor) !== '') {
            $cursoRaw = trim($valor);
            break;
        }
    }

    // Fallback: primeiro valor que pareça "Nome do Curso (codcur/codhab)".
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

    $nomeCurso = trim($mCurso[1]);
    $codcur = (int) $mCurso[2];
    $codhab = (int) $mCurso[3];

    // O primeiro item contém o curso; os demais são conjuntos de equivalência.
    foreach (array_slice($dados, 1) as $i => $linha) {
        // Soma dois porque o array começa em zero e o primeiro item é o curso.
        $numeroLinha = $i + 2;

        if (! is_array($linha)) {
            echo 'Erro em ' . basename($caminho) . " (linha {$numeroLinha}): formato inválido.\n";
            $ignoradas++;

            continue;
        }

        // Valida o código da disciplina que será aproveitada.
        $codigoReq = $normalizarCodigo((string) ($linha['codigo_disciplina_requerida'] ?? ''));

        if ($codigoReq === '') {
            echo 'Erro em ' . basename($caminho) . " (linha {$numeroLinha}): código requerido vazio.\n";
            $ignoradas++;

            continue;
        }

        if (strlen($codigoReq) > 7) {
            echo 'Erro em ' . basename($caminho) . " (linha {$numeroLinha}): código requerido inválido ({$codigoReq}).\n";
            $ignoradas++;

            continue;
        }

        // Separa conjuntos como "SCC0210 + SCC0211" em códigos individuais.
        $codigosCursadaRaw = (string) ($linha['codigo_disciplina_cursada'] ?? '');
        $codigosCursada = preg_split(
            '/\s*(?:\+|\be\b|\bou\b|\/|,|;)\s*/ui',
            $codigosCursadaRaw,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if (! is_array($codigosCursada)) {
            $codigosCursada = [];
        }

        // Normaliza, remove códigos vazios e elimina repetições.
        $codigosCursada = array_values(array_unique(array_filter(
            array_map(
                static fn($codigo) => $normalizarCodigo((string) $codigo),
                $codigosCursada
            )
        )));

        $codigosInvalidos = array_values(array_filter(
            $codigosCursada,
            static fn(string $codigo): bool => strlen($codigo) > 7
        ));

        if ($codigosInvalidos !== []) {
            echo 'Erro em ' . basename($caminho) . " (linha {$numeroLinha}): código(s) cursado(s) inválido(s) ("
                . implode(', ', $codigosInvalidos) . ").\n";
            $ignoradas++;

            continue;
        }

        if ($codigosCursada === []) {
            echo 'Erro em ' . basename($caminho) . " (linha {$numeroLinha}): nenhuma disciplina cursada válida.\n";
            $ignoradas++;

            continue;
        }

        // O modelo permite no máximo três cursadas por aproveitamento.
        if (count($codigosCursada) > 3) {
            echo 'Erro em ' . basename($caminho) . " (linha {$numeroLinha}): o conjunto possui mais de três cursadas.\n";
            $ignoradas++;

            continue;
        }

        // Um erro neste registro não interrompe a importação dos próximos.
        try {
            $dadosRequerida = $dadosDaRequerida(
                $codigoReq,
                isset($linha['nome_disciplina_requerida'])
                    ? (string) $linha['nome_disciplina_requerida']
                    : null
            );

            // O "período" do JSON é a posição da requerida na grade curricular.
            // Ele não corresponde ao semestre em que uma disciplina foi cursada (como isso é AA, não é cursada)
            // por isso, não é persistido no campo disciplinas.semestre.
            $nomeCursadaFallback = count($codigosCursada) === 1 && isset($linha['nome_disciplina_cursada'])
                ? (string) $linha['nome_disciplina_cursada']
                : null;

            $dadosCursadas = array_map(
                fn(string $codigo): array => $dadosDaCursada($codigo, $nomeCursadaFallback),
                $codigosCursada
            );

            // A transação evita salvar um aproveitamento incompleto.
            $resultado = DB::transaction(function () use (
                $codcur,
                $codhab,
                $dadosRequerida,
                $dadosCursadas,
                $chaveDisciplina,
                $mesmoConjuntoDeDisciplinas,
                $dadosPreenchidos
            ): array {
                // Procura um aproveitamento igual no mesmo curso e habilitação.
                $aproveitamentosExistentes = Aproveitamento::query()
                    ->automaticas()
                    ->doContexto($codcur, $codhab)
                    ->whereHas('requerida', function ($query) use ($dadosRequerida) {
                        $query
                            ->where('ies', $dadosRequerida['ies'])
                            ->where('coddis', $dadosRequerida['coddis']);

                        if (($dadosRequerida['verdis'] ?? null) === null) {
                            $query->whereNull('verdis');
                        } else {
                            $query->where('verdis', $dadosRequerida['verdis']);
                        }
                    })
                    ->with(['requerida', 'cursadas'])
                    ->lockForUpdate()
                    ->get();

                foreach ($aproveitamentosExistentes as $aproveitamentoExistente) {
                    // A requerida pode ter vários conjuntos diferentes de cursadas.
                    if (! $mesmoConjuntoDeDisciplinas($aproveitamentoExistente->cursadas, $dadosCursadas)) {
                        continue;
                    }

                    // Reimportar também renova os dados cadastrais disponíveis,
                    // mantendo valores antigos quando a nova consulta vier vazia.
                    $aproveitamentoExistente->requerida?->update($dadosPreenchidos($dadosRequerida));

                    $cursadasPorIdentidade = $aproveitamentoExistente->cursadas
                        ->keyBy(fn(Disciplina $disciplina) => $chaveDisciplina($disciplina));

                    foreach ($dadosCursadas as $dadosCursada) {
                        $cursadasPorIdentidade
                            ->get($chaveDisciplina($dadosCursada))
                            ?->update($dadosPreenchidos($dadosCursada));
                    }

                    return ['criado' => false, 'disciplinas_criadas' => 0];
                }

                // Não encontrou conjunto igual: cria um novo aproveitamento automático.
                $aproveitamento = Aproveitamento::create([
                    'tipo' => EquivalenciaTipo::AUTOMATICA,
                    'codcur' => $codcur,
                    'codhab' => $codhab,
                ]);

                // Cada aproveitamento possui exatamente uma disciplina requerida.
                Disciplina::create(array_merge($dadosRequerida, [
                    'aproveitamento_id' => $aproveitamento->id,
                    'role' => DisciplinaRole::REQUERIDA,
                ]));

                // As cursadas pertencem ao mesmo aproveitamento da requerida.
                foreach ($dadosCursadas as $dadosCursada) {
                    Disciplina::create(array_merge($dadosCursada, [
                        'aproveitamento_id' => $aproveitamento->id,
                        'role' => DisciplinaRole::CURSADA,
                    ]));
                }

                return [
                    'criado' => true,
                    'disciplinas_criadas' => 1 + count($dadosCursadas),
                ];
            });

            $totalConjuntosProcessados++;

            if ($resultado['criado']) {
                $totalAproveitamentosCriados++;
                $totalDisciplinasCriadas += $resultado['disciplinas_criadas'];
            } else {
                $totalAproveitamentosJaExistentes++;
            }
        } catch (Throwable $erro) {
            // Mostra qual entrada falhou e a origem técnica do erro.
            echo "\nERRO AO IMPORTAR APROVEITAMENTO\n";
            echo 'Arquivo JSON: ' . basename($caminho) . "\n";
            echo "Linha no JSON: {$numeroLinha}\n";
            echo "Disciplina requerida: {$codigoReq}\n";
            echo 'Disciplinas cursadas: ' . implode(', ', $codigosCursada) . "\n";
            echo 'Mensagem: ' . $erro->getMessage() . "\n";
            echo 'Origem: ' . $erro->getFile() . ':' . $erro->getLine() . "\n\n";

            $ignoradas++;
        }
    }

    $totalArquivos++;
    echo 'Arquivo processado: ' . basename($caminho) . " — {$nomeCurso} ({$codcur}/{$codhab})\n";
}

// Exibe um resumo geral ao terminar todos os arquivos.
echo "Importação concluída.\n";
echo "Arquivos processados: {$totalArquivos}\n";
echo "Conjuntos processados: {$totalConjuntosProcessados}\n";
echo "Aproveitamentos automáticos criados: {$totalAproveitamentosCriados}\n";
echo "Aproveitamentos já existentes (atualizados/ignorados): {$totalAproveitamentosJaExistentes}\n";
echo "Disciplinas criadas: {$totalDisciplinasCriadas}\n";
echo "Disciplinas sem dados oficiais no Replicado: {$totalSemDadosReplicado}\n";
echo "Registros ignorados/avisos: {$ignoradas}\n";
