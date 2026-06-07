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
$nivel = buscarPorCampo($conn, 'profissional_saude', 'nivel', 'token', $token);
if (!$idMedicoLogado) {
    responder('erro', 'Acesso negado: Sessão inválida ou expirada.');
}

// 3. Consulta estruturada para trazer apenas os pacientes vinculados ao ID do médico
$sql = "SELECT pt.numero_inscricao, pt.nome_completo, pt.cpf, pt.telefone, pt.data_nascimento,
               rl.nome_completo AS responsavel_nome, rl.telefone AS responsavel_telefone,
               rl.email AS responsavel_email, rl.parentesco AS responsavel_parentesco,
               rl.id_responsavel AS responsavel_id,
               ps.nome_completo AS medico_responsavel
        FROM paciente_titular pt
        LEFT JOIN responsavel_legal rl ON rl.id_paciente = pt.id_paciente
        LEFT JOIN profissional_saude ps ON ps.id_profissional = pt.id_profissional_atual
        " . ($nivel < 2 ? "WHERE pt.id_profissional_atual = ?" : "") . "
        ORDER BY pt.nome_completo ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('erro', 'Erro ao preparar a consulta no banco de dados.');
}

if ($nivel < 2) {
    $stmt->bind_param("i", $idMedicoLogado);
}
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