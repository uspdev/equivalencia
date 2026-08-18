# Plano de implementação em ondas — Forms V2, Workflow V2 e Equivalencia

Status: execução em andamento — ondas 1 a 5 concluídas  
Data: 2026-08-17
Última atualização: 2026-08-18

## 1. Objetivo

Coordenar a implementação dos 11 tickets distribuídos entre os repositórios
`forms`, `workflow` e `equivalencia`, preservando os dados operacionais já
existentes e entregando o fluxo de equivalência funcionando com as versões V2
dos dois pacotes.

O plano separa claramente:

- a evolução do schema e dos contratos pertencentes ao Forms;
- as correções mínimas necessárias no Workflow para o fluxo do Equivalencia;
- a integração, a migração controlada e a validação no clone recente do banco
  de produção.

Cada ticket deve ser entregue ao agente responsável pelo seu repositório. Um
ticket posterior só deve começar quando as condições de entrada do ticket
anterior estiverem confirmadas. Nenhuma etapa deste plano autoriza
`migrate:fresh` em produção.

### 1.1. Acompanhamento objetivo

Legenda: ✅ concluído; 🟡 em andamento; ⬜ não iniciado.

| Ticket | Status | Evidência ou pendência principal |
| --- | --- | --- |
| F01 | ✅ Concluído | Schema, versionamento, migrations V2 e provider implementados no Forms. |
| F02 | ✅ Concluído | API pública do Forms V2 implementada, testada e documentada. |
| W01 | ✅ Concluído | Schema, migrations e histórico V2 implementados no Workflow. |
| W02 | ✅ Concluído | Sync atômico, idempotente, publicado e com erros agrupados implementado. |
| W03 | ✅ Concluído | Fluxo mínimo sem formulário, estado e histórico implementados e testados. |
| W04 | ✅ Concluído | Transição `tr_conferencia_indica` usa `obs` pela API pública do Forms V2. |
| W05 | ✅ Concluído | Contrato funcional de notifications por roles implementado e testado; a entrega técnica continua pendente da decisão de Lucas e não faz parte de W05. |
| E01 | ✅ Concluído | Laravel 12, dependências V2, adapter público, seeders via APIs públicas e teste de carregamento conjunto implementados. |
| E02 | ⬜ Não iniciado | Aguardando a conclusão de E01. |
| E03 | ⬜ Não iniciado | Aguardando E02 e a integração do consumidor. |
| E04 | ⬜ Não iniciado | Aguardando o fluxo completo e a preparação do clone limpo de produção. |

Resumo das ondas: ondas 1 a 5 concluídas; ondas 6 a 8
não iniciadas.

## 2. Tickets abrangidos

### Forms — repositório `forms`

| ID | Ticket | Responsabilidade |
| --- | --- | --- |
| F01 | Consolidar schema e versionamento do Forms V2 | Tornar o Forms dono do próprio schema, das versões e do carregamento das migrations. |
| F02 | Disponibilizar a API pública de consumo do Forms V2 | Expor resolução, renderização, validação, submissão, atualização e auditoria sem exigir internals. |

Arquivos de referência:

- `.scratch/forms-v2-consumidores/issues/01-schema-e-versionamento.md`
- `.scratch/forms-v2-consumidores/issues/02-api-publica.md`

### Workflow — branch `refactor`, repositório `workflow`

| ID | Ticket | Responsabilidade |
| --- | --- | --- |
| W01 | Alinhar schema, migrations e histórico do Workflow V2 | Criar o schema V2 com migrations novas, ownership próprio e histórico compatível. |
| W02 | Sincronizar definições de Workflow de forma atômica | Validar e publicar definições sem persistência parcial. |
| W03 | Executar o fluxo mínimo sem formulário | Permitir iniciar o objeto e executar a conferência inicial sem chamar o Forms. |
| W04 | Executar a transição com o formulário `obs` | Integrar uma transição com o Forms V2 e persistir sua submissão. |
| W05 | Aplicar o contrato funcional de notifications por roles | Validar apenas o contrato funcional de roles e notifications previsto para o fluxo. |

