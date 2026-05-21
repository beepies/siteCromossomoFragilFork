<?php
// config.php - O que é igual para todo o site
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Conexão única
$host = "localhost";
$user = "root";
$pass = "1404_Felicia"; 
$db   = "sindrome_x";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Falha na conexão']);
    exit;
}

// Função para ler o JSON enviado pelo JS (evita repetir file_get_contents)
function receberDados() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

// Função para responder ao JS sempre no mesmo padrão
function responder($status, $mensagem, $dadosExtras = []) {
    $resposta = ['status' => $status, 'mensagem' => $mensagem];
    echo json_encode(array_merge($resposta, $dadosExtras));
    exit;
}
?>