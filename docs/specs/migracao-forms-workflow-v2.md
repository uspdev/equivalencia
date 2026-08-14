# Especificação — Migração e integração de Forms V2 e Workflow V2 no Equivalencia

## Problem Statement

O Equivalencia está em produção e possui dados operacionais de aproveitamentos automáticos que precisam ser preservados. A aplicação recebeu implementações locais de Forms e Workflow com migrations duplicadas, contratos incompatíveis e dependências cruzadas entre os pacotes.

O banco de produção não pode ser recriado com `migrate:fresh`. Ao mesmo tempo, as tabelas de Forms e Workflow não são utilizadas pelo fluxo operacional atual e foram aprovadas para descarte e regeneração controlados. A operação precisa ser explícita, limitada às tabelas autorizadas e validada primeiro sobre a cópia local recente do banco de produção.

A branch `refactor` do Workflow ainda possui incompatibilidades de código e de schema. Forms V2 também possui um contrato diferente do consumidor atual e exige compatibilidade de versão do Laravel. O objetivo desta especificação é definir a fronteira de integração do Equivalencia e as condições de implantação; os contratos próprios de Forms e Workflow ficam documentados nos respectivos repositórios.

## Solution

Executar uma implantação coordenada entre os três repositórios:

1. preservar todas as tabelas e dados operacionais do Equivalencia;
2. remover, por migration explícita, somente as tabelas de Forms e Workflow aprovadas para descarte;
3. deixar Forms e Workflow como donos exclusivos das próprias tabelas e migrations;
4. integrar o Equivalencia à API pública do Forms V2;
5. corrigir no Workflow somente o que bloqueia o fluxo mínimo necessário ao Equivalencia;
6. sincronizar e executar a definição de Workflow usada pelo fluxo de equivalência;
7. validar a migration e o fluxo completo na cópia local do banco de produção antes de qualquer implantação produtiva.

Esta especificação trata do consumidor e do procedimento de integração. O contrato do Forms V2 está documentado no repositório Forms; o contrato e o escopo do Workflow V2 estão documentados no repositório Workflow.

## User Stories

1. Como responsável pela aplicação em produção, quero atualizar o banco sem `migrate:fresh`, para preservar os dados dos aproveitamentos automáticos.

2. Como responsável pelo deploy, quero que a migration destrutiva tenha uma lista explícita de tabelas, para impedir a remoção acidental de dados fora do escopo.

3. Como desenvolvedor do Equivalencia, quero executar a operação primeiro sobre a cópia recente do banco de produção, para detectar incompatibilidades antes de atingir produção.

4. Como responsável pelos dados operacionais, quero registrar contagens e amostras dos aproveitamentos automáticos antes e depois da operação, para comprovar que eles permaneceram íntegros.

5. Como responsável pelo histórico do Laravel, quero manter a tabela de migrations sem edição manual, para que o framework continue reconhecendo o estado real do banco.

6. Como responsável pelo schema, quero que Forms seja o único dono das tabelas de Forms, para eliminar divergências entre migrations copiadas e migrations do pacote.

7. Como responsável pelo schema, quero que Workflow seja o único dono das tabelas de Workflow, para evitar dois proprietários concorrentes para a mesma estrutura.

8. Como desenvolvedor do Equivalencia, quero remover as migrations locais duplicadas dos pacotes, para que futuras alterações tenham uma fonte de verdade única.

9. Como responsável pelo deploy, quero disponibilizar as versões compatíveis dos pacotes e a migration de transição na mesma implantação, para evitar que o consumidor execute contra um schema incompleto.

10. Como consumidor do Forms V2, quero localizar uma definição pelo nome e, opcionalmente, pela versão, para renderizar e validar o formulário correto.

11. Como consumidor do Forms V2, quero usar a facade e os serviços públicos do pacote, para não depender de classes internas ou do contrato antigo.

12. Como consumidor do Workflow V2, quero sincronizar a definição `equivalencia`, para disponibilizar o fluxo necessário à aplicação.

13. Como operador do Equivalencia, quero que a definição sincronizada seja executável, para não depender de uma etapa administrativa de publicação que não existe no fluxo previsto.