Arquivos de referência:

- `.scratch/workflow-v2-refactor-equivalencia/issues/01-schema-migrations-historico.md`
- `.scratch/workflow-v2-refactor-equivalencia/issues/02-sync-atomico.md`
- `.scratch/workflow-v2-refactor-equivalencia/issues/03-fluxo-sem-formulario.md`
- `.scratch/workflow-v2-refactor-equivalencia/issues/04-transicao-obs.md`
- `.scratch/workflow-v2-refactor-equivalencia/issues/05-notifications-roles.md`

### Equivalencia — repositório `equivalencia`

| ID | Ticket | Responsabilidade |
| --- | --- | --- |
| E01 | Adotar Laravel 12 e as APIs V2 no Equivalencia | Atualizar o consumidor para as versões compatíveis e para os contratos públicos. |
| E02 | Executar a migração controlada e remover migrations duplicadas | Remover somente as tabelas aprovadas e deixar Forms/Workflow como donos das próprias migrations. |
| E03 | Integrar o fluxo completo de equivalência | Conectar o fluxo real do aproveitamento ao Workflow V2 e ao formulário `obs`. |
| E04 | Validar a implantação na cópia do banco de produção | Produzir evidência objetiva antes de qualquer decisão de implantação produtiva. |

Arquivos de referência:

- `.scratch/migracao-forms-workflow-v2/issues/01-adotar-apis-v2.md`
- `.scratch/migracao-forms-workflow-v2/issues/02-migracao-controlada.md`
- `.scratch/migracao-forms-workflow-v2/issues/03-fluxo-completo.md`
- `.scratch/migracao-forms-workflow-v2/issues/04-validar-clone-producao.md`

## 3. Regras que valem para todas as ondas

### Preservação do banco

- O banco de produção contém dados operacionais de aproveitamentos automáticos.
- Esses dados não podem ser apagados, recriados ou migrados por esta iniciativa.
- `migrate:fresh` é proibido em produção.
- A tabela `migrations` do Laravel não pode ser apagada, recriada nem editada
  manualmente.
- A validação deve ocorrer primeiro na cópia local recente do banco de
  produção, injetada no Equivalencia.

### Descarte autorizado

As tabelas de Forms e Workflow abaixo não possuem dados operacionais a
preservar e foram aprovadas para descarte e regeneração:

Forms:

- `form_definitions`;
- `form_submissions`;
- `activity_log`.

Workflow:

- `workflow_definitions`;
- `workflow_objects`;
- `workflow_history`;
- `workflow_role_users`;
- `workflow_role_emails`;
- `user_workflow_definition`.

O descarte deve ser feito por migration explícita do Equivalencia, com lista
allowlist de tabelas e sem remover qualquer tabela operacional fora desse
escopo. As tabelas legadas de associação de roles e usuários do Workflow não
serão recriadas pelo schema V2.

Não foi exigido backup ou arquivamento adicional das definições, submissões,
objetos, históricos ou logs de Forms e Workflow. O mecanismo de segurança
operacional aprovado é a simulação no clone recente do banco de produção e a
verificação dos dados que devem permanecer.

### Ownership de migrations

- Forms é o único dono das migrations e tabelas de Forms.
- Workflow é o único dono das migrations e tabelas de Workflow.
- O Equivalencia mantém apenas a migration de transição específica da
  aplicação.
- As migrations históricas antigas do Equivalencia permanecem sem alteração,
  pois representam o histórico do banco de produção.
- Migrations novas dos pacotes podem ser alteradas antes da implantação, mas
  devem receber nomes novos posteriores ao histórico já executado.
- Os `ServiceProvider` dos pacotes devem carregar as migrations oficiais dos
  próprios pacotes. O consumidor não deve voltar a copiar ou duplicar essas
  migrations.

### Escopo de código

