import { postData, resultado } from './utils.js';

const formulario = document.getElementById('formCadastroResponsavel');

// Mapeia os IDs dos inputs HTML com os nomes dos campos do banco de dados
// DRY: centraliza nomes de campos para evitar duplicação
const campos = {
    nome_completo: 'nome_completo',
    telefone: 'telefone',
    parentesco: 'parentesco',
    email: 'email',
    cpf_titular: 'cpf_titular'
};

// Função para coletar todos os dados do formulário
// DRY: reutiliza o objeto 'campos' para evitar duplicação
function coletarDados() {
    const dados = {};
    for (const [id, nomeCampo] of Object.entries(campos)) {
        const el = document.getElementById(id);
        if (el) dados[nomeCampo] = el.value;
    }
    return dados;
}

// Função para validar CPF (básico)
function validarCPF(cpf) {
    // Remove caracteres especiais e valida comprimento
    const cpfLimpo = cpf.replace(/\D/g, '');
    return cpfLimpo.length === 11;
}

// Validações do formulário
function validarFormulario(dados) {
    // Validação de campos obrigatórios
    const camposObrigatorios = ['nome_completo', 'cpf_titular'];
    
    for (const campo of camposObrigatorios) {
        if (!dados[campo] || dados[campo].trim() === '') {
            return { valido: false, mensagem: `Campo obrigatório vazio: ${campo}` };
        }
    }
    
    // Validação específica do CPF do paciente
    if (!validarCPF(dados.cpf_titular)) {
        return { valido: false, mensagem: 'CPF do paciente inválido (deve conter 11 dígitos)' };
    }
    
    // Validação do formato de email (se preenchido)
    if (dados.email && dados.email.trim() !== '' && !dados.email.includes('@')) {
        return { valido: false, mensagem: 'E-mail inválido' };
    }
    
    return { valido: true };
}

// Event listener para envio do formulário
formulario.addEventListener('submit', async function(event) {
    event.preventDefault();

    const dados = coletarDados();
    
    // Validação no frontend antes de enviar
    const validacao = validarFormulario(dados);
    if (!validacao.valido) {
        alert(validacao.mensagem);
        return;
    }

    // Envia para o servidor
    const resposta = await resultado('php/cadastro_responsavel.php', dados, formulario);
    alert(resposta.mensagem || resposta);
});