//pega o formulário pelo ID
const formulario = document.getElementById('meuFormulario');

//adiciona um ouvinte de evento para o envio do formulário
formulario.addEventListener('submit', function(event) {
    // Evita que a página recarregue
    event.preventDefault();

//recebe os valores dos campos do formulário
const dados = {
        nome: document.getElementById('nome').value,
        email: document.getElementById('email').value,
        senha: document.getElementById('senha').value
    };


    fetch('back-end.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados) // Transforma o objeto JS em texto JSON
    })
    .then(resposta => {
        if (!resposta.ok) throw new Error('Erro na rede');
        return resposta.json();
    })
    .then(resultado => {
        alert(resultado.mensagem); 
        if(resultado.status == 'sucesso') {
            formulario.reset();
        }
    })
    .catch(erro => console.error('Erro ao enviar:', erro));
});