- O Workflow continua na branch `refactor`.
- No Workflow, só devem ser corrigidos os problemas que impedem o
  funcionamento previsto pelo Equivalencia e os contratos explicitamente
  descritos nos tickets.
- Não é objetivo desta entrega reescrever ou modernizar todo o Workflow.
- Os documentos existentes do Workflow não devem ser alterados; decisões e
  correções novas devem ser registradas somente em documentação nova.
- O Forms não deve persistir `flat_fields`; essa estrutura é derivada apenas
  em memória durante validação.
- Notifications não inclui, nesta iniciativa, a implementação técnica de
  eventos, listeners, Mailables, filas, retries ou operação de worker. Esses
  detalhes serão definidos por Lucas em documentação própria do Workflow.

## 4. Grafo de dependências

```text
F01 ──> F02 ───────────────────────────────┐
                                           │
W01 ──> W02 ──> W03 ──> W04 ──────────────┼──> E01 ──> E02 ──> E03 ──> E04
                       └──> W05 ───────────┘
```

As dependências formais são as declaradas nos próprios tickets. Há também um
gate operacional: embora W05 não seja uma dependência técnica direta de E01,
é preferível concluir W05 antes de iniciar E01 para que o contrato final de
sincronização e validação do Workflow não force retrabalho no adapter do
Equivalencia.

## 5. Ondas de execução

O plano tem oito ondas. Tickets dentro da mesma onda podem ser executados em
paralelo, desde que atuem em repositórios ou áreas independentes e respeitem
as condições de entrada.

### Onda 1 — Fundamentos dos pacotes

**Status da onda:** ✅ Concluída.

Tickets: F01 e W01, em paralelo.

Objetivo: estabelecer as bases de schema, migrations, ownership e contratos
de persistência que serão consumidas pelas ondas seguintes.

#### F01 — Schema e versionamento do Forms V2

**Status:** ✅ Concluído no repositório Forms.

O agente deve:

- consolidar as migrations V2 oficiais do Forms;
- garantir as colunas, índices e restrições de versão e status;
- assegurar uma única versão `active` por nome;
- resolver por nome a versão `active` e, quando informado, a versão exata;
- manter comportamento consistente para `draft`, `active` e `disabled`;
- fazer o `FormServiceProvider` carregar diretamente as migrations V2;
- manter migrations históricas sem alteração e sem backfill de dados legados.

Saída obrigatória:

- schema V2 executável pelo pacote;
- provider carregando a migration oficial;
- testes de unicidade e resolução por versão;
- confirmação de que não existe coluna persistida `flat_fields`.

Critério de passagem: F02 pode começar somente quando o schema e o provider
forem executáveis e os testes do ticket passarem.

#### W01 — Schema, migrations e histórico do Workflow V2

**Status:** ✅ Concluído no repositório Workflow.

O agente deve:

- criar migrations V2 com nomes novos, posteriores ao histórico;
- fazer o `WorkflowServiceProvider` carregar apenas a fonte oficial adequada
  ao schema V2, sem depender de cópias do consumidor;
- alinhar `workflow_definitions`, `workflow_objects` e `workflow_history` ao
  contrato V2;
- persistir tipo e identificador do objeto, places atuais e histórico;
- manter referências externas opcionais, sem foreign keys para Forms ou
  usuários;
- manter foreign keys internas do Workflow quando aplicáveis;
- não recriar tabelas legadas de associação entre Workflow, roles e usuários;
- tornar o histórico append-only e impedir remoção em cascata de objeto que
  possui histórico.

O agente não deve editar os arquivos históricos de migration do pacote apenas
para adaptar seu conteúdo ao V2. Como esses nomes podem já existir na tabela
`migrations` de produção, o V2 precisa de migrations novas.

Critério de passagem: W02 pode começar quando o schema V2 puder ser criado
sem depender das migrations históricas e os testes de persistência passarem.

### Onda 2 — Contratos públicos e sincronização

**Status da onda:** ✅ Concluída.

Tickets: F02 e W02, em paralelo.

