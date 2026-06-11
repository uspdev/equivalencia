<?php

namespace App\Http\Controllers;

use App\Replicado\Graduacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DisciplinaUspController extends Controller
{
    public function __invoke(Request $request, Graduacao $graduacao): JsonResponse
    {
        $term = Str::upper(trim((string) $request->query('term', '')));

        if (mb_strlen($term) < 3 || ! preg_match('/^[A-Z0-9]+$/', $term)) {
            return response()->json(['results' => []]);
        }

        try {
            $results = collect($graduacao->buscarDisciplinas($term, 50))
                ->take(50)
                ->filter(fn ($discipline) => is_array($discipline)
                    && isset($discipline['coddis'], $discipline['nomdis']))
                ->map(fn (array $discipline) => [
                    'id' => trim((string) $discipline['coddis']),
                    'text' => trim((string) $discipline['coddis']).' - '.trim((string) $discipline['nomdis']),
                ])
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            logger()->error('Falha ao pesquisar disciplinas USP no Replicado.', [
                'exception' => $exception,
                'term' => $term,
            ]);

            return response()->json(['results' => []], 503);
        }

        return response()->json(['results' => $results]);
    }
}
