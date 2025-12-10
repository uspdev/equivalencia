# Equivalência

O projeto Equivalência tem como objetivo informatizar e padronizar o processo de requerimentos de equivalência de disciplinas.
Tem como pilares principais as bibliotecas uspdev/workflow e uspdev/forms, que foram construídas com foco na generalização.
Logo, o projeto Equivalência pode ser utilizado e adaptado em outros contextos, com vários tipos de requerimentos.

## Features

- Gerenciamento completo de workflows com definições armazenadas em banco de dados;
- Suporte à biblioteca Symfony Workflow para transições e estados;
- Integração com Laravel 11 em diante;
- Representação visual de workflows.

## Requisitos

- PHP 8.2 ou maior;
- Composer;

## Instalação e Configuração

### 1. **Clonar o repositório:**
    git clone git@github.com/uspdev/equivalencia

### 2. **Instalar as dependências através do composer:**
    composer install

### 3. **Gerar arquivo env baseado no exemplo**
    cp .env.example .env

#### 3.1 **Configurar .env**
- 'APP_NAME' => Nome de exibição da aplicação

- 'APP_URL' => Link do servidor de hosting da aplicação

- 'DB_HOST' => Host do banco de dados

- 'DB_DATABASE' => Nome do banco de dados a ser utilizado

- 'DB_USERNAME' => Nome de usuário do banco de dados

- 'DB_PASSWORD' => Senha de acesso ao banco de dados

- 'SENHAUNICA_DEV' => Link para o host do senhaunica, utilziado para autenticação

### 5. **Gerar chave da aplicação**
    php artisan key:generate

### 6. **Rodando as migrations**
Após a configuração do ambiente e geração da chave da aplicação, utilize o comando a seguir para criar as tabelas necessárias no DB:

```bash
php artisan migrate
```
### 5. **Populando o banco de dados**
    php artisan db:seed

## Usage

### 1. **Criando uma nova definição de workflow**

Utilize o método `Workflow::criarWorkflowDefinition` para criar uma nova definição na tabela `workflow_definitions`. Este método valida os dados enviados e os salva no banco de dados se estiverem corretos.

```php
use Illuminate\Http\Request;

$workflowData  = [
    'name' => 'simples', 
    'description' => 'Fluxo de workflow simples', 
    'definition' => json_encode([
        'type' => 'workflow',
        'title' => 'Workflow simples',
        'name' => 'simples',
        'description' => 'Workflow simples de teste',
        'places' => [
            'inicio' => [
                'description' => 'Formulário inicial',
                'forms' => 'textarea'
            ],
            'processamento1' => [
                'description' => 'Etapa 1 do processo',
                'forms' => 'textarea'
            ],
            'fim' => [
                'description' => 'Finalizado',
                'forms' => 'textarea',
            ]
        ],
        'initial_places' => 'inicio',
        'transitions' => [
            'tr_inicio_p1' => [
                'label' => 'Enviar solicitação',
                'from' => 'inicio',
                'to' => 'processamento1'
            ],
            'tr_p1_fim' => [
                'label' => 'Finalizar',
                'from' => 'processamento1',
                'to' => 'fim'
            ]
        ],
    ]);
];

$request = new Request($workflowData);

Workflow::criarWorkflowDefinition($request);
```

### 2. **Exibindo uma definição de workflows**

Para exibir uma definição de workflow em uma view, utilize o método `Workflow::obterDadosDaDefinicao`. Este método retorna os dados estruturados da definição, incluindo seu estado inicial, transições e caminho da representação visual.

```php
$workflowDefinitionData = Workflow::obterDadosDaDefinicao($definitionName);

$definitionName = $workflowDefinitionData['definitionName'];
$imagePath = $workflowDefinitionData['path'];
$formattedJson = $workflowDefinitionData['formattedJson'];
```

Com os dados retornados, você pode exibir o JSON formatado ou a imagem gerada do workflow na interface do usuário.

### 3. **Editando uma definição de workflow**

Utilize o método `Workflow::atualizarWorkflow` para editar uma definição. Este método também valida os dados enviados e os salva no banco de dados se estiverem corretos.

