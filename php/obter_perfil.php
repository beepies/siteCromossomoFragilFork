<?php
require_once 'helpers.php';

// Inicialização padrão usando o helper
configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

// Captura o token do cabeçalho da requisição
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

// 1. Validação básica de segurança: Verifica se o token veio vazio
if (empty($token)) {
    responder('erro', 'Token não fornecido');
}

// 2. Prepara a query para buscar os dados baseados no token
$sql = "SELECT id_profissional, nome_completo, registro_profissional FROM profissional_saude WHERE token = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('erro', "Erro na preparação do banco: " . $conn->error);
}

$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

// 3. Verifica se encontrou algum profissional com esse token
if ($user = $res->fetch_assoc()) {
    // Fecha o statement e a conexão ANTES de imprimir o resultado
    $stmt->close();
    $conn->close();

    // helpers.php não suporta enviar arrays extras pelo responder(),
    // criamos e enviamos o JSON manualmente em caso de sucesso:
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Dados recuperados',
        'dados' => $user
    ]);
    exit;
} else {
    // Fecha as conexões em caso de erro também
    $stmt->close();
    $conn->close();
    
    // Usa o helper para retornar o erro
    responder('erro', 'Usuário não encontrado ou token inválido');
}
?>