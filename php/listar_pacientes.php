<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

// 1. Captura e valida o token do médico logado através dos cabeçalhos
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (empty($token)) {
    responder('erro', 'Acesso negado: Token não fornecido.');
}

// 2. Descobre o ID do médico logado usando o helper global
$idMedicoLogado = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);

if (!$idMedicoLogado) {
    responder('erro', 'Acesso negado: Sessão inválida ou expirada.');
}

// 3. Consulta estruturada para trazer apenas os pacientes vinculados ao ID do médico
$sql = "SELECT numero_inscricao, nome_completo, cpf, telefone 
        FROM paciente_titular 
        WHERE id_profissional_atual = ? 
        ORDER BY nome_completo ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('erro', 'Erro ao preparar a consulta no banco de dados.');
}

$stmt->bind_param("i", $idMedicoLogado);
$stmt->execute();
$resultado = $stmt->get_result();

// Coleta todos os registros de forma direta em um array associativo
$pacientes = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

// Utiliza o helper para padronizar e disparar o JSON de sucesso de forma universal
ob_end_clean(); 
header('Content-Type: application/json');
echo json_encode([
    'status' => 'sucesso',
    'dados' => $pacientes
]);
exit;