```php
public function update(Request $request)
{
    # Outras validações do update... 
    # As validações do workflow já estão integradas na biblioteca
    Workflow::atualizarWorkflow($request);
    # Outras lógicas do update...
}
```

### 4. **Apagando uma definição de workflow**

Para excluir uma definição, utilize o método `Workflow::deletarDefinicaodeWorkflow`.

```php
Workflow::deletarDefinicaodeWorkflow($definitionName);
```

### 5. **Listando as definições de workflows**

Para listar as definições de workflow, utilize o método `Workflow::obterTodosWorkflowDefinitions`. Este método retorna todas as definições de workflow presentes no banco de dados.

```php
$workflowDefinitions = Workflow::obterTodosWorkflowDefinitions();
```

Com os dados retornados, você pode exibir o JSON formatado ou a imagem gerada do workflow na interface do usuário.

### 6. **Criando um objeto de workflow**

Para instanciar um novo objeto de workflow baseado em uma definição existente, utilize o método `Workflow::criarWorkflowObject`. Ele inicializa o objeto no estado inicial definido pela configuração.

```php
$WorkflowObject = Workflow::criarWorkflowObject('pull_requests');
```

### 7. **Exibindo um objeto de workflow**

Para exibir um objeto de workflow em uma view, utilize o método `Workflow::obterDadosDoObjeto`. Este método retorna os dados estruturados do objeto, incluindo sua definição, transições, formulários, título, atividades e submissões de formulário.

```php
$workflowObjectData = Workflow::obterDadosDoObjeto($workflowObjectId);

$workflowObject = $workflowObjectData['workflowObject'];
$workflowDefinition = $workflowObjectData['workflowDefinition'];
$workflowsTransitions = $workflowObjectData['workflowsTransitions'];
$formHtml = $workflowObjectData['formHtml'];
$title = $workflowObjectData['title'];
$activities = $workflowObjectData['activities'];
$formSubmissions = $workflowObjectData['formSubmissions'];
```

### 8. **Exibindo todos os objetos de uma definição**

Para exibir todos os objetos de uma definição de workflow, utilize o método `Workflow::listarWorkflowsdaDefinition`. Este método retorna os objetos de workflow, as transições daquela definição e a própria definição

```php
$workflowsToDisplay = Workflow::listarWorkflowsdaDefinition($definitionName);

$workflowObjects = $workflowsToDisplay['workflows'];
$workflowTransitions = $workflowsToDisplay['workflowsTransitions'];
$workflowDefinition = $workflowsToDisplay['workflowDefinition'];
```

### 9. **Exibindo todos os objetos criados pelo usuário**

Para exibir todos os objetos criados pelo usuário, utilize o método `Workflow::listarWorkflowsdoUser`. É possível passar um id de usuário como parâmetro, mas se não for fornecido, será utilizado o id do usuário autenticado no sistema. O método retorna os objetos de workflow e os dados do objeto, incluindo estado do dele e sua definição de workflow

```php
$workflowsToDisplay = Workflow::listarWorkflowsdoUser($id);

$workflowObjects = $workflowsToDisplay['workflows'];
$workflowData = $workflowsToDisplay['workflowData'];
```

### 10. **Gerenciando transições de estado**

Utilize o método `Workflow::aplicarTransition` para mudar o estado de um objeto de workflow com base nas transições definidas.

```php
Workflow::aplicarTransition($workflowObjectId, 'tr_opened_in_review');
```

Esse método verifica se a transição é válida no estado atual antes de aplicá-la.

### 11. **Apagando um objeto de workflow**

Para excluir um objeto, utilize o método `Workflow::deletarWorkflow`.

```php
Workflow::deletarWorkflow($workflowObjectId);
```

### 12. **Enviando um formulário**

Para fazer a submissão de um formulário de um objeto de workflow, utilize o método `Workflow::enviarFormulario`

```php
Workflow::enviarFormulario($request);
```

## Contributing

Contributions are welcome! Please follow these steps to contribute:

Fork the repository.
Create a new branch (git checkout -b feature/YourFeature).
Make your changes and commit them (git commit -m 'Add some feature').
Push to the branch (git push origin feature/YourFeature).
Create a new Pull Request.

## License

This package is licensed under the MIT License. See the LICENSE file for details.