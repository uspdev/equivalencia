# 03 — Integrar o fluxo completo de equivalência

**What to build:** O usuário consegue executar no Equivalencia o fluxo de aproveitamento com Workflow V2, incluindo estado inicial, conferência sem formulário, observação `obs`, histórico e respostas compreensíveis na aplicação.

**Blocked by:** Equivalencia 02 — Executar a migração controlada e remover migrations duplicadas; Workflow 04 — Executar a transição com o formulário obs.

**Status:** ready-for-agent

- [ ] O Aproveitamento cria ou localiza seu WorkflowObject sem duplicação.
- [ ] A aplicação apresenta e utiliza o estado inicial `aluno_inicio`.
- [ ] A conferência inicial funciona sem formulário.
- [ ] A indicação de docente usa o formulário `obs` e persiste sua submissão.
- [ ] O estado, as ações disponíveis e o histórico podem ser consultados pelo fluxo da aplicação.
- [ ] Falhas de sync, transição, formulário ou role são convertidas em respostas tratáveis pelo Equivalencia.
- [ ] O fluxo não afeta os dados existentes de aproveitamentos automáticos.
