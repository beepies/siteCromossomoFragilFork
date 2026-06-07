<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$chave = $env['CHAVE_CRIPTOGRAFIA'];
$conn = conectarBanco($env);
$dados = receberDados();

$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

// 1. Validação ANTES de qualquer criptografia
if (empty($email) || empty($senha)) {
    responder('erro', 'Preencha todos os campos');
}

// 2. BUSCA (Buscando o e-mail diretamente, sem criptografar antes)
$sql = "SELECT id_profissional, nome_completo, senha_profissional FROM profissional_saude WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email); // <-- Usando e-mail puro
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

// 3. Verificação de senha
if (!$usuario || !password_verify($senha, $usuario['senha_profissional'])) {
    responder('erro', "E-mail ou senha incorretos");
}

// 4. DESCRIPTOGRAFAR apenas o que foi criptografado (nome)
$nome_descriptografado = descriptografar($usuario['nome_completo'], $chave);

// 5. Geração do Token (mantive sua lógica)
$token = bin2hex(random_bytes(32));
$data_expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));

$sqlUpdate = "UPDATE profissional_saude SET token = ?, data_expiracao = ? WHERE id_profissional = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("ssi", $token, $data_expiracao, $usuario['id_profissional']);
$stmtUpdate->execute();

// 6. Resposta limpa
ob_end_clean();
header('Content-Type: application/json');
echo json_encode([
    'status' => 'sucesso',
    'token' => $token,
    'usuario' => [
        'id' => $usuario['id_profissional'],
        'nome' => $nome_descriptografado // Agora enviamos o nome legível
    ]
]);
?>