# Sistema de Cadastro e Login - Profissionais de Saúde

Sistema web para cadastro e autenticação de profissionais de saúde.

## Configuração Local

### Pré-requisitos
- PHP 7.4+
- MySQL
- Composer
- XAMPP ou similar

### Passos de Setup

1. **Clone o projeto**
   ```bash
   git clone <seu-repositorio>
   cd Site
   ```
2. **Instale o Composer**
   Se o comando `composer` não for reconhecido no seu terminal, siga estes passos para instalar e usar localmente no projeto:
   
   *   **Baixe o instalador:**
   ```bash
   php -r "copy('[https://getcomposer.org/installer](https://getcomposer.org/installer)', 'composer-setup.php');"
   php composer-setup.php
   php -r "unlink('composer-setup.php');"
   ```

3. **Instale as dependências**
   ```bash
   php composer.phar install
   ```

4. **Configure o arquivo .env**
   ```bash
   cp .env.example .env
   ```
   
   Edite o `.env` com suas credenciais:
   ```env
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=sua_senha
   DB_NAME=seu_banco_de_dados
   ```


5. **Acesse a aplicação**
   ```
   http://localhost/Site/cadastro_medico.html    (Cadastro)
   http://localhost/Site/login.html    (Login)
   ```

## Estrutura do Projeto

```
├── cadastro_medico.html           # Página de cadastro
├── login.html           # Página de login
├── JS/
│   ├── cadastro.js     # Lógica do formulário de cadastro
│   ├── login.js        # Lógica do formulário de login
│   └── utils.js        # Funções reutilizáveis (requisições)
├── php/
│   ├── config.php      # Configuração e conexão com BD
│   ├── cadastro.php    # API de cadastro
│   └── login.php       # API de login
├── vendor/             # Dependências (Composer)
├── .env.example        # Template de variáveis de ambiente
└── composer.json       # Gerenciador de dependências
```

## Segurança

- Senhas armazenadas com hash bcrypt (PASSWORD_BCRYPT)
- Prepared statements para prevenir SQL Injection
- Variáveis sensíveis em `.env` (não commitadas)
- Tratamento de exceções

## Notas

- O arquivo `.env` é ignorado pelo Git (contém credenciais)
- Sempre use `.env.example` como template
- Nunca commite o `.env` com dados reais

## Contribuindo

1. Crie uma branch para sua feature
2. Faça commit das mudanças
3. Push para a branch
4. Abra um Pull Request
