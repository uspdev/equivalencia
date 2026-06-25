<?php

namespace App\Enums;

enum EquivalenciaTipo: string
{
    case AUTOMATICA = 'automatica';
    case SOLICITADA = 'solicitada';

    public function label(): string
    {
        return match ($this) {
            self::AUTOMATICA => 'Automática',
            self::SOLICITADA => 'Solicitação do aluno',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AUTOMATICA => 'success',
            self::SOLICITADA => 'warning',
        };
    }
}
