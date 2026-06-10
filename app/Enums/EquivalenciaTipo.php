<?php

namespace App\Enums;

enum EquivalenciaTipo: string
{
    case AUTOMATICA = 'a';
    case REQUERIDA = 'r';

    public function label(): string
    {
        return match ($this) {
            self::AUTOMATICA => 'Automática',
            self::REQUERIDA => 'Solicitação do aluno',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AUTOMATICA => 'success',
            self::REQUERIDA => 'warning',
        };
    }
}
