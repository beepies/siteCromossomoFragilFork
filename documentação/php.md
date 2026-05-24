# Documentação do Sistema: Triagem Médica

Este documento contém o guia completo de arquitetura, segurança e funcionamento do front-end e back-end do sistema.

## 1. Arquitetura do Sistema
O sistema é uma aplicação *Multi-page* onde:
- **Front-end:** Hospedado no GitHub Pages (estático).
- **Back-end:** API RESTful em PHP/MySQL hospedada no Azure.
- **Comunicação:** O front-end envia requisições `fetch` com o token de autenticação no Header `Authorization`.

## 2. Padrão Front-end (`js`)

### `utils.js` (Core)
Centraliza toda a comunicação.
- **`postData(url, data)`**: Envia dados via POST, injetando automaticamente o token do `localStorage` no cabeçalho.
- **`resultado(url, data, elementoForm)`**: Wrapper para submissão de formulários com limpeza automática após sucesso.
- **`verificarSessao()`**: Valida a existência do token no carregamento de páginas restritas. Redireciona para `index.html` se inválido.

### Fluxo de Login
1. Ao logar, o sistema salva o `token_acesso` no `localStorage`.
2. Em seguida, chama `obter_perfil.php` para carregar dados do médico.
3. Os dados do médico são salvos no `sessionStorage` para exibição rápida na interface (nome, CRM).

## 3. Padrão Back-end (`php`)

Todos os arquivos na pasta `/php` seguem a estrutura:
1. `require_once 'config.php';`
2. Validação de autenticação via `validarTokenEObterUsuario($conn);`
3. Execução da lógica (CRUD com `prepared statements`).
4. Retorno de resposta via `responder($status, $mensagem, $dados);`.

### Helpers do `config.php`
* **`receberDados()`**: Lê e decodifica `php://input`.
* **`responder()`**: Padroniza a resposta JSON e encerra a execução.
* **`validarTokenEObterUsuario()`**: A "chave de ouro". Valida o token enviado no Header e retorna os dados do usuário, eliminando a necessidade de buscar ID pelo JS.

## 4. Regras de Ouro
1. **Segurança**: Nunca confie em dados passados pelo JS. O ID do profissional deve vir sempre do token validado no PHP.
2. **SQL Injection**: Sempre utilize `prepare()` e `bind_param()`.
3. **Armazenamento**:
   - `localStorage`: Tokens de sessão.
   - `sessionStorage`: Dados de perfil para exibição na UI.
   - **Não salvar senhas** em nenhum lugar.

## 5. Checklist para Nova Funcionalidade
- [ ] Criar arquivo HTML.
- [ ] Importar `utils.js` e `verificarSessao()`.
- [ ] Criar arquivo PHP na pasta `/php`.
- [ ] Incluir `config.php` e `validarTokenEObterUsuario()` no PHP.
- [ ] Implementar a query com `bind_param()`.