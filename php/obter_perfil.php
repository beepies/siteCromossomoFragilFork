<?php
require_once 'config.php';
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

// Validação de segurança básica
$sql = "SELECT id_profissional, nome_completo, registro_profissional FROM profissional_saude WHERE token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($user = $res->fetch_assoc()) {
    // Retorna os dados do médico como JSON
    responder('sucesso', 'Dados recuperados', ['dados' => $user]);
} else {
    responder('erro', 'Usuário não encontrado');
}