14. Como operador do Equivalencia, quero que o sync valide todas as referências antes de persistir, para evitar um banco parcialmente sincronizado.

15. Como usuário do Equivalencia, quero criar ou localizar de forma idempotente o WorkflowObject de cada Aproveitamento, para não gerar processos duplicados.

16. Como usuário do Equivalencia, quero iniciar o processo em `aluno_inicio`, para refletir o estado inicial definido para a equivalência.

17. Como usuário do Equivalencia, quero executar a transição inicial sem formulário, para encaminhar o aproveitamento à conferência sem exigir dados adicionais.

18. Como usuário do Equivalencia, quero executar a transição de indicação com o formulário `obs`, para registrar a observação e encaminhar o processo ao próximo estado.

19. Como auditor do processo, quero consultar o estado atual e o histórico de transições, para acompanhar a evolução real do aproveitamento.

20. Como responsável pela integração, quero que referências a roles sejam resolvidas pelo consumidor, para que Workflow não dependa de tabelas ou mecanismos de autorização próprios.

21. Como responsável pelo deploy, quero interromper a operação diante de falha de migration ou de integração, para não considerar um banco parcialmente atualizado como válido.

22. Como responsável pela operação, quero repetir a simulação a partir de uma cópia limpa quando houver falha, para separar correção de problema e recuperação de estado.

23. Como responsável pelo escopo, quero manter detalhes técnicos de notifications fora desta implantação, até que Lucas defina a implementação adequada.

24. Como desenvolvedor, quero que esta etapa produza somente documentação, para que nenhuma alteração de código seja confundida com uma decisão já aprovada.

## Implementation Decisions

- O fluxo operacional de aproveitamentos automáticos e suas tabelas ficam fora do descarte. As tabelas de usuários, roles, permissões, disciplinas, arquivos e demais áreas operacionais também permanecem.

- A operação de regeneração autorizada contempla somente estas tabelas de Forms: `form_definitions`, `form_submissions` e `activity_log`.

- A operação de regeneração autorizada contempla somente estas tabelas de Workflow: `workflow_definitions`, `workflow_objects`, `workflow_history`, `workflow_role_users`, `workflow_role_emails` e `user_workflow_definition`.

- A lista acima é uma autorização de escopo, não uma instrução para executar o descarte nesta etapa documental. A migration destrutiva e a ordem de execução serão implementadas e validadas posteriormente.

- A tabela de migrations do Laravel não será apagada, recriada ou editada manualmente. Migrations históricas que já fazem parte da branch principal do Equivalencia não serão alteradas.

- Migrations novas introduzidas por Forms V2 e Workflow V2 podem ser ajustadas antes da implantação. Essa liberdade não se aplica às migrations históricas da branch principal, que já representam o banco em produção.

- Forms será o dono das migrations e tabelas de definições, submissões e auditoria de Forms. Workflow será o dono das migrations e tabelas de definições, objetos e histórico de Workflow. O Equivalencia não manterá cópias concorrentes dessas migrations.

- Os `ServiceProvider` dos pacotes devem carregar diretamente as migrations que pertencem a cada pacote. Por isso, as cópias mantidas no Equivalencia são duplicações da mesma responsabilidade e devem ser removidas em alteração controlada.

- A migration de transição do Equivalencia e as migrations V2 dos pacotes devem ser disponibilizadas de forma coordenada. O Equivalencia não deve tentar recriar a lógica do schema pertencente aos pacotes.

- O Equivalencia usará a API pública do Forms V2 para renderização, validação, submissão, consulta e auditoria. A classe interna de formulário não será tratada como API de integração.

- A integração precisa resolver a diferença de requisito entre o Equivalencia atual e o Forms V2 antes da implementação. O Equivalencia está atualmente em Laravel 11 e o Forms V2 exige Laravel 12; esta compatibilidade não será presumida.

- No Workflow, formulário omitido, nulo ou explicitamente falso significa que a transição não utiliza formulário. Um nome de formulário indica uma definição a ser resolvida pelo Forms V2.

- O fluxo mínimo do consumidor é: carregar a definição `equivalencia`; iniciar ou localizar um WorkflowObject para `App\\Models\\Aproveitamento`; começar em `aluno_inicio`; executar `tr_inicio_conferencia` sem formulário; executar `tr_conferencia_indica` com `obs`; persistir a submissão quando aplicável; e consultar o histórico.

