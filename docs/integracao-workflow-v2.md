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
captura exceções e falhas de persistência. Os detalhes técnicos são registrados no log;
a interface recebe mensagens tratáveis e DTOs locais. Nenhuma view local inclui views do
`workflow` ou do `forms`.

## Contratos encontrados na inspeção

### Workflow

Disponíveis na branch `refactor`:

- serviço público `WorkflowService::start`, `find` e `loadDefinition`;
- comando público `workflow:sync --path=...`;
- métodos públicos de objeto documentados para `apply`, `workflowState`, `getHistory` e
  `currentPlaces`.

### Forms

Disponíveis na versão V2 instalada:

- facade pública `Forms` para definição ativa ou versionada, renderização, validação,
  submissão e consulta;
- serviço público `FormDefinitionSyncService::syncDefinition`, usado pelo seeder da
  aplicação;
- comando público `forms:sync` e endpoints públicos, incluindo a busca de disciplinas.

A aplicação não usa models, controllers, rotas administrativas ou views internas desses
pacotes.

## Tratamento de falhas

Falhas de definição, persistência, validação ou transição não atravessam a fronteira como
models ou exceções dos pacotes. O adapter registra o detalhe técnico e retorna um dos
resultados locais. Erros de validação preservam os erros por campo; as demais falhas
recebem mensagens seguras para a aplicação. Uma falha nunca é convertida em sucesso.

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

`WorkflowDefinitionsTableSeeder` chama `WorkflowSyncService::sync()` usando a definição V2
em `resources/workflows/equivalencia.json`. `FormDefinitionsTableSeeder` usa o serviço
público `FormDefinitionSyncService` para sincronizar a configuração em
`config/equivalencia-forms.php`. Ambos são idempotentes, não conhecem IDs ou tabelas dos
pacotes e deixam a execução falhar quando o sync não pode ser concluído.

O `ConfiguredWorkflowRoleResolver` atende ao contrato público `RoleResolver` com as roles
declaradas pela aplicação. Isso permite que o sync valide todas as referências antes de
publicar uma definição.

## Verificação da onda 5

`PackagesV2IntegrationTest` inicia a aplicação em Laravel 12, resolve os serviços públicos
dos dois pacotes e o adapter local, cria somente os schemas oficiais dos pacotes em SQLite,
sincroniza as definições e valida uma integração básica real. O teste cobre definição,
renderização, validação, submissão e consulta no Forms, além de início e consulta de um
workflow pelo gateway.
