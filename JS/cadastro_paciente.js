import { postData, resultado, verificarSessao } from './utils.js';

verificarSessao();

const formulario = document.getElementById('formCadastroPaciente');

// Recupera a string da sessão e converte para um Objeto JavaScript real
const usuarioSession = sessionStorage.getItem('usuario');
const medicoLogado = usuarioSession ? JSON.parse(usuarioSession) : null;

// Mapeia os campos esperados pela API / Banco de dados
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

// Função para coletar dados apenas dos elementos presentes no HTML
function coletarDados() {
    const dados = {};
    for (const [id, nomeCampo] of Object.entries(campos)) {
        const elemento = document.getElementById(id);
        
        // Só lê o .value se o elemento realmente existir no HTML
        if (elemento) {
            dados[nomeCampo] = elemento.value;
        }
    }
    return dados;
}

// Função para validar CPF (básico)
function validarCPF(cpf) {
    const cpfLimpo = cpf.replace(/\D/g, '');
    return cpfLimpo.length === 11;
}

// Validações estruturais do formulário
function validarFormulario(dados) {
    const camposObrigatorios = [
        'numero_inscricao', 
        'nome_completo', 
        'cpf', 
        'data_nascimento', 
        'sexo', 
        'registro_profissional', 
        'registro_profissional_atual'
    ];
    
    for (const campo of camposObrigatorios) {
        if (!dados[campo] || dados[campo].trim() === '') {
            return { valido: false, mensagem: `O campo obrigatório está ausente ou vazio: ${campo}` };
        }
    }
    
    if (!validarCPF(dados.cpf)) {
        return { valido: false, mensagem: 'CPF inválido (deve conter 11 dígitos).' };
    }
    
    return { valido: true };
}

// Event listener para envio do formulário
formulario.addEventListener('submit', async function(event) {
    event.preventDefault();

    // 1. Coleta os campos preenchidos na interface (HTML)
    const dados = coletarDados();
    
    // 2. Extrai o registro profissional do médico logado na sessão
    const registroMedico = medicoLogado?.registro_profissional;

    if (!registroMedico) {
        alert("Sessão inválida ou expirada. Faça login novamente para cadastrar pacientes.");
        return;
    }

    // 3. Injeta as informações do médico logado de forma transparente no payload
    dados.registro_profissional = registroMedico;       // Quem está cadastrando
    dados.registro_profissional_atual = registroMedico; // Responsável inicial

    // 4. Executa a validação geral com os dados completos
    const validacao = validarFormulario(dados);
    if (!validacao.valido) {
        alert(validacao.mensagem);
        return;
    }

    // 5. Envia os dados acoplados para o backend
    const resposta = await resultado('php/cadastro_paciente.php', dados, formulario);
    alert(resposta.mensagem || resposta);
});