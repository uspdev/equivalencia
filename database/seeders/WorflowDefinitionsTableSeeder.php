<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class WorflowDefinitionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('workflow_definitions')->insert([
            'name' => 'equivalencia',
            'description' => 'Workflow de equivalência de disciplinas',
            'definition' => '{"type":"workflow","title":"Solicitação de Equivalência de Disciplinas na Graduação","name":"equivalencia","description":"Workflow de equivalência de disciplinas","supports":["App\\\\Equivalencia"],"places":{"aluno_inicio":{"description":"Formulário de solicitação inicial","role":{"Aluno":"aluno"}},"svgrad_conferencia":{"description":"Conferência de documentos","role":{"Serviço de Graduação":"svgrad"}},"depto_indica_docente":{"description":"Indicar docente para emitir parecer","role":{"Departamento":"depto"}},"docente_emite_parecer":{"description":"Parecer sobre equivalência de disciplina","role":{"Docente":"docente"}},"depto_envia_apreciacao":{"description":"Enviar parecer para apreciação do conselho","role":{"Departamento":"depto"}},"conselho_delibera":{"description":"Deliberar sobre os pareceres emitidos","role":{"Conselho do Departamento":"conselho"}},"depto_retorno_apreciacao":{"description":"Enviar apreciação para a CoC","role":{"Departamento":"depto"}},"svgrad_apreciacao_coc":{"description":"Enviar apreciação para a CoC","role":{"Serviço de Graduação":"svgrad"}},"coc_delibera_solicitacao":{"description":"Deliberar sobre a solicitação","role":{"CoC":"coc"}},"svgrad_recebe_deliberacao":{"description":"Receber resposta da CoC","role":{"Serviço de Graduação":"svgrad"}},"svgrad_cadastra_aprovadas":{"description":"Cadastrar solicitações aprovadas","role":{"Serviço de Graduação":"svgrad"}},"aluno_preavaliacao":{"description":"Aluno necessita fazer avaliação","role":{"Aluno":"aluno"}},"aluno_avaliacao":{"description":"Avaliação","role":{"Aluno":"aluno"},"max":1},"docente_avaliacao":{"description":"Docente necessita elaborar avaliação","role":{"Docente":"docente"}},"docente_delibera":{"description":"Docente delibera sobre avaliação","role":{"Docente":"docente"}},"svgrad_finaliza":{"description":"Finalizar","role":{"Serviço de Graduação":"svgrad"}},"aluno_fim":{"description":"Confirmação de finalização","role":{"Aluno":"aluno"}}},"initial_places":"aluno_inicio","transitions":{"tr_inicio_conferencia":{"label":"Enviar para Serviço de Graduação","from":"aluno_inicio","tos":"svgrad_conferencia"},"tr_conferencia_inicio":{"label":"Retornar para aluno","from":"svgrad_conferencia","tos":"aluno_inicio","forms":["tr_conferencia_inicio"]},"tr_conferencia_indica":{"label":"Enviar para Departamento","from":"svgrad_conferencia","tos":"depto_indica_docente","forms":["tr_conferencia_indica"]},"tr_indica_parecerista":{"label":"Indicar parecerista","from":"depto_indica_docente","tos":"docente_emite_parecer","forms":["tr_indica_parecerista"]},"tr_emite_parecer":{"label":"Emitir parecer","from":"docente_emite_parecer","tos":"depto_envia_apreciacao","forms":["tr_emite_parecer"]},"tr_envia_apreciacao":{"label":"Enviar para o Conselho do Departamento","from":"depto_envia_apreciacao","tos":"conselho_delibera","forms":["tr_envia_apreciacao"]},"tr_delibera_parecer":{"label":"Enviar deliberação","from":"conselho_delibera","tos":"depto_retorno_apreciacao","forms":["tr_delibera_parecer"]},"tr_retorna_apreciacao":{"label":"Solicitar envio à CoC para o Serviço de Graduação","from":"depto_retorno_apreciacao","tos":"svgrad_apreciacao_coc","forms":["tr_retorna_apreciacao"]},"tr_conferencia_apreciacao":{"label":"Solicitar envio à CoC","from":"svgrad_conferencia","tos":"svgrad_apreciacao_coc","forms":["tr_conferencia_apreciacao"]},"tr_solicitacao_coc":{"label":"Enviar solicitação para a CoC","from":"svgrad_apreciacao_coc","tos":"coc_delibera_solicitacao","forms":["tr_solicitacao_coc"]},"tr_envia_deliberacao":{"label":"Enviar deliberação para o Serviço de Graduação","from":"coc_delibera_solicitacao","tos":"svgrad_recebe_deliberacao","forms":["tr_envia_deliberacao"]},"tr_conferencia_deliberacao":{"label":"Enviar resposta da CoC para cadastro","from":"svgrad_conferencia","tos":"svgrad_recebe_deliberacao","forms":["tr_conferencia_deliberacao"]},"tr_enviar_condicional":{"label":"Cadastrar condicional","from":"svgrad_recebe_deliberacao","tos":["aluno_preavaliacao","docente_avaliacao"],"forms":["tr_enviar_condicional"]},"tr_enviar_aprovada":{"label":"Cadastrar aprovativa","from":"svgrad_recebe_deliberacao","tos":"svgrad_cadastra_aprovadas","forms":["tr_enviar_aprovada"]},"tr_enviar_negada":{"label":"Cadastrar negativa","from":"svgrad_recebe_deliberacao","tos":"aluno_fim","forms":["tr_enviar_negada"]},"tr_cadastrar_solicitacoes":{"label":"Finalizar cadastro de solicitações","from":"svgrad_cadastra_aprovadas","tos":"svgrad_finaliza","forms":["tr_cadastrar_solicitacoes"]},"tr_enviar_avaliacao":{"label":"Realizar avaliação","from":"docente_avaliacao","tos":"aluno_avaliacao","forms":["tr_enviar_avaliacao"]},"tr_realizar_avaliacao":{"label":"Realizar avaliação","from":"aluno_preavaliacao","tos":"aluno_avaliacao","forms":["tr_realizar_avaliacao"]},"tr_submeter_avaliacao":{"label":"Submeter avaliação para docente","from":"aluno_avaliacao","tos":"docente_delibera","forms":["tr_submeter_avaliacao"]},"tr_delibera_avaliacao":{"label":"Docente delibera sobre a avaliação","from":"docente_delibera","tos":"svgrad_recebe_deliberacao","forms":["tr_delibera_avaliacao"]},"tr_finalizar_enviar":{"label":"Finalizar processo","from":"svgrad_finaliza","tos":"aluno_fim","forms":["tr_finalizar_enviar"]}}}',
            'created_at' => '2025-05-09 17:41:56',
            'updated_at' => '2025-05-09 17:52:00',
        ]);

        DB::table('permissions')->insert([
            ['id' => 24, 'name' => 'aluno_inicio', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 25, 'name' => 'svgrad_conferencia', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 26, 'name' => 'depto_indica_docente', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 27, 'name' => 'docente_emite_parecer', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 28, 'name' => 'depto_envia_apreciacao', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 29, 'name' => 'conselho_delibera', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 30, 'name' => 'depto_retorno_apreciacao', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 31, 'name' => 'svgrad_apreciacao_coc', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 32, 'name' => 'coc_delibera_solicitacao', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 33, 'name' => 'svgrad_recebe_deliberacao', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 34, 'name' => 'svgrad_cadastra_aprovadas', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 35, 'name' => 'aluno_preavaliacao', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 36, 'name' => 'aluno_avaliacao', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 37, 'name' => 'docente_avaliacao', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 38, 'name' => 'docente_delibera', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 39, 'name' => 'svgrad_finaliza', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 40, 'name' => 'aluno_fim', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
        ]);

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'aluno', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 2, 'name' => 'svgrad', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 3, 'name' => 'depto', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 4, 'name' => 'docente', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 5, 'name' => 'conselho', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
            ['id' => 6, 'name' => 'coc', 'guard_name' => 'web', 'created_at' => '2025-05-09 17:41:56', 'updated_at' => '2025-05-09 17:41:56'],
        ]);

        DB::table('role_has_permissions')->insert([
            ['permission_id' => 24, 'role_id' => 1],
            ['permission_id' => 25, 'role_id' => 2],
            ['permission_id' => 26, 'role_id' => 3],
            ['permission_id' => 27, 'role_id' => 4],
            ['permission_id' => 28, 'role_id' => 3],
            ['permission_id' => 29, 'role_id' => 5],
            ['permission_id' => 30, 'role_id' => 3],
            ['permission_id' => 31, 'role_id' => 2],
            ['permission_id' => 32, 'role_id' => 6],
            ['permission_id' => 33, 'role_id' => 2],
            ['permission_id' => 34, 'role_id' => 2],
            ['permission_id' => 35, 'role_id' => 1],
            ['permission_id' => 36, 'role_id' => 1],
            ['permission_id' => 37, 'role_id' => 4],
            ['permission_id' => 38, 'role_id' => 4],
            ['permission_id' => 39, 'role_id' => 2],
            ['permission_id' => 40, 'role_id' => 1],
        ]);
    }
}
