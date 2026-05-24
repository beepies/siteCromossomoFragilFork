<?php
require_once 'config.php';

// Pega o token enviado pelo Header Authorization
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (empty($token)) {
    responder('erro', 'Token não fornecido');
}

// Verifica se existe um usuário com esse token
$sql = "SELECT id_profissional, data_expiracao FROM profissional_saude WHERE token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $id_profissional = $row['id_profissional'];
    $data_expiracao = $row['data_expiracao'];

    // Verifica se a sessão ainda é válida
    $agora = new DateTime();
    $expiracao = new DateTime($data_expiracao);

    if ($agora < $expiracao) {
        responder('sucesso', 'Autenticado');
    } else {
        responder('erro', 'Sessão expirada');
    }
} else {
    responder('erro', 'Sessão inválida');
}