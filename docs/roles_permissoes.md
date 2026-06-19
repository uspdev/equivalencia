# Roles e permissões

Este documento descreve as roles e permissões atualmente usadas pela aplicação.

O sistema usa o pacote Spatie Permission e trabalha com dois conjuntos de permissões:

- `web`: roles e permissões próprias da aplicação.
- `senhaunica`: permissões atribuídas pela integração com Senha Única, incluindo permissões hierárquicas e vínculos institucionais.

## Roles da aplicação

As roles abaixo usam o guard `web`.

| Role | Nome funcional | O que permite |
| --- | --- | --- |
| `admin` | Administrador | Acesso administrativo total. O método `User::isAdmin()` e o `Gate::before` tratam esta role como override geral da aplicação. |
| `aluno` | Aluno | Criar novo requerimento, visualizar os próprios requerimentos e participar das etapas do workflow destinadas ao aluno. |
| `svgrad` | Serviço de Graduação | Participar das etapas do workflow destinadas ao Serviço de Graduação e visualizar as planilhas de aproveitamento automático. Não pode editar essas planilhas. |
| `cg` | Comissão de Graduação | Visualizar e gerenciar as planilhas de aproveitamento automático. |
| `depto` | Departamento | Participar das etapas do workflow destinadas ao departamento. |
| `docente` | Docente | Participar das etapas do workflow destinadas ao docente. |
| `conselho` | Conselho do Departamento | Participar da etapa de deliberação do conselho no workflow. |
| `coc` | CoC | Participar da etapa de deliberação da CoC no workflow. |

## Permissões de negócio

As permissões abaixo usam o guard `web` e estão declaradas em `App\Enums\Permission`.

| Permissão | O que permite |
| --- | --- |
| `requerimentos.create` | Acessar o fluxo de criação de novo requerimento de equivalência/aproveitamento. |
| `requerimentos.view-own` | Acessar a listagem, detalhes e arquivos dos próprios requerimentos. |
| `aproveitamentos-automaticos.view` | Visualizar a área de aproveitamentos automáticos. |
| `aproveitamentos-automaticos.manage` | Criar, alterar, excluir e alternar estados de edição das planilhas de aproveitamento automático. |

## Permissões do workflow

As permissões abaixo usam o guard `web` e representam places/etapas do workflow de equivalência. Elas são criadas em `WorkflowDefinitionsTableSeeder`.

| Permissão | Role vinculada | O que representa |
| --- | --- | --- |
| `aluno_inicio` | `aluno` | Formulário de solicitação inicial. |
| `svgrad_conferencia` | `svgrad` | Conferência de documentos pelo Serviço de Graduação. |
| `depto_indica_docente` | `depto` | Indicação de docente para emitir parecer. |
| `docente_emite_parecer` | `docente` | Emissão de parecer sobre equivalência de disciplina. |
| `depto_envia_apreciacao` | `depto` | Envio de parecer para apreciação do conselho. |
| `conselho_delibera` | `conselho` | Deliberação sobre os pareceres emitidos. |
| `depto_retorno_apreciacao` | `depto` | Envio de apreciação para a CoC. |
| `svgrad_apreciacao_coc` | `svgrad` | Envio de apreciação para a CoC pelo Serviço de Graduação. |
| `coc_delibera_solicitacao` | `coc` | Deliberação da CoC sobre a solicitação. |
| `svgrad_recebe_deliberacao` | `svgrad` | Recebimento da deliberação pelo Serviço de Graduação. |
| `svgrad_cadastra_aprovadas` | `svgrad` | Cadastro de solicitações aprovadas. |
| `aluno_preavaliacao` | `aluno` | Etapa em que o aluno precisa realizar avaliação. |
| `aluno_avaliacao` | `aluno` | Avaliação realizada pelo aluno. |
| `docente_avaliacao` | `docente` | Elaboração de avaliação pelo docente. |
| `docente_delibera` | `docente` | Deliberação do docente sobre a avaliação. |
| `svgrad_finaliza` | `svgrad` | Finalização pelo Serviço de Graduação. |
| `aluno_fim` | `aluno` | Confirmação de finalização pelo aluno. |

O middleware `SyncAlunoRoleFromSenhaunicaPermissions` sincroniza automaticamente a role `aluno` para usuários que tenham permissões de aluno no guard `senhaunica`, incluindo permissões com sufixo, como `Alunogr.<curso>`.

## Matriz de permissões por role

| Role | Permissões |
| --- | --- |
| `admin` | Override administrativo total via `Gate::before`. Não depende de uma lista explícita de permissões de negócio. |
| `aluno` | `requerimentos.create`, `requerimentos.view-own`, `aluno_inicio`, `aluno_preavaliacao`, `aluno_avaliacao`, `aluno_fim` |
| `svgrad` | `aproveitamentos-automaticos.view`, `svgrad_conferencia`, `svgrad_apreciacao_coc`, `svgrad_recebe_deliberacao`, `svgrad_cadastra_aprovadas`, `svgrad_finaliza` |
| `cg` | `aproveitamentos-automaticos.view`, `aproveitamentos-automaticos.manage` |
| `depto` | `depto_indica_docente`, `depto_envia_apreciacao`, `depto_retorno_apreciacao` |
| `docente` | `docente_emite_parecer`, `docente_avaliacao`, `docente_delibera` |
| `conselho` | `conselho_delibera` |
| `coc` | `coc_delibera_solicitacao` |

## Pontos de manutenção

- Novas permissões de negócio devem ser adicionadas em `App\Enums\Permission` e semeadas em `RolesAndPermissionsSeeder`.
- Novas roles de negócio devem ser adicionadas em `App\Enums\Role` quando forem usadas diretamente pelo código.
- Alterações nas etapas do workflow devem ser refletidas em `WorkflowDefinitionsTableSeeder`.
- Menus, rotas, controllers, FormRequests e views devem usar as permissões de negócio, não nomes de role, sempre que a intenção for controlar uma capacidade funcional.
