<?php

namespace App\Enums;

enum DisciplinaRole: string
{
    case REQUERIDA = 'requerida';
    case CURSADA = 'cursada';

    public function label(): string
    {
        return match ($this) {
            self::REQUERIDA => 'Disciplina requerida',
            self::CURSADA => 'Disciplina cursada',
        };
    }
}