- O Workflow deve usar o contrato de schema V2 instalado para tipo e identificador do objeto, places atuais, transições e histórico. O consumidor não criará aliases locais para mascarar divergências entre contratos.

- A sincronização do Workflow deve disponibilizar uma definição executável como `published`. A regra de não permitir `draft` em nenhuma definição executável pertence ao contrato do Workflow V2.

- A definição desta integração não amplia o escopo para modernização geral do Workflow, Forms ou do Equivalencia. Correções devem ser feitas nos repositórios donos e somente quando bloquearem o fluxo previsto.

- Notifications permanece apenas com o comportamento funcional necessário ao contrato do Workflow. Eventos, listeners, Mailables, filas, retries, falhas e operação do worker serão definidos por Lucas em documentação própria do Workflow antes da implementação técnica.

- Não será exigido backup ou arquivamento adicional das tabelas de Forms e Workflow antes da regeneração aprovada. A segurança operacional desta decisão será obtida pela simulação na cópia recente do banco de produção; os dados operacionais do Equivalencia continuam preservados no banco alvo.

- Nenhuma alteração de código, migration ou provider faz parte desta etapa. Esta especificação registra decisões para orientar uma etapa posterior de implementação e validação.

## Testing Decisions

- O principal seam de validação será um teste de integração no nível do fluxo do Equivalencia, usando as versões reais dos pacotes e a cópia local recente do banco de produção. Mocks de internals não substituem esse teste.

- Antes da migration, registrar contagens e amostras identificáveis dos dados de aproveitamentos automáticos. Depois, confirmar que as mesmas informações continuam presentes e inalteradas.

- Executar `php artisan migrate` sobre a cópia derivada da produção. O cenário não deve usar `migrate:fresh`.

- Confirmar que somente as tabelas autorizadas de Forms e Workflow foram removidas e recriadas, e que as tabelas operacionais do Equivalencia permaneceram.

- Confirmar que não houve edição manual da tabela de migrations e que as migrations V2 dos pacotes foram executadas pelo mecanismo esperado.

- Confirmar o fluxo funcional completo: sync, definição executável, criação ou localização idempotente do objeto, consulta do estado, transição sem formulário, transição com `obs`, submissão no Forms V2 e histórico no Workflow.

- Confirmar os casos de falha: definição inválida, referência de place inválida, formulário inexistente, role inexistente e falha de migration. O sync não deve persistir parcialmente quando a validação falhar.

- Se a simulação falhar, interromper a avaliação e corrigir a causa antes de qualquer implantação produtiva. A repetição deve partir de uma cópia limpa do banco de produção, sem editar manualmente o histórico de migrations.

## Out of Scope

- `migrate:fresh` em produção.
- Alteração, remoção ou edição manual de migrations históricas do Equivalencia.
- Preservação ou arquivamento de definições, submissões, objetos, históricos e logs de Forms/Workflow nesta regeneração aprovada.
- Migração ou backfill de dados legados de Forms/Workflow nesta implantação.
- Reescrita geral do Workflow ou modernização de código que não afete o fluxo do Equivalencia.
- Definição do contrato interno ou da implementação técnica de notifications.
- Criação de Jobs, Mailables, listeners ou configuração de fila nesta etapa documental.
- Alterações de código nesta etapa.
- Publicação desta especificação em issue tracker ou qualquer sistema externo.

## Further Notes

- A cópia recente do banco de produção é o ambiente de simulação escolhido para reduzir o risco de incompatibilidade. Ela não substitui a execução real da migration nem a verificação dos dados preservados.

- As especificações dos pacotes são deliberadamente separadas. Alterações no contrato do Forms devem ser registradas no repositório Forms; alterações no contrato do Workflow, no repositório Workflow; decisões de consumo, implantação e preservação de dados, no Equivalencia.

- Os documentos existentes do Workflow não devem ser alterados. O contrato V2 e as divergências resolvidas devem ser registrados nos novos documentos do repositório Workflow.

- A seleção, inclusão e commit dos arquivos ficam sob responsabilidade do desenvolvedor. Esta especificação não autoriza operações de Git.
