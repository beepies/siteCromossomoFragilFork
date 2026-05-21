import { postData, resultado } from './utils.js';

const formulario = document.getElementById('meuFormulario');

formulario.addEventListener('submit', async function(event) {
    event.preventDefault();

    const dados = {
        nome: document.getElementById('nome').value,
        Registro_profissional: document.getElementById('Registro_profissional').value,
        Especialidade: document.getElementById('Especialidade').value,
        email: document.getElementById('email').value,
        telefone: document.getElementById('telefone').value,
        Instituicao: document.getElementById('Instituicao').value,
        senha: document.getElementById('senha').value
    };

    alert(await resultado('php/cadastro.php', dados, formulario));
});