#### F02 — API pública de consumo do Forms V2

**Status:** ✅ Concluído no repositório Forms.

O agente deve disponibilizar uma facade ou serviço público para:

- localizar uma definição por nome;
- localizar uma versão exata;
- resolver a versão `active`;
- renderizar e validar usando a mesma definição resolvida;
- submeter e atualizar dados sem chamadas diretas a classes internas;
- manter cada submissão vinculada à definição e à versão utilizada;
- produzir auditoria pelo mecanismo do Forms, sem duplicação no consumidor;
- emitir erros claros para definição inexistente, versão inválida e dados
  inválidos.

O contrato deve ser consumível por outra aplicação sem conhecimento de models,
controllers, views ou internals do Forms.

Critério de passagem: W04 e E01 só podem consumir o Forms quando a API estiver
testada e sua assinatura estiver documentada no ticket.

#### W02 — Sincronização atômica de definições do Workflow

**Status:** ✅ Concluído no repositório Workflow.

O agente deve corrigir o serviço de sync para:

- validar todas as definições, versões, places, transições, Forms e roles
  antes de persistir;
- agregar os erros de uma mesma execução;
- impedir qualquer persistência parcial em caso de erro;
- disponibilizar a definição executável como `published`;
- rejeitar `draft` para uma definição executável;
- interpretar `initial_places`, `from` e `tos` conforme o contrato V2;
- rejeitar role inexistente, resolver ausente ou falha do resolver com erro
  explícito.

O sync deve ser idempotente e adequado ao comando operacional previsto,
`php artisan workflow:sync`. A existência do comando não significa que o
usuário final invocará o serviço diretamente; a aplicação o consumirá por seu
contrato de runtime.

Critério de passagem: W03 só começa com sync válido, falha agrupada e ausência
comprovada de persistência parcial.

### Onda 3 — Fluxo mínimo do Workflow

**Status da onda:** ✅ Concluída.

Ticket: W03.

#### W03 — Fluxo mínimo sem formulário

**Status:** ✅ Concluído no repositório Workflow.

O agente deve implementar e testar o caminho que não depende do Forms:

1. localizar ou criar de forma idempotente um único `WorkflowObject` para o
   mesmo `Aproveitamento`;
2. iniciar o objeto em `aluno_inicio`;
3. executar `tr_inicio_conferencia`;
4. levar o objeto a `svgrad_conferencia`;
5. permitir consulta de estado, transições disponíveis e histórico;
6. registrar histórico compatível com o schema V2.

Formulário omitido, `null` ou `false` significa que a transição não utiliza
formulário. Nessa situação, o Workflow não pode renderizar, validar nem criar
submissão no Forms.

Critério de passagem: W04 pode começar quando a idempotência, a transição sem
formulário e a leitura do histórico estiverem cobertas por testes.

### Onda 4 — Formulário `obs` e contrato funcional de notifications

**Status da onda:** ✅ Concluída. A pendência técnica de entrega de
notifications atribuída a Lucas é posterior e não reabre W05.

Tickets: W04 e W05, em paralelo.

#### W04 — Transição com o formulário `obs`

**Status:** ✅ Concluído no repositório Workflow.

O agente deve:

- resolver `obs` pelo contrato público do Forms V2;
- validar e submeter os dados pela API pública;
- preservar o vínculo da submissão com a versão efetivamente resolvida;
- executar `tr_conferencia_indica` de `svgrad_conferencia` para
  `depto_indica_docente`;
- registrar no histórico a referência e os metadados da transição;
- não duplicar no histórico o payload completo do formulário;
- impedir a conclusão quando o Forms não encontrar a definição ou rejeitar os
  dados.

O ticket deve comprovar também a diferença entre uma transição sem formulário
e uma transição que referencia uma definição de formulário.

Critério de passagem: E01 só deve integrar o Forms e o Workflow depois que
essa transição for executável com os contratos públicos dos dois pacotes.

#### W05 — Notifications por roles

