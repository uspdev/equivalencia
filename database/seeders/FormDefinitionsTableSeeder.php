<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormDefinitionsTableSeeder extends Seeder
{
    public function run()
    {
        $formDefinitions = [
            [1, 'tr_inicio_conferencia', '', null, '[{"name":"nome","type":"text","label":"Nome","required":true},{"name":"coddis","type":"text","label":"Código da disciplina que solicita equivalência","required":true},{"name":"obs","type":"textarea","label":"Observações","required":false},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [2, 'tr_conferencia_inicio', '', null, '[{"name":"retorno","type":"texttextarea","label":"Informações sobre retorno ao aluno","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [3, 'tr_conferencia_indica', '', null, '[{"name":"obs","type":"texttextarea","label":"Enviar ao departamento","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [4, 'tr_indica_parecerista', '', null, '[{"name":"obs","type":"texttextarea","label":"Nomear o docente a emitir o parecer","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [5, 'tr_emite_parecer', '', null, '[{"name":"parecer","type":"texttextarea","label":"Parecer","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [6, 'tr_envia_apreciacao', '', null, '[{"name":"obs","type":"texttextarea","label":"Observações sobre a apreciação","required":false},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [7, 'tr_delibera_parecer', '', null, '[{"name":"deliberacao","type":"texttextarea","label":"Deliberação sobre os pareceres emitidos","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [8, 'tr_retorna_apreciacao', '', null, '[{"name":"obs","type":"texttextarea","label":"Enviar pareceres à CoC","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [9, 'tr_conferencia_apreciacao', '', null, '[{"name":"obs","type":"texttextarea","label":"Solicitação à CoC","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [10, 'tr_solicitacao_coc', '', null, '[{"name":"obs","type":"texttextarea","label":"Enviar apreciação à CoC","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [11, 'tr_envia_deliberacao', '', null, '[{"name":"deliberacao","type":"texttextarea","label":"Deliberação sobre a apreciação","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [12, 'tr_conferencia_deliberacao', '', null, '[{"name":"obs","type":"texttextarea","label":"Enviar para cadastro","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [13, 'tr_enviar_aprovada', '', null, '[{"name":"obs","type":"texttextarea","label":"Cadastrar aprovada","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [14, 'tr_enviar_negada', '', null, '[{"name":"obs","type":"texttextarea","label":"Cadastrar negada","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [15, 'tr_enviar_condicional', '', null, '[{"name":"obs","type":"texttextarea","label":"Cadastrar condicional","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [16, 'tr_enviar_avaliacao', '', null, '[{"name":"avaliacao","type":"texttextarea","label":"Informações para avaliação","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [17, 'tr_realizar_avaliacao', '', null, '[{"name":"avaliacao","type":"texttextarea","label":"Informações sobre a avaliação","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [18, 'tr_submeter_avaliacao', '', null, '[{"name":"avaliacao_submetida","type":"texttextarea","label":"Submissão da avaliação para docente","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [19, 'tr_delibera_avaliacao', '', null, '[{"name":"parecer_docente","type":"texttextarea","label":"Parecer sobre a avaliação condicional","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [20, 'tr_cadastrar_solicitacoes', '', null, '[{"name":"cadastro","type":"texttextarea","label":"Cadastro das solicitações aprovadas","required":true},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
            [21, 'tr_finalizar_enviar', '', null, '[{"name":"obs","type":"texttextarea","label":"Observações sobre finalização","required":false},{"name":"definition_name","type":"hidden","value":"workflowDefinitionName"},{"name":"place","type":"hidden","value":"place_name"},{"name":"transition","type":"hidden","value":"transition_name"}]', '2025-03-17 19:33:49', '2025-03-17 19:33:49'],
        ];

        foreach ($formDefinitions as $formDefinition) {
            DB::table('form_definitions')->insert([
                'id' => $formDefinition[0],
                'name' => $formDefinition[1],
                'group' => $formDefinition[2],
                'description' => $formDefinition[3],
                'fields' => $formDefinition[4],
                'created_at' => $formDefinition[5],
                'updated_at' => $formDefinition[6],
            ]);
        }
    }
}
