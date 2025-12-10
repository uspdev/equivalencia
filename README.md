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
    git clone https://github.com/uspdev/equivalencia.git

### 2. **Instalar as dependências através do composer:**
- Instala as dependências:
```bash
composer install
```

- Atualiza-as para a versão mais recente disponível:
```bash
composer update
```

### 3. **Gerar arquivo env baseado no exemplo**
    cp .env.example .env

#### 3.1 **Configurar .env**
- 'APP_NAME' => Nome de exibição da aplicação

- 'APP_URL' => Link do servidor de hosting da aplicação

- 'DB_HOST' => Host do banco de dados

- 'DB_DATABASE' => Nome do banco de dados a ser utilizado

- 'DB_USERNAME' => Nome de usuário do banco de dados

- 'DB_PASSWORD' => Senha de acesso ao banco de dados

- 'SENHAUNICA_DEV' => Link para o host do senhaunica, utilizado para autenticação

### 5. **Gerar chave da aplicação**
    php artisan key:generate

### 6. **Rodando as migrations**
Após a configuração do ambiente e geração da chave da aplicação, utilize o comando a seguir para criar as tabelas necessárias no DB:

```bash
php artisan migrate
```
### 5. **Populando o banco de dados**
    php artisan db:seed

## Contributing

Contributions are welcome! Please follow these steps to contribute:

Fork the repository.
Create a new branch (git checkout -b feature/YourFeature).
Make your changes and commit them (git commit -m 'Add some feature').
Push to the branch (git push origin feature/YourFeature).
Create a new Pull Request.

## License

This package is licensed under the MIT License. See the LICENSE file for details.