import { postData, resultado } from './utils.js';

const formulario = document.getElementById('formCadastroDependente');

// Mapeia os IDs dos inputs HTML com os nomes dos campos do banco de dados
// DRY: centraliza nomes de campos para evitar duplicação
const campos = {
    numero_inscricao: 'numero_inscricao',
    nome_completo: 'nome_completo',
    data_nascimento: 'data_nascimento',
    sexo: 'sexo',
    email: 'email',
    cpf_titular: 'cpf_titular'
};

// Função para coletar todos os dados do formulário
// DRY: reutiliza o objeto 'campos' para evitar duplicação
function coletarDados() {
    const dados = {};
    for (const [id, nomeCampo] of Object.entries(campos)) {
        dados[nomeCampo] = document.getElementById(id).value;
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
    const camposObrigatorios = ['numero_inscricao', 'nome_completo', 'data_nascimento', 'sexo', 'cpf_titular'];
    
    for (const campo of camposObrigatorios) {
        if (!dados[campo] || dados[campo].trim() === '') {
            return { valido: false, mensagem: `Campo obrigatório vazio: ${campo}` };
        }
    }
    
    // Validação específica do CPF do titular
    if (!validarCPF(dados.cpf_titular)) {
        return { valido: false, mensagem: 'CPF do titular inválido (deve conter 11 dígitos)' };
    }
    
    // Validação do formato de email (se preenchido)
    if (dados.email && dados.email.trim() !== '' && !dados.email.includes('@')) {
        return { valido: false, mensagem: 'E-mail do dependente inválido' };
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
    const resposta = await resultado('php/cadastro_dependente.php', dados, formulario);
    alert(resposta.mensagem || resposta);
});
