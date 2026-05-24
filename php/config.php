<?php
// config.php - O que é igual para todo o site
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// php/db.php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Carrega as variáveis do arquivo .env (que está na raiz)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Conexão única
$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$db   = $_ENV['DB_NAME'];
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Falha na conexão']);
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
    $resposta = ['status' => $status, 'mensagem' => $mensagem];
    echo json_encode(array_merge($resposta, $dadosExtras));
    exit;
}