**Status:** ✅ Contrato funcional concluído no repositório Workflow. A
implementação técnica de entrega permanece pendente de decisão e documentação
de Lucas, fora do escopo de W05.

O agente deve implementar somente a validação funcional acordada:

- `notifications` aceita um único objeto opcional por transição;
- as roles de `tos` são os destinatários padrão;
- `append_roles` apenas acrescenta roles;
- `override_roles`, `users` e `emails` são rejeitados explicitamente;
- role inexistente impede o sync;
- role válida sem membros não invalida a transição;
- ausência total de roles não cria trabalho de notification;
- não há requisito de deduplicação;
- a transição permanece confirmada quando a role válida não possui membros.

Eventos, listeners, Mailables, filas, retries e tratamento técnico da entrega
ficam fora deste ticket e serão definidos por Lucas em outra documentação do
Workflow.

##### Pendência de Lucas — decisão e implementação técnica

W05 não implementou entrega de notifications. Ele encerrou somente o contrato
funcional de configuração, cálculo e validação de roles. As seguintes regras
já estão decididas e não precisam ser redefinidas por Lucas:

- as roles dos places em `tos` são os destinatários potenciais padrão;
- `append_roles` apenas acrescenta roles e não há deduplicação;
- `override_roles`, `users` e `emails` não pertencem ao contrato V2;
- role inexistente bloqueia o sync;
- role existente sem membros não desfaz a transição e não exige retry somente
  por estar vazia;
- ausência de roles calculadas não deve criar trabalho de entrega;
- o Workflow não é dono de usuários, membros de roles, conteúdo ou transporte.

Lucas ainda deve decidir e documentar:

1. qual será o mecanismo de integração depois da transição confirmada: evento,
   callback, chamada de serviço, outbox ou outra alternativa;
2. em que momento esse mecanismo será acionado e como garantir que nenhuma
   entrega seja iniciada antes do commit do estado e do histórico;
3. qual será o payload técnico definitivo, preferencialmente com
   identificadores e roles, sem serializar models completos nem o payload do
   formulário;
4. qual contrato o consumidor implementará para resolver os membros atuais das
   roles e executar a entrega;
5. se o processamento será síncrono ou assíncrono e quais canais serão
   suportados;
6. conteúdo, assunto, templates, idioma e demais dados pertencentes ao
   consumidor;
7. fila, idempotência, retries, tratamento de falhas, observabilidade,
   segurança e operação do worker, se houver;
8. quais testes de integração e quais instruções operacionais comprovarão a
   solução escolhida.

Depois dessas decisões, a implementação deverá respeitar esta divisão:

- no Workflow: disponibilizar o hook/contrato escolhido somente após a
  transição e o histórico estarem confirmados, não gerar trabalho para uma
  lista vazia de roles e não reverter a transição por falha posterior de
  entrega;
- no Equivalencia ou consumidor: resolver membros, definir conteúdo e canais,
  executar a entrega e operar fila, retries e observabilidade conforme a
  solução documentada.

Nenhuma dessas escolhas técnicas foi tomada durante W05. A implementação atual
apenas calcula `['roles' => [...]]`; esse retorno não foi definido como payload
final de evento ou Job. `WorkflowObject::apply()` não emite notification, e o
`RoleResolver` atual apenas verifica a existência de roles durante o sync, sem
resolver membros.

Critério de passagem: o contrato de configuração e seus erros precisam estar
testados, sem ampliar o escopo para ownership de usuários ou implementação de
entrega.

### Onda 5 — Adoção dos pacotes no Equivalencia

**Status da onda:** ✅ Concluída.

Ticket: E01.

Dependência formal: F02 e W04.  
Gate recomendado: W05 também concluído.

#### E01 — Laravel 12 e APIs V2

**Status:** ✅ Concluído. O Equivalencia executa em Laravel 12, carrega as
dependências Forms/Workflow V2 em conjunto e acessa o Workflow pelo adapter
público da aplicação. Os seeders deixaram de gravar nas tabelas internas dos
pacotes e usam os serviços públicos de sincronização.

