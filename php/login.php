<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);
$dados = receberDados();

// Extração das variáveis
$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

// Validação básica 
if (!validarCamposObrigatorios([
    'email' => $email,
    'senha' => $senha
], ['email', 'senha'])) {
    responder('erro', 'Preencha todos os campos');
}

// Query para verificar se o usuário existe e obter a senha hash
$sql = "SELECT id_profissional, nome_completo, email, senha_profissional FROM profissional_saude WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('erro', "Erro na preparação do banco: " . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

$usuario = $resultado->fetch_assoc();

if (!$usuario || !password_verify($senha, $usuario['senha_profissional'])) {
    responder('erro', "E-mail ou senha incorretos");
}


// 1. Gera um token aleatório e seguro
$token = bin2hex(random_bytes(32));

// 2. Define a data de expiração para daqui a 24 horas (Formato aceito pelo MySQL/DateTime)
$data_expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));

// 3. Salva o token e a expiração nas colunas corretas do banco de dados
$sqlUpdate = "UPDATE profissional_saude SET token = ?, data_expiracao = ? WHERE id_profissional = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);

if (!$stmtUpdate) {
    responder('erro', "Erro ao preparar o salvamento do token: " . $conn->error);
}

$stmtUpdate->bind_param("ssi", $token, $data_expiracao, $usuario['id_profissional']);
$stmtUpdate->execute();
$stmtUpdate->close();

// Responde com sucesso e inclui os dados do usuário + O TOKEN para o Front-end
ob_end_clean();
header('Content-Type: application/json');
echo json_encode([
    'status' => 'sucesso',
    'mensagem' => 'Login realizado com sucesso',
    'token' => $token, // O front-end deve capturar isso e enviar no Header Authorization
    'data_expiracao' => $data_expiracao,
    'usuario' => [
        'id' => $usuario['id_profissional'],
        'nome' => $usuario['nome_completo']
    ]
]);
$stmt->close();
$conn->close();
?>