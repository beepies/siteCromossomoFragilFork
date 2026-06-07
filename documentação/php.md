# Documentação do Sistema: Triagem Médica

Este documento contém o guia completo de arquitetura, segurança e funcionamento do front-end e back-end do sistema.

## 1. Arquitetura do Sistema
O sistema é uma aplicação *Multi-page* onde:
- **Front-end:** Hospedado no GitHub Pages (estático).
- **Back-end:** API RESTful em PHP/MySQL hospedada no Azure.
- **Comunicação:** O front-end envia requisições `fetch` com o token de autenticação no Header `Authorization`.

---

## 2. Padrão Front-end (`js`)

### `utils.js` (Core)
Centraliza toda a comunicação com a API.
- **`postData(url, data)`**: Envia dados via POST, injetando automaticamente o token do `localStorage` no cabeçalho.
- **`resultado(url, data, elementoForm)`**: Wrapper para submissão de formulários com limpeza automática após sucesso.
- **`verificarSessao()`**: Valida a existência do token no carregamento de páginas restritas. Redireciona para `cadastro_medico.html` se inválido.

### Fluxo de Login
1. Ao logar, o sistema salva o token recebido no `localStorage`.
2. Em seguida, chama o endpoint de validação/perfil para carregar dados do profissional.
3. Os dados públicos do profissional são salvos no `sessionStorage` para exibição rápida na interface (nome, especialidade).

---

## 3. Padrão Back-end (`php`)

Todos os arquivos de endpoint na pasta `/php` devem seguir a estrutura padrão de inicialização e tratamento de dados:

```php
require_once 'helpers.php';

// 1. Garante que qualquer erro/exceção retorne como JSON
configurarErrorHandlers();

// 2. Inicializa ambiente e conexão
$env = carregarEnv();
$conn = conectarBanco($env);

// 3. Recebe os dados da requisição (se houver POST/JSON)
$dados = receberDados();

// ... Execução da lógica de negócio (CRUD com prepared statements) ...

// 4. Retorno padronizado
responder('sucesso', 'Operação realizada com sucesso');
```

### Arquivo Core: `helpers.php`
Centraliza as funções auxiliares para manter o código dentro do princípio **DRY (Don't Repeat Yourself)**.

#### **Configuração e Inicialização**
* **`configurarErrorHandlers()`**: Captura erros, exceções e falhas fatais de desligamento (*shutdown*), limpando o buffer e devolvendo um JSON padronizado com o erro. Evita que códigos HTML de erro quebrem o front-end.
* **`carregarEnv()`**: Lê o arquivo `.env` do projeto, ignora comentários e retorna um array associativo com las credenciais.
* **`conectarBanco($env)`**: Instancia a conexão `mysqli` utilizando as variáveis de ambiente. Já define o header de resposta como `application/json`.
* **`receberDados()`**: Captura o fluxo `php://input` e decodifica o JSON enviado pelo front-end. Interrompe a execução se o JSON for inválido.
* **`responder($status, $mensagem)`**: Limpa o buffer de saída (`ob_end_clean`), define o cabeçalho JSON e envia a resposta final, encerrando o script (`exit`).

#### **Validações Úteis**
* **`validarCamposObrigatorios($campos, $obrigatorios)`**: Verifica de forma automatizada se uma lista de campos chave está preenchida no array de dados enviado.
* **`validarEmail($email)`**: Validação nativa de formato de e-mail.
* **`validarCPF($cpf)`**: Valida se o comprimento do CPF limpo (apenas números) possui 11 dígitos.

#### **Queries Comuns e Banco de Dados**
* **`tratarErroUniqueConstraint($conn, $mapeoCampos)`**: Simplifica o tratamento do erro `1062` (valores duplicados) do MySQL. Mapeia a coluna do banco para uma mensagem amigável ao usuário.
* **`buscarPorCampo($conn, $tabela, $campoRetorno, $campoBusca, $valor)`**: Abstrai uma busca simples (`SELECT`) utilizando *Prepared Statements* e retorna diretamente o valor da coluna desejada (ou `null`).
* **`contarRegistros($conn, $tabela, $campo, $valor)`**: Retorna a quantidade total de registros (`COUNT(*)`) baseada em uma condição inteira.

---

## 4. Regras de Ouro
1. **Segurança**: Nunca confie em dados passados diretamente pelo JS no corpo da requisição sem validação. Sempre valide a expiração e existência do token enviado no Header `Authorization`.
2. **SQL Injection**: É terminantemente obrigatório utilizar `prepare()` e `bind_param()` em qualquer query que aceite parâmetros externos.
3. **Tratamento de Erros**: Nunca deixe o PHP expor caminhos de arquivos em produção. Utilize sempre o `configurarErrorHandlers()` no início de cada endpoint.
4. **Armazenamento**:
   - `localStorage`: Tokens de sessão e persistência de login.
   - `sessionStorage`: Dados de perfil para exibição imediata na UI (ex: Nome no menu).
   - **Nunca salvar senhas** no navegador.

---

## 5. Checklist para Nova Funcionalidade
- [ ] Criar arquivo HTML na estrutura do Front-end.
- [ ] Importar `utils.js` e chamar `verificarSessao()` se a página for restrita.
- [ ] Criar o arquivo do endpoint na pasta `/php`.
- [ ] Importar o `helpers.php` no topo do arquivo PHP.
- [ ] Chamar `configurarErrorHandlers()`, `carregarEnv()` e `conectarBanco($env)`.
- [ ] Recuperar e validar o token do cabeçalho de requisição (`getallheaders()['Authorization']`).
- [ ] Executar a lógica de banco utilizando as funções utilitárias ou *Prepared Statements* nativos.
- [ ] Finalizar a execução sempre utilizando a função `responder()`.