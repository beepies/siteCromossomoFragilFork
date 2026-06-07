<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);
$dados = receberDados();

$token = getallheaders()['Authorization'] ?? '';
if (empty($token)) responder('erro', 'Acesso negado: Token não fornecido.');
$id_medico = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$id_medico) responder('erro', 'Acesso negado: Sessão inválida ou expirada.');

$nivel = buscarPorCampo($conn, 'profissional_saude', 'nivel', 'token', $token);
if ($nivel < 1) responder('erro', 'Sem permissão para cadastrar dependentes.');

// Extração e sanitização das variáveis
$nome_completo = trim($dados['nome_completo'] ?? '');
$telefone = trim($dados['telefone'] ?? '');
$parentesco = trim($dados['parentesco'] ?? '');
$email = trim($dados['email'] ?? '');
$cpf_titular = trim($dados['cpf_titular'] ?? '');

// Validação de campos obrigatórios
if (!validarCamposObrigatorios([
    'nome_completo' => $nome_completo,
    'cpf_titular' => $cpf_titular
], ['nome_completo', 'cpf_titular'])) {
    responder('erro', 'Campos obrigatórios vazios');
}

// Validação do CPF do titular
if (!validarCPF($cpf_titular)) {
    responder('erro', 'CPF do titular inválido (deve conter 11 dígitos)');
}

// Validação do email (se preenchido)
if (!empty($email) && !validarEmail($email)) {
    responder('erro', 'E-mail do dependente inválido');
}

// Busca o ID do titular usando o CPF (função compartilhada)
$id_titular = buscarPorCampo($conn, 'paciente_titular', 'id_paciente', 'cpf', $cpf_titular);
if ($id_titular === null) {
    responder('erro', "Paciente titular não encontrado com CPF: $cpf_titular");
}

// Verifica limite de 4 responsaveis
$total_responsaveis = contarRegistros($conn, 'responsavel_legal', 'id_paciente', $id_titular);
if ($total_responsaveis >= 4) {
    responder('erro', 'Este paciente já possui 4 responsáveis (limite atingido)');
}

// INSERT do dependente
$sql = "INSERT INTO responsavel_legal (nome_completo, telefone, parentesco, email, id_paciente) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $nome_completo, $telefone, $parentesco, $email, $id_titular);

if ($stmt->execute()) {
    responder('sucesso', "Responsável $nome_completo cadastrado com sucesso");
}

$stmt->close();
$conn->close();
?>
