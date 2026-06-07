import { getData } from './utils.js';
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (!id) {
        document.getElementById('conteudo').innerHTML = '<div class="carregando">ID de avaliação não informado.</div>';
    } else {
        const resposta = await getData(`php/imprimir_avaliacao.php?id=${id}`);

        if (resposta.status !== 'sucesso') {
            document.getElementById('conteudo').innerHTML = `<div class="carregando">${resposta.mensagem}</div>`;
        } else {
            const d = resposta.dados;
            const data = new Date(d.data_avaliacao.replace(' ', 'T') + '+02:00').toLocaleString('pt-BR');
            const suspeito = d.classificacao_risco === 'Suspeito';

            document.getElementById('conteudo').innerHTML = `
                <div class="cabecalho">
                    <div class="logo">
                        <div class="logo-icon">✚</div>
                        <div class="logo-texto">
                            <h1>SXF</h1>
                            <p>Instituto Buko Kaesemodel</p>
                        </div>
                    </div>
                    <div class="cabecalho-info">
                        <strong>Relatório de Avaliação Clínica</strong>
                        Avaliação #${d.id_avaliacao}<br>
                        ${data}
                    </div>
                </div>

                <div class="secao">
                    <span class="secao-titulo">Dados do Paciente</span>
                    <div class="grid-2">
                        <div class="info-item"><label>Nome completo</label><span>${d.nome_paciente}</span></div>
                        <div class="info-item"><label>Nº de Inscrição</label><span>${d.numero_inscricao}</span></div>
                        <div class="info-item"><label>CPF</label><span>${d.cpf}</span></div>
                        <div class="info-item"><label>Data de Nascimento</label><span>${new Date(d.data_nascimento).toLocaleDateString('pt-BR')}</span></div>
                        <div class="info-item"><label>Sexo</label><span>${d.sexo}</span></div>
                    </div>
                </div>

                <div class="secao">
                    <span class="secao-titulo">Profissional Responsável</span>
                    <div class="grid-2">
                        <div class="info-item"><label>Nome</label><span>${d.nome_medico}</span></div>
                        <div class="info-item"><label>Registro Profissional</label><span>${d.registro_profissional}</span></div>
                        <div class="info-item"><label>Especialidade</label><span>${d.especialidade}</span></div>
                        <div class="info-item"><label>Instituição</label><span>${d.instituicao}</span></div>
                    </div>
                </div>

                <div class="secao">
                    <span class="secao-titulo">Resultado da Triagem</span>
                    <div class="classificacao ${suspeito ? 'suspeito' : 'baixo'}">
                        <div>
                            <div class="classificacao-badge">${d.classificacao_risco ?? 'Não calculada'}</div>
                            <div class="classificacao-score">Score total: <strong>${d.score ?? 0}</strong> - Limiar: <strong>${d.sexo === 'Masculino' ? '0.56' : '0.55'}</strong></div>
                        </div>
                    </div>
                </div>

                <div class="secao">
                    <span class="secao-titulo">Checklist de Sintomas</span>
                    <table>
                        <thead>
                            <tr>
                                <th>Sintoma</th>
                                <th>Resultado</th>
                                <th>Peso Aplicado</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${d.sintomas.map(s => `
                                <tr>
                                    <td>${s.nome_sintoma}</td>
                                    <td><span class="${s.presente ? 'tag-presente' : 'tag-ausente'}">${s.presente ? 'Presente' : 'Ausente'}</span></td>
                                    <td>${s.peso_aplicado ?? '0.00'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                <div class="rodape">
                    <div>
                        <div>Documento gerado em ${new Date().toLocaleString('pt-BR')}</div>
                        <div>Sistema de Triagem SXF - Instituto Buko Kaesemodel</div>
                    </div>
                    <div class="assinatura">
                        <div class="assinatura-linha"></div>
                        <div>${d.nome_medico}</div>
                        <div>${d.registro_profissional} - ${d.especialidade}</div>
                    </div>
                </div>
            `;
        }
    }