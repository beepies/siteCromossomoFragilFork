import { getData, postData, verificarSessao } from './utils.js';
verificarSessao();

const formBuscar = document.getElementById('formBuscarPaciente');
const areaResponsaveis = document.getElementById('areaResponsaveis');
const selectResponsavel = document.getElementById('selectResponsavel');
const formEditar = document.getElementById('formEditarResponsavel');
let responsaveis = [];

// Busca responsáveis pelo CPF do paciente
formBuscar.addEventListener('submit', async function(event) {
    event.preventDefault();
    const cpf = document.getElementById('cpf_paciente').value.trim();
    const resposta = await getData(`php/buscar_responsaveis.php?cpf=${encodeURIComponent(cpf)}`);

    if (resposta.status !== 'sucesso') {
        alert(resposta.mensagem);
        return;
    }
    responsaveis = resposta.dados;
    if (responsaveis.length === 0) {
        alert('Nenhum responsável cadastrado para este paciente.');
        return; }

    // Preenche o dropdown
    selectResponsavel.innerHTML = '<option value="" disabled selected>Selecione...</option>';
    responsaveis.forEach(r => {
        const option = document.createElement('option');
        option.value = r.id_responsavel;
        option.textContent = `${r.nome_completo} (${r.parentesco || '-'})`;
        selectResponsavel.appendChild(option);   });
    areaResponsaveis.style.display = 'block';
    formEditar.style.display = 'none';
});
// Quando seleciona um responsável, preenche o formulário
selectResponsavel.addEventListener('change', function() {
    const id = parseInt(this.value);
    const responsavel = responsaveis.find(r => r.id_responsavel === id);
    if (!responsavel) return;
    document.getElementById('nome_completo').value = responsavel.nome_completo || '';
    document.getElementById('telefone').value = responsavel.telefone || '';
    document.getElementById('parentesco').value = responsavel.parentesco || '';
    document.getElementById('email').value = responsavel.email || '';
    formEditar.style.display = 'block';
});
// Salva as alterações
formEditar.addEventListener('submit', async function(event) {
    event.preventDefault();

    const id_responsavel = parseInt(selectResponsavel.value);
    const dados = {
        id_responsavel,
        nome_completo: document.getElementById('nome_completo').value.trim(),
        telefone: document.getElementById('telefone').value.trim(),
        parentesco: document.getElementById('parentesco').value.trim(),
        email: document.getElementById('email').value.trim()};
    if (!dados.nome_completo) {
        alert('Nome completo é obrigatório.');
        return; }

    const resposta = await postData('php/editar_responsavel.php', dados);
    alert(resposta.mensagem || resposta);
});