// Função universal para requisições POST (substitui todos os seus fetch repetidos)
export async function postData(url = '', data = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    if (!response.ok) {
        throw new Error(`Erro na requisição: ${response.status}`);
    }
    
    return response.json();
}

export async function resultado(url = '', data = {}, elementoForm = null) {
    try {
        const resposta = await postData(url, data);
        
        if (resposta.status === 'sucesso' && elementoForm) {
            elementoForm.reset();
        }

        return resposta.mensagem; 

    } catch (erro) {
        console.error('Erro ao enviar:', erro);
        return "Erro na comunicação com o servidor.";
    }
}