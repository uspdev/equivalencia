<?php

namespace App\Enums;

enum Permission: string
{
    case REQUERIMENTOS_CREATE = 'requerimentos.create';
    case REQUERIMENTOS_VIEW_OWN = 'requerimentos.view-own';
    case APROVEITAMENTOS_AUTOMATICOS_VIEW = 'aproveitamentos-automaticos.view';
    case APROVEITAMENTOS_AUTOMATICOS_MANAGE = 'aproveitamentos-automaticos.manage';
}
