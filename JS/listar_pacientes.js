// Importa as funções que você já criou e a nova getData
import { verificarSessao, getData } from './utils.js';

// Executa a sua trava de segurança
verificarSessao();

document.addEventListener('DOMContentLoaded', async () => {
    const corpoTabela = document.getElementById('corpoTabelaPacientes');
    const divMensagem = document.getElementById('mensagemStatus');

    try {
        // DRY em ação: O seu utils.js resolve os headers e o token sozinho aqui!
        const dados = await getData('php/listar_pacientes.php');

        if (dados.status === 'sucesso') {
            const pacientes = dados.dados;

            if (pacientes.length === 0) {
                divMensagem.innerHTML = '<p class="mensagem-vazia">Você ainda não possui pacientes sob sua responsabilidade.</p>';
                return;
            }

            // Limpa o corpo da tabela e renderiza as linhas
            corpoTabela.innerHTML = '';
            pacientes.forEach(paciente => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${paciente.numero_inscricao}</td>
                    <td>${paciente.nome_completo}</td>
                    <td>${paciente.cpf}</td>
                    <td>${paciente.telefone || 'Não informado'}</td>
                    <td>
                        <a href="editar_paciente.html?inscricao=${paciente.numero_inscricao}">Editar</a>
                    </td>
                `;
                corpoTabela.appendChild(tr);
            });

        } else {
            divMensagem.innerHTML = `<p class="mensagem-vazia">${dados.mensagem}</p>`;
        }

    } catch (erro) {
        console.error("Erro ao buscar pacientes:", erro);
        divMensagem.innerHTML = '<p class="mensagem-vazia">Erro de conexão com o servidor.</p>';
    }
});