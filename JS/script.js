import { postData, resultado } from './utils.js';

const formulario = document.getElementById('meuFormulario');

formulario.addEventListener('submit', async function(event) {
    event.preventDefault();

    const dados = {
        nome: document.getElementById('nome').value,
        email: document.getElementById('email').value,
        senha: document.getElementById('senha').value
    };

    alert(await resultado('php/back-end.php', dados, formulario));
});

