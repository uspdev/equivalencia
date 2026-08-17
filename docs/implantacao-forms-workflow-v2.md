# Implantação do Forms V2 e Workflow V2

- Status: aprovado para planejamento
- Data: 2026-08-14

## Objetivo

Descrever a implantação coordenada do schema V2 de Forms e Workflow no Equivalencia, preservando os dados operacionais de aproveitamentos automáticos.

## Premissas

- O banco de produção não pode usar `migrate:fresh`.
- As tabelas de Forms e Workflow não possuem dados operacionais a preservar nesta implantação.
- As definições existentes também podem ser descartadas.
- A validação será feita em uma cópia recente do banco de produção no ambiente de desenvolvimento.
- Não será exigido backup ou snapshot adicional como critério desta implantação; a cópia de desenvolvimento é o mecanismo de segurança aprovado.

## Ownership

- O Forms é responsável por `form_definitions`, `form_submissions` e `activity_log`.
- O Workflow é responsável por `workflow_definitions`, `workflow_objects` e `workflow_history`.
- O Equivalencia mantém somente a migration de transição que remove as tabelas aprovadas.
- Migrations históricas permanecem registradas no banco e não são alteradas nem removidas manualmente da tabela `migrations`.
- Os arquivos históricos podem continuar visíveis em `database/migrations`, mas não são
  migrations ativas de evolução: não devem ser editados, duplicados ou usados como fonte
  do schema V2. Consulte o [guia de histórico e ownership](migrations-ownership-e-historico.md).
- As migrations V2 dos pacotes são carregadas diretamente pelos `ServiceProvider` e não são publicadas para cópia na aplicação.

## Ordem da implantação

Equivalencia, Forms e Workflow devem ser atualizados de forma coordenada:

1. Disponibilizar as versões dos pacotes que contêm as migrations V2.
2. Disponibilizar a migration de transição do Equivalencia.
3. Confirmar que o ambiente está usando o banco de desenvolvimento derivado da produção.
4. Executar `php artisan migrate`.
5. Confirmar que o reset e todas as migrations V2 foram concluídos.

O reset não deve ser executado isoladamente, pois sua operação é deliberadamente irreversível.

## Tabelas removidas

Forms:

- `form_definitions`
- `form_submissions`
- `activity_log`

Workflow:

- `workflow_definitions`
- `workflow_objects`
- `workflow_history`
- `workflow_role_users`
- `workflow_role_emails`
- `user_workflow_definition`

As três últimas tabelas de Workflow são legadas e não serão recriadas pelo schema V2.

## Tabelas preservadas

A migration não pode modificar tabelas de aproveitamentos automáticos, incluindo suas definições, registros, disciplinas, arquivos, usuários, roles e permissões.

Os registros da tabela `migrations` também permanecem intactos.

## Critérios de validação

Antes e depois da execução, registrar as contagens e uma amostra identificável dos dados operacionais de aproveitamentos automáticos. Após o comando, confirmar:

- `php artisan migrate` terminou sem erro;
- nenhuma migration foi executada com `migrate:fresh`;
- as migrations V2 dos dois pacotes foram executadas;
- as tabelas aprovadas foram removidas e recriadas pelo schema V2;
- as tabelas legadas de roles e usuários do Workflow não foram recriadas;
- os dados e as contagens dos aproveitamentos automáticos permanecem iguais;
- o fluxo atual de aproveitamentos automáticos continua funcionando;
- as migrations históricas continuam representadas na tabela `migrations`.

Somente após essa validação no banco de desenvolvimento a mesma implantação poderá ser considerada para produção.
