# Integração com workflow/forms v2

## Fronteira da aplicação

O `equivalencia` conversa com o workflow somente por `App\Contracts\WorkflowGateway`.
Sua implementação é `App\Services\Workflow\UspdevWorkflowGateway` e é registrada no
container pelo `AppServiceProvider`.

Controllers e views recebem apenas resultados e DTOs da aplicação:

- `WorkflowStartResult` para inicialização;
- `WorkflowQueryResult` e `WorkflowProcess` para consulta;
- `WorkflowTransitionResult` para ações;
- `WorkflowPlace`, `WorkflowAction` e `WorkflowHistoryEntry` para a tela própria.

O adapter usa o serviço público `Uspdev\Workflow\WorkflowService`, traduz respostas e
captura exceções, respostas inválidas e falhas de persistência. Os detalhes técnicos são
registrados no log; a interface recebe mensagens genéricas. Nenhuma view local inclui
views do `workflow` ou do `forms`.

## Contratos encontrados na inspeção

### Workflow

Disponíveis na branch `refactor`:

- serviço público `WorkflowService::start`, `find` e `loadDefinition`;
- comando público `workflow:sync --path=...`;
- métodos públicos de objeto documentados para `apply`, `workflowState`, `getHistory` e
  `currentPlaces`.

### Forms

Disponíveis na versão instalada `0.8.4`:

- serviço público `FormDefinitionSyncService::syncDefinition`;
- comando público `forms:sync`;
- endpoints públicos, incluindo a busca de disciplinas.

A aplicação não usa models, controllers, rotas administrativas ou views internas desses
pacotes.

## Limitações da dependência externa

A inspeção da branch `refactor` registrou incompatibilidades que não são corrigidas neste
repositório:

- `WorkflowService::loadDefinition` consulta `is_published`, mas o modelo/migration usam
  `status` e `published_at`;
- `WorkflowService::start` usa atributos legados (`model_type`, `model_id` e
  `current_place`), enquanto o modelo v2 usa `object_type`, `object_id` e
  `current_places`;
- há divergências adicionais no DTO de definição e no autoload da facade.

Por isso, uma falha real do pacote aparece como indisponibilidade controlada no
`equivalencia`. O requerimento local não é apagado, a tela continua carregando e existe
uma ação para tentar a integração novamente.

## Persistência e banco local

As migrations locais novas que duplicavam `forms`, `workflow` e `activity_log` foram
removidas. Os arquivos históricos antigos continuam no repositório porque seus nomes
representam o histórico do banco de produção; eles não devem ser editados nem usados
para evoluir o schema. O app carrega a migration V2 oficial de Forms pelo provider, e o
provider do Workflow carrega as migrations oficiais do próprio pacote. A distinção entre
arquivos históricos, migration de transição e migrations ativas está em
[Migrations: histórico e ownership](migrations-ownership-e-historico.md).

Na inspeção de 07/08/2026, o banco configurado era MySQL
`eduardoferraz_equivalencia` e continha:

- 1 definição legada de workflow;
- 23 definições legadas de formulário;
- 0 objetos de workflow;
- 0 submissões de formulário.

Como as migrations históricas podem constar como executadas e a transição é destrutiva,
não se deve executar `migrate` ou `migrate:fresh` nesse banco sem confirmar que as
migrations V2 dos pacotes e a migration de transição estão disponíveis e sem validar a
estratégia na cópia local do banco de produção. Os testes do repositório usam SQLite em
memória e podem recriar o schema.

## Seeders

`WorkflowDefinitionsTableSeeder` chama o comando público `workflow:sync` usando a definição
v2 em `resources/workflows/equivalencia.json`. `FormDefinitionsTableSeeder` usa o serviço
público `FormDefinitionSyncService` para sincronizar a configuração em
`config/equivalencia-forms.php`. Ambos são idempotentes e não conhecem IDs ou tabelas dos
pacotes.
