<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case ALUNO = 'aluno';
    case SVGRAD = 'svgrad';
    case DEPTO = 'depto';
    case DOCENTE = 'docente';
    case CONSELHO = 'conselho';
    case COC = 'coc';
    case CG = 'cg';
}
