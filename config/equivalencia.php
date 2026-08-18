<?php

return [
    'codhabs' => explode(',', env('CODHABS', 0)),
    'edit_mode_session_key' => env('EQUIVALENCIA_EDIT_MODE_SESSION_KEY', 'equivalencias.edit_mode.global'),
    'workflow' => [
        'name' => env('EQUIVALENCIA_WORKFLOW_NAME', 'equivalencia'),
        'definition_path' => resource_path('workflows/equivalencia.json'),
    ],
];