O agente deve:

- alinhar o Equivalencia à versão de Laravel exigida pelo Forms V2;
- usar as versões compatíveis dos pacotes Forms e Workflow;
- consumir o Forms por facade/serviços públicos para definição, renderização,
  validação, submissão e consulta;
- consumir o Workflow pelo serviço e contrato V2 definidos para o fluxo;
- criar ou ajustar o adapter do Equivalencia para traduzir resultados e falhas;
- impedir que controllers, views ou services do consumidor dependam de
  internals dos pacotes;
- remover a necessidade das chamadas aos contratos antigos no fluxo suportado;
- testar o carregamento conjunto dos dois pacotes.

O adapter deve esconder detalhes técnicos e fornecer respostas tratáveis pela
aplicação. Falhas do pacote não devem ser mascaradas como sucesso.

Critério de passagem: o Equivalencia deve iniciar com as versões reais dos
pacotes e executar uma integração básica sem depender de schema local duplicado.

### Onda 6 — Migração controlada e ownership no consumidor

**Status da onda:** ⬜ Não iniciada.

Ticket: E02.

Dependências: E01, F01 e W01.

#### E02 — Migration controlada e remoção de duplicações

**Status:** ⬜ Não iniciado; depende da conclusão de E01.

O agente deve:

- manter a migration de transição explícita com a allowlist aprovada;
- remover do Equivalencia as migrations locais que duplicavam Forms, Workflow
  e `activity_log`;
- não alterar migrations históricas antigas do Equivalencia;
- não alterar nem editar manualmente a tabela `migrations`;
- confirmar que Forms e Workflow carregam as migrations oficiais pelos próprios
  providers;
- garantir que a operação seja incremental e não use `migrate:fresh`;
- não criar backfill nem exigir arquivamento das tabelas aprovadas;
- executar a operação primeiro na cópia local do banco de produção.

A migration de transição não deve recriar o schema dos pacotes. Sua função é
somente remover as tabelas autorizadas para que as migrations V2 oficiais
possam criá-las. O nome, a ordem e o comportamento da migration devem ser
compatíveis com os nomes novos fornecidos por Forms e Workflow.

Critério de passagem: a migração deve completar no clone sem tocar os dados
operacionais, mantendo intacto o histórico do Laravel e criando somente os
schemas oficiais dos pacotes.

### Onda 7 — Fluxo completo do Equivalencia

**Status da onda:** ⬜ Não iniciada.

Ticket: E03.

Dependências: E02 e W04.

#### E03 — Integração ponta a ponta

**Status:** ⬜ Não iniciado; depende de E02.

O agente deve validar o comportamento real da aplicação:

1. carregar/sincronizar a definição `equivalencia`;
2. iniciar ou localizar o WorkflowObject do `App\\Models\\Aproveitamento`;
3. iniciar em `aluno_inicio`;
4. executar `tr_inicio_conferencia` sem formulário;
5. consultar estado, ações disponíveis e histórico;
6. executar `tr_conferencia_indica` com o formulário `obs`;
7. persistir a submissão no Forms V2;
8. levar o processo a `depto_indica_docente`;
9. traduzir falhas de sync, transição, formulário e role em respostas
   tratáveis.

O fluxo não pode criar objetos duplicados nem afetar dados existentes de
aproveitamentos automáticos. O Equivalencia deve consumir os contratos
públicos e o Workflow deve continuar responsável pelo próprio estado e
histórico.

Critério de passagem: um teste de integração deve executar o fluxo completo
com os pacotes reais e o schema V2 recém-criado.

### Onda 8 — Validação final no clone de produção

**Status da onda:** ⬜ Não iniciada.

Ticket: E04.

Dependência: E03.

#### E04 — Evidência de implantação

**Status:** ⬜ Não iniciado; depende da conclusão do fluxo completo de E03 e de
uma cópia limpa e recente do banco de produção.

