# Documentação Técnica: Sistema Front-end

Este documento descreve a arquitetura e o funcionamento dos scripts do sistema de triagem médica. O sistema utiliza uma arquitetura *Multi-page* com comunicação centralizada via `utils.js`.



## 1. Módulo de Comunicação (`utils.js`)

Este arquivo contém a lógica central de comunicação com a API (PHP). Ele padroniza o envio de dados e a autenticação.

### Funções Principais:

* **`postData(url, data)`**: 
    * **Finalidade**: Realiza requisições `POST` automatizadas.
    * **Segurança**: Verifica automaticamente se existe um `token_acesso` no `localStorage` e o insere no Header `Authorization`.
    * **Uso**: Deve ser usada para salvar, editar ou buscar dados protegidos.

* **`resultado(url, data, elementoForm)`**:
    * **Finalidade**: Wrapper para `postData` focada em submissão de formulários.
    * **Funcionalidade**: Se a resposta for `'sucesso'`, ela limpa automaticamente o formulário (`elementoForm.reset()`).

* **`verificarSessao()`**:
    * **Finalidade**: Guarda do sistema. Deve ser chamada no `DOMContentLoaded` de todas as páginas restritas.
    * **Ação**: Se o token for inválido ou inexistente, redireciona para `cadastro_medico.html`.

## 2. Fluxos de Trabalho

### Fluxo de Login
1. O usuário submete o formulário no `cadastro_medico.html`.
2. O sistema chama `resultado()` para enviar as credenciais para `login.php`.
3. Ao receber sucesso e o token:
    * Salva o token em `localStorage.setItem('token_acesso', ...)`.
    * Busca o perfil do médico (`obter_perfil.php`).
    * Salva os dados do médico em `sessionStorage.setItem('usuario', ...)` para acesso rápido na UI.
4. Redireciona para `quiz.html`.

### Fluxo do Quiz
1. O quiz gerencia seu estado internamente via `questions`, `perguntaAtual` e `answers`.
2. A UI é reativa: funções como `updateProgressUI()` atualizam o HTML dinamicamente sem recarregar a página.
3. Ao finalizar, os dados são enviados via `postData("php/quiz.php", ...)` para o servidor.

## 3. Armazenamento de Dados (Cache vs Persistência)

| Local | Onde usar | Quando limpar |
| :--- | :--- | :--- |
| **localStorage** | `token_acesso` (Autenticação) | Nunca (até o logout/expiração) |
| **sessionStorage** | Dados do Médico (nome, CRM) | Ao fechar a aba do navegador |

> **Dica**: Sempre que precisar do nome do médico na tela, leia do `sessionStorage`. Isso evita requisições desnecessárias ao servidor.

## 4. Como adicionar uma nova página restrita

Sempre que criar um novo arquivo HTML, siga estes passos para garantir que ele esteja protegido:

1. Importe o `verificarSessao` no seu arquivo JS.
2. Adicione a verificação no carregamento da página:

```javascript
import { verificarSessao } from "./utils.js";

document.addEventListener("DOMContentLoaded", async () => {
    const logado = await verificarSessao();
    if (logado) {
        // Inicialize sua lógica aqui
    }
});