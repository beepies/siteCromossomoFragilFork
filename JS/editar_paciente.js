import { resultado, verificarSessao } from './utils.js';
verificarSessao(); 

const formulario = document.getElementById('formEditarPaciente');

// Lista limpa dos IDs dos inputs que existem no HTML
const campos = [
    'numero_inscricao', 'nome_completo', 'cpf', 'data_nascimento', 
    'sexo', 'email', 'telefone', 'endereco', 'novo_registro_profissional_atual'
];

// Coleta os dados reduzindo a lista de campos em um único objeto final
const coletarDados = () => campos.reduce((dados, id) => {
    const elemento = document.getElementById(id);
    if (elemento) dados[id] = elemento.value;
    return dados;
}, {});

// Validações simplificadas
const validarCPF = cpf => cpf.replace(/\D/g, '').length === 11;

function validarFormulario(dados) {
    const obrigatorios = ['numero_inscricao', 'nome_completo', 'cpf', 'data_nascimento', 'sexo', 'novo_registro_profissional_atual'];
    
    // Procura se algum campo obrigatório está em branco
    const campoVazio = obrigatorios.find(campo => !dados[campo]?.trim());
    if (campoVazio) return `O campo obrigatório está vazio: ${campoVazio}`;
    
    if (!validarCPF(dados.cpf)) return 'CPF inválido (deve conter 11 dígitos).';
    if (dados.novo_registro_profissional_atual.trim().length < 4) return 'O Novo Registro Profissional deve conter um formato válido.';
    
    return null;
}

// Evento de envio do formulário
formulario.addEventListener('submit', async (event) => {
    event.preventDefault();
    const dados = coletarDados();
    
    const erro = validarFormulario(dados);
    if (erro) return alert(erro);

    const resposta = await resultado('php/editar_paciente.php', dados, formulario);
    alert(resposta.mensagem || resposta);
});
// Botão Cancelar
formulario.querySelector('button[type="button"]')?.addEventListener('click', () => {
    if (confirm('Deseja realmente cancelar as edições? As alterações não salvas serão perdidas.')) {
        formulario.reset();
    }
});