## Modelo atual

## Equivalencia

id
grupo
estado
requerida_id
cursada_id
placeholder_requerida
tipo
codcur
codhab
ano
semestre
codtur
frequencia
nota
numero_reuniao
data_reuniao
observacoes

## Disciplina

id
coddis (código da disciplina)
nomdis (nome)
verdis
creditos
carga_horaria
ies (instituição)
sglund (unidade)
disciplina_ativa

## Proposta

## aproveitamentos

id
estado // rascunho, processando, deferido, negado: deferido e negado são estados finais
tipo // automatica, requerida
codcur // curso associado: se requerida é o curso do aluno
codhab // habilitação associada

numero_reuniao // reuniao que foi aprovada a equivalencia
data_reuniao
observacoes

created_by (FK users) // aluno que solicitou ou servidor que cadastrou eq. automática
created_at
updated_at

arquivo de historico

## disciplinas

id
coddis
nomdis
verdis
ies // usp, unicep, ufscar, etc

cretot // creditos-total (teo+pra)
cgahortot // carga-horaria-total
sglund // sigla unidade

nota // nota do aluno que cursou
frequencia // frequencia do aluno que cursou
ano // ano que cursou
semestre // semestre que cursou

role // requerida | cursada
equivalencia_id (FK)
created_at
updated_at

falta ementa

// a ser tratado na biblioteca de workflow
workflow_definition
id
version

workflow_submission
model
model_id
definition_id
estado
?????

workflow_history
