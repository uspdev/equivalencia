<?php

namespace App\Enums;

enum EquivalenciaEstado: string
{
    case RASCUNHO = 'rascunho';
    case PROCESSANDO = 'processando';
    case DEFERIDO = 'deferido';
    case NEGADO = 'negado';

    public function label(): string
    {
        return match ($this) {
            self::RASCUNHO => 'Rascunho',
            self::PROCESSANDO => 'Processando',
            self::DEFERIDO => 'Deferido',
            self::NEGADO => 'Negado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RASCUNHO => 'secondary',
            self::PROCESSANDO => 'warning',
            self::DEFERIDO => 'success',
            self::NEGADO => 'danger',
        };
    }
}
