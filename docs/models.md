# Modelo de dados: Aproveitamentos

## Tabela `aproveitamentos`

- uma disciplina requerida, ou seja, a disciplina que o aluno deseja aproveitar;
- uma ou mais disciplinas cursadas, usadas como base para o aproveitamento;
- um histórico escolar único, obrigatório para requerimentos solicitados pelo aluno;
- informações de contexto do curso/habilitação;
- estado, tipo e dados administrativos da análise.

### Colunas

| Coluna | Descrição |
| --- | --- |
| `id` | Identificador do aproveitamento. |
| `estado` | Estado do fluxo. Usa `app/Enums/EquivalenciaEstado.php`. |
| `tipo` | Tipo do aproveitamento. Usa `app/Enums/EquivalenciaTipo.php`. |
| `codcur` | Código do curso no contexto do aproveitamento. |
| `codhab` | Código da habilitação no contexto do aproveitamento. |
| `numero_reuniao` | Número da reunião em que o aproveitamento foi analisado. |
| `data_reuniao` | Data da reunião em que o aproveitamento foi analisado. |
| `observacoes` | Observações administrativas ou de análise. |
| `historico_id` | Arquivo de histórico escolar único. Referencia `arquivos.id`. |
| `criado_por_id` | Usuário que criou o registro. Referencia `users.id`. |
| `alterado_por_id` | Usuário que alterou o registro. Referencia `users.id`. |
| `created_at` / `updated_at` | Timestamps padrão do Laravel. |

### Relações

- `disciplinas()`: todas as disciplinas do agregado;
    - `requerida()`: disciplina com `role = requerida`;
    - `cursadas()`: disciplinas com `role = cursada`;
- `historico()`: arquivo referenciado por `historico_id`.

O histórico é singular e obrigatório para requerimentos solicitados pelo aluno.
Quando o aluno possui históricos fragmentados, o frontend deve orientar o aluno
a juntá-los em um único PDF antes do envio.

No schema, `historico_id` aceita nulo para viabilizar dois casos específicos:
rascunhos ainda em edição e aproveitamentos automáticos, que não possuem
histórico de aluno. Antes de um rascunho solicitado ser submetido para análise, o
domínio deve exigir que `historico_id` esteja preenchido.

## Tabela `disciplinas`

Uma disciplina sempre pertence a um aproveitamento. A coluna `role` define sua
função dentro do agregado:

- `requerida`: disciplina pretendida no currículo atual;
- `cursada`: disciplina já cursada e usada como evidência.

### Colunas

| Coluna | Descrição |
| --- | --- |
| `id` | Identificador da disciplina. |
| `aproveitamento_id` | Aproveitamento ao qual a disciplina pertence. Referencia `aproveitamentos.id`. |
| `role` | Papel da disciplina no agregado. Usa `app/Enums/DisciplinaRole.php`. |
| `verdis` | Versão da disciplina. |
| `coddis` | Código da disciplina. |
| `nomdis` | Nome da disciplina. |
| `credito_aula` | Quantidade de créditos-aula. Para disciplinas USP, vem do campo `creaul` do Replicado. |
| `credito_trabalho` | Quantidade de créditos-trabalho. Para disciplinas USP, vem do campo `cretrb` do Replicado. |
| `carga_horaria` | Carga horária da disciplina. |
| `ies` | Instituição em que a disciplina foi cursada, quando aplicável. |
| `sglund` | Sigla da unidade. |
| `disciplina_ativa` | Indica se a disciplina está ativa no catálogo. |
| `ano` | Ano em que a disciplina foi cursada. |
| `semestre` | Semestre em que a disciplina foi cursada. |
| `codtur` | Código da turma. |
| `frequencia` | Frequência obtida na disciplina cursada. |
| `nota` | Nota obtida na disciplina cursada. |
| `ementa_id` | Arquivo de ementa. Referencia `arquivos.id`. |
| `criado_por_id` | Usuário que criou o registro. Referencia `users.id`. |
| `alterado_por_id` | Usuário que alterou o registro. Referencia `users.id`. |
| `created_at` / `updated_at` | Timestamps padrão do Laravel. |

### Relações

- `aproveitamento()`: aproveitamento pai;
- `ementa()`: arquivo referenciado por `ementa_id`;
- scope `requeridas()`: restringe para `role = requerida`;
- scope `cursadas()`: restringe para `role = cursada`.

### Regras de uso

Um aproveitamento deve ter obrigatóriamente e exclusivamente uma disciplina requerida.

Um aproveitamento deve ter obrigatóriamente ao menos uma disciplina cursada, com um máximo de três

Disciplina externa exige ementa, disciplina USP não. A ementa pertence à disciplina cursada, não ao aproveitamento como um todo.

Para disciplinas USP, `credito_aula` e `credito_trabalho` são preenchidos automaticamente a partir do Replicado.
Para disciplinas externas, esses dois campos são opcionais e podem ser informados manualmente.

## Tabela `arquivos`

Arquivos não são agregados independentes do fluxo de aproveitamento. Eles apenas
armazenam `tipo`, `nome` e `path`.

A posse semântica do arquivo é dada pela tabela que o referencia:

### Colunas

| Coluna | Descrição |
| --- | --- |
| `id` | Identificador do arquivo. |
| `tipo` | Tipo lógico do arquivo, como histórico ou ementa. |
| `nome` | Nome original ou nome de exibição. |
| `path` | Caminho do arquivo armazenado. |
| `created_at` / `updated_at` | Timestamps padrão do Laravel. |

### Relações

O relacionamento é sempre definido a partir da tabela principal:

- histórico: `aproveitamentos.historico_id`;
- ementa: `disciplinas.ementa_id`.

## Fluxos da Aplicação

### Solicitação do aluno

O fluxo do aluno trabalha com `Aproveitamento` do tipo `solicitada`. Esse tipo
representa um pedido que ainda precisa passar por um processo de análise.

Durante a edição, o aproveitamento permanece em `rascunho`. O rascunho deve
conter, antes da submissão:

- uma disciplina requerida;
- até três disciplinas cursadas;
- um histórico escolar único;
- ementa para cada disciplina externa, quando exigido.

Ao submeter, o aproveitamento deixa de ser apenas rascunho e passa para o estado
de análise definido pelo fluxo da aplicação. A submissão não deve ocorrer sem
histórico.

Nesse fluxo, o cadastro do aproveitamento não significa que a equivalência já foi
concedida. Ele registra uma solicitação formal do aluno, que depende de avaliação
e pode ser deferida ou negada.

### Aproveitamento automático

O fluxo automático trabalha com `Aproveitamento` do tipo `automatica`. Esse tipo
não representa uma solicitação pendente do aluno: representa uma equivalência já
cadastrada pela secretaria/SVGrad como regra válida para um curso e habilitação.

Cada conjunto equivalente deve ser representado por um aproveitamento próprio,
com:

- uma disciplina requerida;
- uma ou mais disciplinas cursadas;
- contexto de curso e habilitação em `codcur` e `codhab`.

Por ser automático, esse aproveitamento não precisa percorrer estados como
rascunho, submissão, análise, deferimento ou indeferimento para ser concluído. Ao
ser cadastrado, ele já expressa que aquelas disciplinas cursadas são aceitas para
aproveitar a disciplina requerida naquele contexto.

A aplicação pode exibir esses registros agrupados visualmente por disciplina
requerida, mas cada conjunto persistido é um aproveitamento próprio.
