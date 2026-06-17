Abaixo está o dicionário de dados da tabela HISTESCOLARGR no formato Markdown. (doc incompleta, existem mais colunas do que o informado). A princípio as informações que queremos extrair são: programadisciplina, programaresumodisciplina, objetivo-disciplina

### Tabela: HISTESCOLARGR

**Descrição:** Histórico escolar do aluno: contém os dados de todas as matrículas efetuadas pelo aluno.

**Colunas:**

* **`codpes`** (`codigo-pessoa`)
* **Tipo:** `int` | **Restrições:** PK (Primary Key), FK (Foreign Key), Obrigatório
* **Comentário:** Código de identificação do aluno.
---
* **`codpgm`** (`codigo-programa`)
* **Tipo:** `tinyint` | **Restrições:** PK, FK, Obrigatório
* **Comentário:** Código que identifica cada programa do aluno.
---
* **`coddis`** (`codigo-disciplina`)
* **Tipo:** `char(7)` | **Restrições:** PK, FK, Obrigatório
* **Comentário:** Código atribuído às disciplinas da USP.
---
* **`verdis`** (`versao-disciplina`)
* **Tipo:** `tinyint` | **Restrições:** PK, FK, Obrigatório
* **Comentário:** Versão da disciplina acrescido de 1 a cada alteração sofrida pela disciplina.
---
* **`codtur`** (`codigo-turma`)
* **Tipo:** `char(7)` | **Restrições:** PK, FK, Obrigatório
* **Comentário:** Código de identificação da turma.
---
* **`dtacrihst`** (`data-criacao-historico`)
* **Tipo:** `smalldatetime` | **Restrições:** Obrigatório
* **Comentário:** Data de criação do histórico escolar independentemente da 1.a situação da matrícula: inscrição, pré-matrícula ou matrícula.
---
* **`notfim`** (`nota-final`)
* **Tipo:** `decimal(4,2)`
* **Comentário:** Nota final da disciplina do programa, validada pelo docente.
---
* **`notfim2`** (`nota-final-2`)
* **Tipo:** `decimal(4,2)`
* **Comentário:** Nota final da 2.a avaliação, da disciplina do programa, validada pelo docente.
---
* **`frqfim`** (`frequencia-final`)
* **Tipo:** `decimal(5,2)`
* **Comentário:** Frequência final da disciplina do programa, validada pelo docente.
---
* **`rstfim`** (`resultado-final`)
* **Tipo:** `char(2)`
* **Comentário:** Indica o resultado final da matrícula na disciplina: A - Aluno aprovado na disciplina; AR - Aluno aprovado por Reunião Pedagógica; D - Dispensado devido a equivalência entre disciplinas; R - Aluno cursou disciplina em recuperação; RA - Aluno reprovado por ambos (nota e frequência); RF - Aluno reprovado por frequência; RN - Aluno reprovado por nota; ou T - Aluno trancou disciplina (trancamento parcial).
---
* **`dtavalfim`** (`data-validacao-final`)
* **Tipo:** `smalldatetime`
* **Comentário:** Data de validação final, cuja existência indica que a nota não pode mais ser alterada.
---
* **`dtavalfim2`** (`data-validacao-final-2`)
* **Tipo:** `smalldatetime`
* **Comentário:** Data de validação final da 2.a avaliação, cuja existência indica que a nota não pode mais ser alterada.
---
* **`stamtr`** (`status-matricula`)
* **Tipo:** `char(1)` | **Restrições:** Obrigatório
* **Comentário:** Situação da matrícula: E - excluído; I - inscrição; P - pré matrícula; M - matrícula, R - remoção forçada (via tela Histórico de Matrícula que permite a remoção de matrícula em turma já consolidada).
---
* **`discrl`** (`disciplina-curricular`)
* **Tipo:** `char(1)` | **Restrições:** FK
* **Comentário:** Informe sobre a característica da Disciplina: O - obrigatória, L - optativa livre, C - optativa complementar e N - extra-curricular.
---
* **`primtralu`** (`prioridade-matricula-aluno`)
* **Tipo:** `tinyint`
* **Comentário:** Indica a prioridade escolhida pelo aluno para a matrícula em disciplinas optativas.
---
* **`stacrihstesc`** (`status-criacao-historico-escolar`)
* **Tipo:** `char(1)`
* **Comentário:** Indica a situação inicial de cadastro do Histórico Escolar (pois o status-matricula é alterado conforme as mudanças de situação). Pode ser: P=pré-matrícula, M=matrícula, R=requerimento de matrícula, H=matrícula forçada (via tela 'Histórico de Matrícula' que permite cadastro ou remoção em turmas já consolidadas).
---
* **`dtaultalt`** (`data-ultima-alteracao`)
* **Tipo:** `smalldatetime`
* **Comentário:** Data em que ocorreu a última atualização no Histórico Escolar.
---
* **`codpesalt`** (`codigo-pessoa-alterou`)
* **Tipo:** `int` | **Restrições:** FK
* **Comentário:** Código da pessoa que efetuou a última atualização no Histórico escolar (Matrícula) deste programa.
---
* **`timestamp`** (`timestamp`)
* **Tipo:** `timestamp`
* **Comentário:** *(Sem descrição detalhada fornecida nas imagens)*
---
* **`aplori`** (`aplicativo-origem`)
* **Tipo:** `char(1)`
* **Comentário:** Indica qual aplicativo foi utilizado para efetuar a matrícula: J = Júpiter, W = Júpiter Web ou C = Carga.
---
* **`numseqtur`** (`numero-sequencial-turma`)
* **Tipo:** `tinyint` | **Restrições:** FK
* **Comentário:** Número sequencial que identifica a subdivisão da turma conforme a característica da disciplina.
---
* **`aplorifrqnot`** (`aplicativo-origem-frequencia-nota`)
* **Tipo:** `char(1)`
* **Comentário:** Indica se o aplicativo que cadastrou/alterou a frequência e/ou nota e validações foi o Júpiter (cliente-servidor) ou o JúpiterWeb.
---
* **`dtlstamtr`** (`detalhamento-status-matricula`)
* **Tipo:** `char(1)`
* **Comentário:** Detalhamento do status da matrícula. Atualmente utilizado pela Escola Politécnica, é atualizado nas etapas de consolidação das matrículas dos alunos da EP (enquanto o stamtr for 'T'). Pode ser: Reservado (R), Turma lotada (T), em Lista de espera (L), Preterida (P - Obs: Nesse caso indica as matrículas que o sistema excluiu porque o aluno escolheu menos disciplinas que as selecionadas) ou Matriculado (M).
---
* **`primtr`** (`prioridade-matricula`)
* **Tipo:** `tinyint`
* **Comentário:** Indica a prioridade de matrícula nesta disciplina, conforme a classificação do programa do aluno. Obs: utilizado somente para os alunos da Escola Politécnica.
---
* **`clsesmalutur`** (`classif-estimada-aluno-turma`)
* **Tipo:** `smallint`
* **Comentário:** Classificação estimada do aluno na turma. Permite ao aluno decidir se mantém a matrícula nesta turma ou se migra para outra onde tenha mais possibilidade de ser deferido.
---