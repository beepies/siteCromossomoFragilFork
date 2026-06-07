<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

$dados = receberDados();

// 1. Validação de Token de forma direta
$token = getallheaders()['Authorization'] ?? '';
if (empty($token)) responder('erro', 'Acesso negado: Token não fornecido.');

$idMedicoLogado = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$idMedicoLogado) responder('erro', 'Acesso negado: Sessão inválida ou expirada.');

$nivel = buscarPorCampo($conn, 'profissional_saude', 'nivel', 'token', $token);
if ($nivel < 1) { responder('erro', 'Sem permissão para editar pacientes!!!');
}

// 2. DRY nas validações obrigatórias locais do PHP
$obrigatorios = ['numero_inscricao', 'nome_completo', 'cpf', 'data_nascimento', 'sexo'];
if (!validarCamposObrigatorios($dados, $obrigatorios)) {
    responder('erro', 'Campos obrigatórios ausentes ou vazios.');
}

// 3. Regras de Negócio e Segurança (Travas)
$idAtualNoBanco = buscarPorCampo($conn, 'paciente_titular', 'id_profissional_atual', 'numero_inscricao', $dados['numero_inscricao']);
if (!$idAtualNoBanco) responder('erro', 'Paciente não encontrado no sistema.');

if (intval($idAtualNoBanco) !== intval($idMedicoLogado) && $nivel < 2) {
    responder('erro', 'Acesso negado: Você não é o profissional responsável por este paciente.');
}

$registroNovo = trim($dados['novo_registro_profissional_atual'] ?? '');
if (!empty($registroNovo)) {
    $idNovoProfissional = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'registro_profissional', $registroNovo);
    if (!$idNovoProfissional) responder('erro', 'O novo profissional informado não foi encontrado.');
} else {
    $idNovoProfissional = $idAtualNoBanco;
}
// 4. Execução do Update
$sql = "UPDATE paciente_titular SET 
            nome_completo = ?, cpf = ?, data_nascimento = ?, sexo = ?, 
            email = ?, telefone = ?, endereco = ?, id_profissional_atual = ? 
        WHERE numero_inscricao = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) responder('erro', "Erro ao preparar a atualização: " . $conn->error);

$stmt->bind_param(
    "sssssssis", 
    $dados['nome_completo'], $dados['cpf'], $dados['data_nascimento'], $dados['sexo'],
    $dados['email'], $dados['telefone'], $dados['endereco'], $idNovoProfissional, 
    $dados['numero_inscricao']
);

$sucesso = $stmt->execute();
$stmt->close();
$conn->close();

if ($sucesso) {
    responder('sucesso', 'Dados do paciente atualizados com sucesso!');
} else {
    responder('erro', 'Erro ao atualizar os dados no banco de dados.');
}