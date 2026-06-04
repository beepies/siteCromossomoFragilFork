<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

// Validação de segurança básica
$sql = "SELECT id_profissional, nome_completo, registro_profissional FROM profissional_saude WHERE token = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('erro', "Erro na preparação do banco: " . $conn->error);
}

$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($user = $res->fetch_assoc()) {
    // Retorna os dados do médico como JSON
    responder('sucesso', 'Dados recuperados', ['dados' => $user]);
} else {
    responder('erro', 'Usuário não encontrado');
}

$stmt->close();
$conn->close();
?>
