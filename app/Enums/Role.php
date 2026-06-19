<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case ALUNO = 'aluno';
    case SVGRAD = 'svgrad';
    case CG = 'cg';
}
