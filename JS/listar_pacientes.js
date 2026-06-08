import { verificarSessao, getData } from './utils.js';
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
            const itensPorPagina = 10;
let paginaAtual = 1;

function renderPagina(lista) {
                    corpoTabela.innerHTML = '';
                    const inicio = (paginaAtual - 1) * itensPorPagina;
                    const fim = inicio + itensPorPagina;
                    const paginados = lista.slice(inicio, fim);

                    paginados.forEach(paciente => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${paciente.numero_inscricao}</td>
                            <td>${paciente.nome_completo}</td>
                            <td>${paciente.cpf}</td>
                            <td>${paciente.data_nascimento}</td>
                            <td>${paciente.telefone || 'Não informado'}</td>
                            <td>
                                ${paciente.responsavel_nome ? `
                                    <span style="font-size:0.82rem;color:#64748b;">${paciente.responsavel_nome} (${paciente.responsavel_parentesco || '-'})</span><br>
                                    <span id="contato-${paciente.numero_inscricao}-${paciente.responsavel_id}" style="display:none;font-size:0.8rem;color:#10b981;">
                                        ${paciente.responsavel_telefone || ''} ${paciente.responsavel_email ? `<a href="mailto:${paciente.responsavel_email}" style="color:var(--primaria);">${paciente.responsavel_email}</a>` : ''}
                                    </span>
                                    <a href="#" onclick="document.getElementById('contato-${paciente.numero_inscricao}-${paciente.responsavel_id}').style.display='inline';this.style.display='none';return false;" style="font-size:0.78rem;">ver contato</a>
                                ` : '-'}
                            </td>
                            <td>${paciente.medico_responsavel || '-'}</td>
                            <td><a href="editar_paciente.html?inscricao=${paciente.numero_inscricao}">Editar</a></td>
                        `;
                        corpoTabela.appendChild(tr);   });
                     renderPaginacao(lista.length); }

                function renderPaginacao(total) {
                    const totalPaginas = Math.ceil(total / itensPorPagina);
                    let paginacaoEl = document.getElementById('paginacao');
                    if (!paginacaoEl) {
                        paginacaoEl = document.createElement('div');
                        paginacaoEl.id = 'paginacao';
                        corpoTabela.closest('table').after(paginacaoEl); }
                    paginacaoEl.innerHTML = '';
                    for (let i = 1; i <= totalPaginas; i++) {
                        const btn = document.createElement('button');
                        btn.textContent = i;
                        btn.className = `btn-pagina${i === paginaAtual ? ' ativo' : ''}`;
                        btn.onclick = () => { paginaAtual = i; renderPagina(pacientes); };
                        paginacaoEl.appendChild(btn); } }
                document.getElementById('buscarPaciente').addEventListener('input', function() {
                    const termo = this.value.toLowerCase();
                    const filtrados = pacientes.filter(p => 
                        JSON.stringify(p).toLowerCase().includes(termo)
                    );
                    paginaAtual = 1;
                    renderPagina(filtrados);
                });
                renderPagina(pacientes);
        } else {
            divMensagem.innerHTML = `<p class="mensagem-vazia">${dados.mensagem}</p>`;}

    } catch (erro) {
        console.error("Erro ao buscar pacientes:", erro);
        divMensagem.innerHTML = '<p class="mensagem-vazia">Erro de conexão com o servidor.</p>';
    }
});