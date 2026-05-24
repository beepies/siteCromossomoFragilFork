import { postData, resultado } from './utils.js';

const formulario = document.getElementById('meuFormulario');

formulario.addEventListener('submit', async function (event) {
    event.preventDefault();

    const dados = {
        email: document.getElementById('email').value,
        senha: document.getElementById('senha').value
    };

    const resposta = await resultado('php/login.php', dados, formulario);

    if (resposta.status === "sucesso") {
        alert("Login realizado com sucesso!");
        localStorage.setItem('token_acesso', resposta.token);

        try {
            const resultado = await postData('php/obter_perfil.php', {});
            if (resultado.status === 'sucesso') {
                const usuarioLogado = resultado.dados;
                // Salva no sessionStorage para usar nas outras páginas
                sessionStorage.setItem('usuario', JSON.stringify(usuarioLogado));

            }
        } catch (e) {
            console.error("Erro ao carregar dados:", e);
        }
        window.location.href = 'quiz.html';

    }
    else {
        alert("Falha no login: " + resposta.mensagem);
    }
});

