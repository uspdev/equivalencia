---
status: accepted
---

# ADR 0001 — Ownership das migrations e reset controlado de Forms e Workflow

## Contexto

O banco do Equivalencia está em produção e contém dados operacionais de aproveitamentos automáticos que precisam ser preservados. As tabelas de Forms e Workflow, porém, não participam do fluxo operacional atual e foram analisadas como estruturas não operacionais.

O Equivalencia também possui migrations copiadas dos pacotes Forms e Workflow. Esse modelo cria duas possíveis fontes de verdade para as mesmas tabelas: as migrations locais da aplicação e as migrations dos pacotes. Algumas migrations históricas da aplicação já foram executadas em produção e não podem ser alteradas. `migrate:fresh` também não é uma opção.

## Decisão

Forms e Workflow serão os únicos donos das próprias tabelas e migrations. O Equivalencia deixará de criar e evoluir diretamente o schema desses pacotes.

As migrations V2 dos pacotes serão carregadas diretamente pelos respectivos `ServiceProvider` e não serão publicadas para cópia na aplicação consumidora. As cópias locais das migrations dos pacotes serão removidas em alteração controlada.

Migrations novas introduzidas por Forms V2 e Workflow V2 podem ser ajustadas antes da implantação. Migrations históricas já existentes na branch principal do Equivalencia, que representam o histórico do banco em produção, não serão alteradas nem recarregadas.

Antes da regeneração das tabelas, será executada uma migration de transição explícita e deliberadamente irreversível, limitada às tabelas autorizadas:

- Forms: `form_definitions`, `form_submissions` e `activity_log`;
- Workflow: `workflow_definitions`, `workflow_objects`, `workflow_history`, `workflow_role_users`, `workflow_role_emails` e `user_workflow_definition`.

A migration de transição não criará tabelas, não fará backfill ou normalização e não editará manualmente a tabela de migrations do Laravel. A criação do schema novo ficará a cargo das migrations V2 dos pacotes.

Ficam fora do descarte as tabelas e os dados operacionais do Equivalencia, incluindo aproveitamentos automáticos, disciplinas, arquivos, usuários, roles e permissões. `migrate:fresh` continua proibido em produção.

Não será exigido backup ou arquivamento adicional das tabelas de Forms e Workflow antes da regeneração aprovada. A validação de segurança será feita primeiro na cópia local recente do banco de produção; não existem dados operacionais de Forms/Workflow a preservar nesta implantação.

## Consequências

- O schema de cada pacote passa a ter um único dono.
- O banco de produção é preservado fora da lista explícita de descarte.
- Definições, submissões, objetos, históricos e logs de Forms/Workflow serão recriados sem preservação dos registros atuais.
- A tabela genérica `activity_log` será regenerada junto com Forms/Workflow enquanto esses forem seus únicos consumidores identificados. Se outro módulo passar a utilizá-la, esta decisão deverá ser reavaliada.
- Uma migration rollback não recuperará os registros descartados.
- O deploy depende de as migrations V2 dos pacotes estarem disponíveis e criarem o schema completo.

## Alternativas consideradas

- `migrate:fresh`: rejeitado porque destruiria dados operacionais do Equivalencia.
- Alterar migrations históricas: rejeitado porque elas já fazem parte do histórico do banco de produção.
- Manter migrations copiadas na aplicação: rejeitado porque cria dois donos e permite divergência entre schemas.
- Fazer backfill ou conversão de dados legados: rejeitado porque não há dados operacionais de Forms/Workflow a preservar nesta implantação.
- Fazer backup ou arquivamento obrigatório das definições: não adotado para este reset, conforme decisão do consumidor; a simulação usa a cópia recente do banco de produção.

## Histórico consolidado

Este ADR consolida as decisões anteriormente registradas em `0001-remover-e-regenerar-tabelas-de-forms-e-workflow` e `0002-adotar-migrations-dos-pacotes-e-reset-controlado`. O conteúdo foi reunido para manter uma única decisão sobre ownership, descarte e regeneração controlada.
