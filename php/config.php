<?php
// config.php - O que é igual para todo o site
error_reporting(E_ALL);
ini_set('display_errors', 1);

// NÃO colocar header aqui, pois pode causar problemas com error_reporting
// header('Content-Type: application/json'); será colocado após validações iniciais

// Carrega variáveis do arquivo .env manualmente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Conexão única
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$db   = $_ENV['DB_NAME'] ?? 'sindrome_x';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'erro', 'mensagem' => 'Falha na conexão: ' . $conn->connect_error]);
    exit;
}

// Função para ler o JSON enviado pelo JS (evita repetir file_get_contents)
function receberDados()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

// Função para responder ao JS sempre no mesmo padrão
function responder($status, $mensagem, $dadosExtras = [])
{
    header('Content-Type: application/json');
    $resposta = ['status' => $status, 'mensagem' => $mensagem];
    echo json_encode(array_merge($resposta, $dadosExtras));
    exit;
}
