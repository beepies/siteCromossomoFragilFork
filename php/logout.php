<?php
require_once 'helpers.php';

// configura error handler e conecta no banco
configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

// pega o token que veio no header da requisição
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

// se veio token not null, apaga ele do banco
if (!empty($token)) {
    $sql = "UPDATE profissional_saude SET token = NULL WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
}

// fecha a conexão com o banco
$conn->close();

// destrói a sessão do servidor
session_start();
session_destroy();

// manda pra pagina inicial
header('Location: ../index.html');
exit;
?>