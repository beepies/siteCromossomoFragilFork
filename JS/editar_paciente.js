import { postData, resultado, verificarSessao } from './utils.js';
verificarSessao(); 

const formulario = document.getElementById('formEditarPaciente');

// Mapeia os IDs dos inputs HTML com as propriedades que o PHP espera receber
const campos = {
    numero_inscricao: 'numero_inscricao',
    nome_completo: 'nome_completo',
    cpf: 'cpf',
    data_nascimento: 'data_nascimento',
    sexo: 'sexo',
    email: 'email',
    telefone: 'telefone',
    endereco: 'endereco',
    novo_registro_profissional_atual: 'novo_registro_profissional_atual'
};

// Função para coletar todos os dados do formulário com segurança
function coletarDados() {
    const dados = {};
    for (const [id, nomeCampo] of Object.entries(campos)) {
        const elemento = document.getElementById(id);
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

// Função para validar data de nascimento (verifica maioridade)
function validarDataNascimento(data) {
    const dataNasc = new Date(data);
    const hoje = new Date();
    const idade = hoje.getFullYear() - dataNasc.getFullYear();
    
    const mesJa = hoje.getMonth() > dataNasc.getMonth() || 
                  (hoje.getMonth() === dataNasc.getMonth() && hoje.getDate() >= dataNasc.getDate());
    
    const idadeReal = mesJa ? idade : idade - 1;
    
    if (idadeReal < 18) {
        return { valido: false, mensagem: 'O paciente deve ser maior de 18 anos.' };
    }
    return { valido: true };
}

// Validações de preenchimento do formulário
function validarFormulario(dados) {
    const camposObrigatorios = [
        'numero_inscricao', 
        'nome_completo', 
        'cpf', 
        'data_nascimento', 
        'sexo', 
        'novo_registro_profissional_atual'
    ];
    
    for (const campo of camposObrigatorios) {
        if (!dados[campo] || dados[campo].trim() === '') {
            return { valido: false, mensagem: `O campo obrigatório está vazio: ${campo}` };
        }
    }
    
    if (!validarCPF(dados.cpf)) {
        return { valido: false, mensagem: 'CPF inválido (deve conter 11 dígitos).' };
    }
    
    const validacaoData = validarDataNascimento(dados.data_nascimento);
    if (!validacaoData.valido) {
        return validacaoData;
    }
    
    if (dados.novo_registro_profissional_atual.trim().length < 4) {
        return { valido: false, mensagem: 'O Novo Registro Profissional deve conter um formato válido.' };
    }
    
    return { valido: true };
}

// Event listener para envio do formulário
formulario.addEventListener('submit', async function(event) {
    event.preventDefault();

    const dados = coletarDados();
    
    // Validação visual e estrutural no front-end
    const validacao = validarFormulario(dados);
    if (!validacao.valido) {
        alert(validacao.mensagem);
        return;
    }

    // Envia os dados para o servidor
    const resposta = await resultado('php/editar_paciente.php', dados, formulario);
    alert(resposta.mensagem || resposta);
});

// Event listener para o botão de cancelar
const btnCancelar = formulario.querySelector('button[type="button"]');
if (btnCancelar) {
    btnCancelar.addEventListener('click', () => {
        if (confirm('Deseja realmente cancelar as edições? As alterações não salvas serão perdidas.')) {
            formulario.reset();
        }
    });
}