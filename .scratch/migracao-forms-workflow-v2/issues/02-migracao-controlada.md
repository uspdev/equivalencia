# 02 — Executar a migração controlada e remover migrations duplicadas

**What to build:** O banco do Equivalencia pode migrar para os schemas V2 sem `migrate:fresh`, descartando somente as tabelas autorizadas e preservando os dados operacionais dos aproveitamentos automáticos.

**Blocked by:** Equivalencia 01 — Adotar Laravel 12 e as APIs V2 no Equivalencia; Forms 01 — Consolidar schema e versionamento do Forms V2; Workflow 01 — Alinhar schema, migrations e histórico do Workflow V2.

**Status:** ready-for-agent

- [ ] Existe uma migration de transição explícita com somente as tabelas de Forms e Workflow aprovadas para descarte.
- [ ] Nenhuma migration histórica do Equivalencia é alterada ou editada manualmente.
- [ ] As migrations locais duplicadas dos pacotes são removidas do consumidor.
- [ ] As migrations dos pacotes são carregadas diretamente pelos respectivos providers.
- [ ] A tabela de migrations do Laravel permanece intacta.
- [ ] O procedimento usa migrations incrementais e não usa `migrate:fresh`.
- [ ] A operação não faz backfill nem backup obrigatório das tabelas descartadas.
- [ ] A migration pode ser executada sobre a cópia local do banco de produção sem remover dados operacionais fora da lista aprovada.
