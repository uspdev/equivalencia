# Contexto do Equivalencia

Este glossário registra os termos usados para separar dados de negócio preserváveis de configurações dos módulos Forms e Workflow.

## Dados e configurações

**Dados operacionais**:
Registros produzidos pelo uso real do sistema, como submissões de formulários e execuções de workflows.
_Avoid_: definições, seeds, configurações

**Definição de formulário**:
Configuração que descreve um formulário disponível no sistema; não representa o preenchimento ou envio feito por uma pessoa.
_Avoid_: submissão, dado operacional

**Definição de workflow**:
Configuração que descreve um processo e suas transições; não representa uma execução desse processo.
_Avoid_: execução, histórico

**Execução de workflow**:
Processo iniciado para um registro de negócio específico; possui estado atual, transições aplicadas e histórico próprio.
_Avoid_: definição de workflow

**Objeto de workflow**:
Registro persistido que vincula uma execução de workflow ao objeto de negócio que ela acompanha.
_Avoid_: definição, configuração

**Transição de workflow**:
Ação autorizada que move uma execução de um ou mais estados atuais para um ou mais estados de destino; pode exigir dados de um formulário.
_Avoid_: mudança direta de coluna, definição de formulário

**Log de atividade**:
Registro de auditoria associado às operações dos módulos Forms e Workflow. No estado atual, não há outro consumidor identificado no Equivalencia.
_Avoid_: dado de equivalência, log de aproveitamento
