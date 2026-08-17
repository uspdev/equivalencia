# Migrations: histórico e ownership

Este documento explica por que existem migrations antigas no Equivalencia e
quais migrations devem ser consideradas ativas no desenvolvimento atual.

## A tabela `migrations` é o histórico do banco

O Laravel identifica uma migration pelo nome do arquivo, não pelo conteúdo da
tabela que ela criou. Quando uma migration é executada, seu nome é registrado
na tabela `migrations`. Alterar o arquivo depois não faz o Laravel executar a
migration novamente.

Por esse motivo, as migrations históricas já presentes na branch principal do
Equivalencia não devem ser editadas. Elas representam a sequência que levou o
banco de produção ao estado atual.

Esses arquivos não são a fonte de verdade para novas alterações de Forms ou
Workflow. Eles permanecem como registro do passado e não devem receber novas
regras de schema.

## O que é histórico e o que é ativo

As migrations antigas locais de Forms e Workflow, como as de criação de
`form_definitions`, `form_submissions`, `workflow_definitions` e
`workflow_objects`, são arquivos históricos do consumidor. Elas não devem ser
alteradas para acompanhar as versões novas dos pacotes.

O schema atual deve ser criado pelas migrations dos próprios pacotes:

- Forms carrega a migration V2 pelo `FormServiceProvider`, em
  `database/migrations/v2` do pacote;
- Workflow carrega as migrations do próprio pacote pelo
  `WorkflowServiceProvider`;
- o Equivalencia mantém somente a migration de transição específica desta
  aplicação.

Essa separação evita que uma correção futura seja aplicada em dois lugares
diferentes.

## Migration de transição

[`2026_08_16_235900_drop_forms_workflow_tables_for_v2.php`](../database/migrations/2026_08_16_235900_drop_forms_workflow_tables_for_v2.php)
é a única migration do Equivalencia responsável pela transição. Ela remove
somente as tabelas aprovadas de Forms e Workflow e `activity_log`, sem criar o
schema novo.

Ela não remove nem altera:

- `aproveitamentos`;
- `disciplinas`;
- `arquivos`;
- `users`;
- roles e permissões;
- qualquer outra tabela operacional do Equivalencia;
- a tabela `migrations`.

O rollback é bloqueado porque os registros descartados não são restauráveis
por essa migration.

## Arquivos que não devem voltar

Foram removidas as migrations locais criadas durante a tentativa de integração
que duplicavam o schema dos pacotes:

- `2024_10_16_162700_rebuild_forms_workflow_base_tables.php`;
- `2026_07_02_000000_update_form_definitions_for_versioning.php`;
- `2026_07_02_000001_add_flat_fields_to_form_definitions_table.php`.

Essas migrations não devem ser recriadas no Equivalencia. Em particular,
`flat_fields` não pertence ao schema Forms V2; é apenas uma estrutura derivada
usada internamente durante a validação.

## Ordem e pré-condições

A implantação deve ser coordenada entre os três repositórios:

1. disponibilizar migrations V2 dos pacotes com nomes novos, posteriores ao
   histórico já executado;
2. executar a migration de transição do Equivalencia;
3. deixar Forms e Workflow criarem o próprio schema por seus providers;
4. validar tudo primeiro na cópia local do banco de produção;
5. somente depois avaliar a implantação em produção.

O Workflow ainda precisa disponibilizar suas migrations V2 com nomes novos. As
migrations atuais do pacote usam nomes históricos de 2024; como esses nomes já
podem estar registrados em produção, alterar o conteúdo delas não faria com
que fossem executadas novamente.

`migrate:fresh` continua proibido em produção.
