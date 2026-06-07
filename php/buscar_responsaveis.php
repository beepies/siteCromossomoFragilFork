<?php
require_once 'helpers.php';
configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';
if (empty($token)) {
    responder('erro', 'Token não fornecido');
}

$idMedico = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$idMedico) {
    responder('erro', 'Sessão inválida ou expirada');
}

$cpf = trim($_GET['cpf'] ?? '');
if (empty($cpf)) {
    responder('erro', 'CPF não informado');
}

// busca o paciente pelo CPF e verifica se é do médico logado
$id_paciente = buscarPorCampo($conn, 'paciente_titular', 'id_paciente', 'cpf', $cpf);
if (!$id_paciente) {
    responder('erro', 'Paciente não encontrado');}

$sql = "SELECT id_responsavel, nome_completo, telefone, email, parentesco
        FROM responsavel_legal
        WHERE id_paciente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_paciente);
$stmt->execute();
$resultado = $stmt->get_result();
$responsaveis = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
ob_end_clean();
header('Content-Type: application/json');
echo json_encode(['status' => 'sucesso', 'dados' => $responsaveis]);
exit;
?>