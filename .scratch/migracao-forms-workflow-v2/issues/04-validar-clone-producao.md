# 04 — Validar a implantação na cópia do banco de produção

**What to build:** A equipe obtém evidência reproduzível de que a migração e o fluxo integrado funcionam em uma cópia recente do banco de produção antes de avaliar qualquer implantação produtiva.

**Blocked by:** Equivalencia 03 — Integrar o fluxo completo de equivalência.

**Status:** ready-for-agent

- [ ] Contagens e amostras identificáveis dos aproveitamentos automáticos são registradas antes da migration.
- [ ] A migration é executada no clone usando o fluxo incremental normal, sem `migrate:fresh`.
- [ ] Somente as tabelas autorizadas de Forms e Workflow são removidas e recriadas.
- [ ] Os dados operacionais do Equivalencia permanecem presentes e inalterados após a operação.
- [ ] O fluxo completo de equivalência é executado com os pacotes reais e o schema recriado.
- [ ] O histórico de migrations permanece íntegro.
- [ ] O resultado registra critérios objetivos de aprovação ou bloqueio antes de qualquer decisão de produção.
