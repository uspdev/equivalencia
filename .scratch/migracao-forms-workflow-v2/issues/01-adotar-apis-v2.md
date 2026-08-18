# 01 — Adotar Laravel 12 e as APIs V2 no Equivalencia

**What to build:** O Equivalencia inicia com as versões compatíveis dos pacotes e usa os contratos públicos do Forms V2 e do Workflow V2, sem depender das APIs internas ou legadas.

**Blocked by:** Forms 02 — Disponibilizar a API pública de consumo do Forms V2; Workflow 04 — Executar a transição com o formulário obs.

**Status:** done

- [x] A aplicação executa com a versão de Laravel exigida pelo Forms V2.
- [x] Forms é consumido pela facade ou serviços públicos para definição, renderização, validação e submissão.
- [x] Workflow é consumido pelo serviço e pelo contrato V2 definidos para o fluxo mínimo.
- [x] O adapter do Equivalencia traduz resultados e falhas sem expor detalhes internos dos pacotes.
- [x] As chamadas ao contrato antigo e às classes internas deixam de ser necessárias no fluxo suportado.
- [x] Um teste de inicialização e integração básica confirma que os dois pacotes podem ser carregados juntos.
