import { postData, resultado } from './utils.js';

const formulario = document.getElementById('formCadastroPaciente');

// Mapeia os IDs dos inputs HTML com os nomes dos campos no banco de dados
// DRY: evita repetir nomes de campos em múltiplos lugares
const campos = {
    numero_inscricao: 'numero_inscricao',
    nome_completo: 'nome_completo',
    cpf: 'cpf',
    data_nascimento: 'data_nascimento',
    sexo: 'sexo',
    email: 'email',
    telefone: 'telefone',
    endereco: 'endereco',
    registro_profissional: 'registro_profissional',
    registro_profissional_atual: 'registro_profissional_atual'
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

// Função para validar data de nascimento
// Trigger do banco garante que titular seja maior de 18 anos, mas validamos também no frontend
function validarDataNascimento(data) {
    const dataNasc = new Date(data);
    const hoje = new Date();
    const idade = hoje.getFullYear() - dataNasc.getFullYear();
    
    // Verifica se já completou aniversário este ano
    const mesJa = hoje.getMonth() > dataNasc.getMonth() || 
                  (hoje.getMonth() === dataNasc.getMonth() && hoje.getDate() >= dataNasc.getDate());
    
    const idadeReal = mesJa ? idade : idade - 1;
    
    if (idadeReal < 18) {
        return { valido: false, mensagem: 'Titular deve ser maior de 18 anos' };
    }
    return { valido: true };
}

// Validações do formulário
function validarFormulario(dados) {
    // Validação de campos obrigatórios
    const camposObrigatorios = ['numero_inscricao', 'nome_completo', 'cpf', 'data_nascimento', 'sexo', 'registro_profissional', 'registro_profissional_atual'];
    
    for (const campo of camposObrigatorios) {
        if (!dados[campo] || dados[campo].trim() === '') {
            return { valido: false, mensagem: `Campo obrigatório vazio: ${campo}` };
        }
    }
    
    // Validação específica do CPF
    if (!validarCPF(dados.cpf)) {
        return { valido: false, mensagem: 'CPF inválido (deve conter 11 dígitos)' };
    }
    
    // Validação específica da data de nascimento
    const validacaoData = validarDataNascimento(dados.data_nascimento);
    if (!validacaoData.valido) {
        return validacaoData;
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
    const resposta = await resultado('php/cadastro_paciente.php', dados, formulario);
    alert(resposta.mensagem || resposta);
});
