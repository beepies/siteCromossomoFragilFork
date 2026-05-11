import {postData} from './utils.js';
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


    try{
        const resultados = /*adiconar await*/ postData('back-end.php', dados);
    alert(resultado.mensagem); 
        
        if(resultado.status === 'sucesso') {
            formulario.reset();
        }
    } catch (erro) {
        console.error('Erro ao enviar:', erro);
        alert("Erro na comunicação com o servidor.");
    }
});