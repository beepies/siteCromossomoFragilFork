import { postData, resultado } from './utils.js';

const formulario = document.getElementById('meuFormulario');

formulario.addEventListener('submit', async function(event) {
    event.preventDefault();

    const dados = {
        email: document.getElementById('email').value,
        senha: document.getElementById('senha').value
    };

    alert(await resultado('php/login.php', dados, formulario));
});

