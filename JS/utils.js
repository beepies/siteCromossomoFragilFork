// Função universal para requisições POST (substitui todos os seus fetch repetidos)
export async function postData(url = '', data = {}) {
    // Tenta pegar o token do localStorage
    const token = localStorage.getItem('token_acesso');
    
    // Configura os headers
    const headers = { 'Content-Type': 'application/json' };
    if (token) {
        headers['Authorization'] = token; // Adiciona o token se ele existir
    }

    const response = await fetch(url, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(data)
    });

    if (!response.ok) {
        throw new Error(`Erro na requisição: ${response.status}`);
    }
    
    return response.json();
}

//Função para retornar a resposta do servidor, e limpar o formulário caso haja sucesso
export async function resultado(url = '', data = {}, elementoForm = null) {
    try {
        const resposta = await postData(url, data);
        
        if (resposta.status === 'sucesso' && elementoForm) {
            elementoForm.reset();
        }

        return resposta; 

    } catch (erro) {
        console.error('Erro ao enviar:', erro);
        return "Erro na comunicação com o servidor.";
    }
}
//Função que carrega dados de um json 
export async function getDados(url = '') {
    const response = await fetch(url, { cache: 'no-cache' });
    if (!response.ok) {
        throw new Error(`Erro HTTP ${response.status}`);
    }
    return response.json();
}

//função para verificar se o usuário tem sessão ativa, caso contrário redireciona para a página de login
export async function verificarSessao() {
    const token = localStorage.getItem('token_acesso');

    if (!token) {
        alert("Sessão expirada ou inválida. Faça login novamente.");
        window.location.href = 'cadastro_medico.html'; // Usuário não logado
        return false;
    }

    try {
        const response = await fetch('php/verificar_login.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': token 
            }
        });
        
        const data = await response.json();

        if (data.status !== 'sucesso') {
            alert("Sessão expirada ou inválida. Faça login novamente.");
            localStorage.removeItem('token_acesso');
            window.location.href = 'cadastro_medico.html';
            return false;
        }
        
        return true; // Sessão válida
    } catch (error) {

        alert("Erro ao verificar sessão:", error);
        localStorage.removeItem('token_acesso');
        window.location.href = 'cadastro_medico.html';
        return false;
    }
    
}

// Função universal para requisições GET (Adicione no seu utils.js)
export async function getData(url = '') {
    const token = localStorage.getItem('token_acesso');
    
    const headers = { 'Content-Type': 'application/json' };
    if (token) {
        headers['Authorization'] = token; // Injeta o token se ele existir
    }

    const response = await fetch(url, {
        method: 'GET',
        headers: headers
    });

    if (!response.ok) {
        throw new Error(`Erro na requisição: ${response.status}`);
    }
    
    return response.json();
}