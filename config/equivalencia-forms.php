<?php

return [
    'group' => 'equivalencia',
    'definitions' => [
        'tr_conferencia_inicio' => ['name' => 'retorno', 'label' => 'Informações sobre o retorno ao aluno', 'required' => true],
        'tr_conferencia_indica' => ['name' => 'obs', 'label' => 'Informações para o departamento', 'required' => true],
        'tr_indica_parecerista' => ['name' => 'obs', 'label' => 'Nome do docente parecerista', 'required' => true],
        'tr_emite_parecer' => ['name' => 'parecer', 'label' => 'Parecer', 'required' => true],
        'tr_envia_apreciacao' => ['name' => 'obs', 'label' => 'Observações sobre a apreciação', 'required' => false],
        'tr_delibera_parecer' => ['name' => 'deliberacao', 'label' => 'Deliberação sobre os pareceres', 'required' => true],
        'tr_retorna_apreciacao' => ['name' => 'obs', 'label' => 'Enviar pareceres à CoC', 'required' => true],
        'tr_conferencia_apreciacao' => ['name' => 'obs', 'label' => 'Solicitação à CoC', 'required' => true],
        'tr_solicitacao_coc' => ['name' => 'obs', 'label' => 'Enviar apreciação à CoC', 'required' => true],
        'tr_envia_deliberacao' => ['name' => 'deliberacao', 'label' => 'Deliberação da apreciação', 'required' => true],
        'tr_conferencia_deliberacao' => ['name' => 'obs', 'label' => 'Enviar para cadastro', 'required' => true],
        'tr_enviar_aprovada' => ['name' => 'obs', 'label' => 'Cadastrar aprovada', 'required' => true],
        'tr_enviar_negada' => ['name' => 'obs', 'label' => 'Cadastrar negada', 'required' => true],
        'tr_enviar_condicional' => ['name' => 'obs', 'label' => 'Cadastrar condicional', 'required' => true],
        'tr_enviar_avaliacao' => ['name' => 'avaliacao', 'label' => 'Informações para avaliação', 'required' => true],
        'tr_realizar_avaliacao' => ['name' => 'avaliacao', 'label' => 'Informações sobre a avaliação', 'required' => true],
        'tr_submeter_avaliacao' => ['name' => 'avaliacao_submetida', 'label' => 'Submissão da avaliação', 'required' => true],
        'tr_delibera_avaliacao' => ['name' => 'parecer_docente', 'label' => 'Parecer sobre a avaliação', 'required' => true],
        'tr_cadastrar_solicitacoes' => ['name' => 'cadastro', 'label' => 'Cadastro das solicitações aprovadas', 'required' => true],
        'tr_finalizar_enviar' => ['name' => 'obs', 'label' => 'Observações sobre a finalização', 'required' => false],
        'eq_usp_create' => ['name' => 'coddis', 'label' => 'Código da disciplina USP', 'type' => 'disciplina-usp', 'required' => true],
        'eq_usp_edit' => ['name' => 'coddis', 'label' => 'Código da disciplina USP', 'type' => 'disciplina-usp', 'required' => true],
        'eq_child_add' => [
            'fields' => [
                ['name' => 'coddis', 'type' => 'text', 'label' => 'Código', 'required' => true],
                ['name' => 'nome_disciplina', 'type' => 'text', 'label' => 'Nome da equivalência', 'required' => true],
                ['name' => 'ies', 'type' => 'text', 'label' => 'IES', 'required' => true],
            ],
        ],
    ],
];