O agente deve usar uma cópia recente e limpa do banco de produção e registrar:

Antes da migration:

- conexão e identificação do banco usado;
- contagens dos dados operacionais de aproveitamentos automáticos;
- amostras identificáveis suficientes para comparação posterior;
- estado das migrations e das tabelas aprovadas.

Durante a migration:

- execução do fluxo incremental normal com `php artisan migrate`;
- ausência de `migrate:fresh`;
- registro dos batches e migrations executados;
- confirmação de que a operação usa os providers dos pacotes.

Depois da migration:

- somente as tabelas aprovadas foram removidas e recriadas;
- tabelas operacionais e dados de aproveitamentos automáticos permanecem;
- a tabela `migrations` permanece íntegra;
- tabelas legadas de associação não foram recriadas;
- o fluxo completo funciona no schema recriado;
- as contagens e amostras operacionais são iguais às registradas antes.

Critério de aprovação: todas as verificações acima passam e o resultado fica
registrado de forma reproduzível. Se houver falha, a implantação produtiva
fica bloqueada; a correção deve ser feita no repositório dono e a simulação
deve ser repetida a partir de uma cópia limpa.

## 6. Sequência operacional resumida

1. Entregar F01 e W01 a agentes independentes.
2. Revisar os contratos e resultados de F01/W01.
3. Entregar F02 e W02 em paralelo.
4. Entregar W03 após W02.
5. Entregar W04 e W05 em paralelo após W03 e suas dependências.
6. Entregar E01 após F02 e W04; preferencialmente também após W05.
7. Entregar E02 após E01, F01 e W01.
8. Entregar E03 após E02 e W04.
9. Preparar uma cópia limpa do banco de produção e executar E04.
10. Somente com E04 aprovado avaliar a implantação em produção.

Cada agente deve devolver, junto com sua implementação:

- arquivos alterados;
- testes executados e resultado;
- migrations criadas ou removidas;
- contratos públicos alterados;
- dependências satisfeitas e eventuais bloqueios;
- instruções de execução para o agente seguinte.

## 7. Condições de bloqueio

O trabalho deve parar e retornar ao repositório responsável quando ocorrer
qualquer uma destas situações:

- tentativa de editar migration histórica já representada em produção;
- necessidade de `migrate:fresh` ou de alteração manual da tabela `migrations`;
- migration de pacote com nome já usado no histórico do banco;
- provider que continua carregando cópias históricas ou duplicadas;
- perda, alteração ou contagem incompatível dos dados operacionais;
- definição Workflow parcialmente persistida após falha de validação;
- transição com formulário que usa internals do Forms;
- dependência de notification técnico não aprovado no escopo;
- falha no clone que ainda não foi reproduzida e explicada.

Um ticket bloqueado não deve ser contornado por alterações improvisadas em um
repositório downstream. A correção deve ser feita no ticket ou repositório que
é dono do contrato.

## 8. Resultado esperado ao final

Ao concluir E04, devem ser verdadeiras as seguintes condições:

- Forms e Workflow são donos exclusivos de suas tabelas e migrations;
- o Equivalencia não possui migrations duplicadas dos pacotes;
- migrations históricas antigas continuam preservadas como histórico;
- a transição do banco é explícita, incremental e limitada às tabelas
  autorizadas;
- os dados dos aproveitamentos automáticos permanecem intactos;
- o Workflow V2 sincroniza uma definição `published` sem persistência parcial;
- o fluxo inicial sem formulário funciona;
- a transição `obs` funciona pela API pública do Forms;
- estado e histórico podem ser consultados;
- o comportamento de roles/notifications respeita o contrato aprovado;
- a integração foi validada no clone recente do banco de produção;
- nenhuma decisão de produção é tomada antes da evidência final de E04.

Este documento organiza a execução. As regras detalhadas de cada pacote
continuam nos specs e tickets do respectivo repositório; qualquer divergência
deve ser resolvida no documento dono do contrato antes de avançar a onda.
