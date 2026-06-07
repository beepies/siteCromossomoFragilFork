import { getData, postData } from './utils.js';

        const labels = { 0: 'Pendente', 1: 'Médico', 2: 'Admin' };
        const cores = {
            0: 'background:#f1f5f9;color:#64748b;',
            1: 'background:#dcfce7;color:#166534;',
            2: 'background:#dbeafe;color:#1e40af;'
        };

        async function carregarUsuarios() {
            const resposta = await getData('php/gerenciar_usuarios.php');
            if (resposta.status !== 'sucesso') {
                alert(resposta.mensagem);
                return;  }

            const tbody = document.getElementById('corpoTabelaUsuarios');
            tbody.innerHTML = '';
            resposta.dados.forEach(u => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${u.nome_completo}</td>
                  <td>${u.registro_profissional}</td>
                    <td>${u.especialidade}</td>
                    <td>${u.email || '-'}</td>
                    <td>
                        <span style="padding:3px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;${cores[u.nivel]}">
                            ${labels[u.nivel]}
                        </span>
                  </td>
                    <td>
                      ${parseInt(u.nivel) === 0 ? `<a href="#" onclick="alterarNivel(${u.id_profissional}, 1); return false;">Aprovar</a>` : ''}
                        ${parseInt(u.nivel) === 1 ? `<a href="#" onclick="alterarNivel(${u.id_profissional}, 2); return false;">Tornar Admin</a> | <a href="#" onclick="alterarNivel(${u.id_profissional}, 0); return false;" style="color:#ef4444;">Revogar</a>` : ''}
                       ${parseInt(u.nivel) === 2 ? `<a href="#" onclick="alterarNivel(${u.id_profissional}, 1); return false;" style="color:#ef4444;">Rebaixar</a>` : ''}
                    </td>
                `;
                tbody.appendChild(tr);
            });  }

        window.alterarNivel = async function(id_profissional, nivel) {
       const confirmacao = confirm(`Deseja alterar o nível deste usuário para "${labels[nivel]}"?`);
       if (!confirmacao) return;

      const resposta = await postData('php/gerenciar_usuarios.php', { id_profissional, nivel });
            alert(resposta.mensagem);
            if (resposta.status === 'sucesso') carregarUsuarios(); };
  carregarUsuarios();