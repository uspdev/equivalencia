Abaixo está o dicionário de dados da tabela DISCIPLINAGR no formato Markdown. A princípio as informações que queremos extrair são: codtur (2024-02-01), sitdisc (disciplina ativa ou não)

### Tabela: DISCIPLINAGR

**Descrição:** Dados das versões de Disciplinas de graduação da USP.

**Colunas:**

* **`coddis`** (`codigo-disciplina`)
* **Tipo:** `char(7)` | **Restrições:** PK (Primary Key), Obrigatório
* **Comentário:** Código da disciplina de graduação da USP. Ex: ACA0410, BAB0164, MAC0110, MAT0245...
---
* **`verdis`** (`versao-disciplina`)
* **Tipo:** `tinyint` | **Restrições:** PK, Obrigatório
* **Comentário:** Versão da disciplina de graduação, acrescida de 1 a cada alteração de definição.
---
* **`nomdis`** (`nome-disciplina`)
* **Tipo:** `varchar(240)` | **Restrições:** Obrigatório
* **Comentário:** Nome completo da disciplina de graduação.
---
* **`creaul`** (`credito-aula`)
* **Tipo:** `tinyint` | **Valor por Omissão:** `0`
* **Comentário:** Número de créditos da disciplina para aulas teóricas, seminários e aulas práticas (cada crédito-aula corresponde a 15 horas-aula).
---
* **`cretrb`** (`credito-trabalho`)
* **Tipo:** `tinyint` | **Valor por Omissão:** `0`
* **Comentário:** Número de créditos da disciplina para trabalhos planeados, execução e aval. de pesquisas/ trabalhos de campo, internato e estágio supervis./ leituras programadas / trabalhos especiais conforme a discipl./ excursões programadas. (O valor do créd.trab. é de 30 hrs).
---
* **`dtaatvdis`** (`data-ativacao-disciplina`)
* **Tipo:** `smalldatetime`
* **Comentário:** Data de ativação desta versão da disciplina de graduação.
---
* **`dtadtvdis`** (`data-desativacao-disciplina`)
* **Tipo:** `smalldatetime`
* **Comentário:** Data de desativação desta versão da disciplina de graduação.
---
* **`durdis`** (`duracao-disciplina`)
* **Tipo:** `tinyint` | **Valor por Omissão:** `0`
* **Comentário:** Tempo previsto para se ministrar a disciplina (em semanas).
---
* **`objdis`** (`objetivo-disciplina`)
* **Tipo:** `text`
* **Comentário:** Descrição do objetivo da disciplina.
---
* **`numvagdis`** (`numero-vagas-disciplina`)
* **Tipo:** `smallint`
* **Comentário:** Número de vagas para alunos regulares.
---
* **`numvagdiscpl`** (`numero-vagas-disciplina-complementar`)
* **Tipo:** `tinyint`
* **Comentário:** Número de vagas complementares para alunos especiais.
---
* **`pgmdis`** (`programa-disciplina`)
* **Tipo:** `text`
* **Comentário:** Descrição do programa da disciplina.
---
* **`pgmrsudis`** (`programa-resumo-disciplina`)
* **Tipo:** `text`
* **Comentário:** Resumo do programa da disciplina, para impressão do catálogo de Cursos da Graduação.
---
* **`tipdis`** (`tipo-disciplina`)
* **Tipo:** `char(1)`
* **Comentário:** *(Sem descrição detalhada fornecida nas imagens)*
---
* **`cgahoreto`** (`carga-horaria-estagio`)
* **Tipo:** `smallint`
* **Comentário:** *(Sem descrição detalhada fornecida nas imagens)*
---
* **`timestamp`** (`timestamp`)
* **Tipo:** `timestamp`
* **Comentário:** *(Sem descrição detalhada fornecida nas imagens)*
---
* **`staarecln`** (`status-area-clinica`)
* **Tipo:** `char(1)` | **Valor por Omissão:** `'N'`
* **Comentário:** *(Sem descrição detalhada fornecida nas imagens)*
---
* **`nomdisord`** (`nome-disciplina-ordenado`)
* **Tipo:** `varchar(240)`
* **Comentário:** *(Sem descrição detalhada fornecida nas imagens)*
---
* **`cgahorlcn`** (`carga-horaria-licenciatura`)
* **Tipo:** `smallint`
* **Comentário:** *(Sem descrição detalhada fornecida nas imagens)*
---
* **`staofe
davl`** (`status-oferece-segunda-avaliacao`)
* **Tipo:** `char(1)` | **Valor por Omissão:** `'S'`
* **Comentário:** Indica se a turma oferece ou não segunda avaliação (S/N).
---
* **`stapmdcfthor`** (`status-permitir-conflito-horario`)
* **Tipo:** `char(1)`
* **Comentário:** Indica se a disciplina permite conflito de horário (S/Null).
---
* **`cgaacdciecul`** (`carga-academico-cientifico-cultural`)
* **Tipo:** `smallint`
* **Comentário:** Carga horária de atividades académico-científico-culturais.
---
* **`numpro`** (`numero-processo`)
* **Tipo:** `char(11)`
* **Comentário:** Número que identifica um Processo ou Protocolado da USP. Regra de formação: '[03456789][0-9][0152][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]' onde os 2 primeiros números indicam o ano ( > 30 ou 0n a partir de 2000 ); 0 ou 1 = processo e 5 = protocolado; os 5 seguintes definem um número sequencial; os próximos 2 representam o código da unidade e o último caracter é o dígito de controlo.
---
* **`nomdisepa`** (`nome-disciplina-espanhol`)
* **Tipo:** `varchar(240)`
* **Comentário:** Nome completo da disciplina de graduação no idioma espanhol.
---
* **`objdisepa`** (`objetivo-disciplina-espanhol`)
* **Tipo:** `text`
* **Comentário:** Descrição do objetivo da disciplina no idioma espanhol.
---
* **`pgmdisepa`** (`programa-disciplina-espanhol`)
* **Tipo:** `text`
* **Comentário:** Descrição do programa da disciplina no idioma espanhol.
---
* **`pgmrsudisepa`** (`programa-resumo-disciplina-espanhol`)
* **Tipo:** `text`
* **Comentário:** Resumo do programa da disciplina no idioma espanhol.
---
* **`nomdisigl`** (`nome-disciplina-ingles`)
* **Tipo:** `varchar(240)`
* **Comentário:** Nome completo da disciplina de graduação no idioma inglês.
---
* **`objdisigl`** (`objetivo-disciplina-ingles`)
* **Tipo:** `text`
* **Comentário:** Descrição do objetivo da disciplina no idioma inglês.
---
* **`pgmdisigl`** (`programa-disciplina-ingles`)
* **Tipo:** `text`
* **Comentário:** Descrição do programa da disciplina no idioma inglês.
---
* **`pgmrsudisigl`** (`programa-resumo-disciplina-ingles`)
* **Tipo:** `text`
* **Comentário:** Resumo do programa da disciplina no idioma inglês.
---
* **`sitdis`** (`situacao-disciplina`)
* **Tipo:** `char(2)` | **Valor por Omissão:** `'PE'`
* **Comentário:** Indica a situação em que a disciplina se encontra: PE- pendente, AU- aguardando análise da própria UNIDADE, AO- aguardando análise de OUTRAS unidades, AT- ativada, AP- aprovada, DT- desativada. Esta coluna indica em qual parte do fluxo de aprovação se encontra a disciplina.
---
* **`dtacad`** (`data-cadastro`)
* **Tipo:** `smalldatetime` | **Valor por Omissão:** `getdate()`
* **Comentário:** Data de cadastro.
---
* **`codpescad`** (`codigo-pessoa-cadastrou`)
* **Tipo:** `int`
* **Comentário:** Número USP da pessoa que cadastrou esta linha.
---
* **`dtaultalt`** (`data-ultima-alteracao`)
* **Tipo:** `smalldatetime`
* **Comentário:** Data da última alteração de alguma informação desta linha.
---
* **`codpesalt`** (`codigo-pessoa-alterou`)
* **Tipo:** `int`
* **Comentário:** Número da pessoa que efetuou a última alteração nesta linha.
---
* **`codlinegr`** (`codigo-lingua-estrangeira`)
* **Tipo:** `char(3)` | **Restrições:** FK (Foreign Key)
* **Comentário:** Código do idioma (seguindo a ISO639-3), quando a disciplina é oferecida em língua estrangeira.
---
