<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

// 1. Validação do Token do Médico logado
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (empty($token)) {
    responder('erro', 'Acesso negado: Token não fornecido.');
}

$idMedicoLogado = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$idMedicoLogado) {
    responder('erro', 'Acesso negado: Sessão inválida.');
}

// 2. Captura o parâmetro enviado por GET
$inscricao = trim($_GET['inscricao'] ?? '');

if (empty($inscricao)) {
    responder('erro', 'O número de inscrição não foi informado.');
}

// 3. Busca o paciente no banco de dados
$sql = "SELECT nome_completo, cpf, id_profissional_atual 
        FROM paciente_titular 
        WHERE numero_inscricao = ? LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    responder('erro', 'Erro interno no servidor de banco de dados.');
}

$stmt->bind_param("s", $inscricao);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    responder('erro', 'Paciente não cadastrado no sistema.');
}

$paciente = $resultado->fetch_assoc();
$stmt->close();

// Verifica se é menor de idade e tem responsável
$sqlIdade = "SELECT id_paciente, data_nascimento FROM paciente_titular WHERE numero_inscricao = ? LIMIT 1";
$stmtIdade = $conn->prepare($sqlIdade);
$stmtIdade->bind_param("s", $inscricao);
$stmtIdade->execute();
$rowIdade = $stmtIdade->get_result()->fetch_assoc();
$stmtIdade->close();
$idade = date_diff(date_create($rowIdade['data_nascimento']), date_create('today'))->y;
if ($idade < 18) {
    $totalResponsaveis = contarRegistros($conn, 'responsavel_legal', 'id_paciente', $rowIdade['id_paciente']);
  if ($totalResponsaveis === 0) {
        responder('erro', 'Paciente menor de idade sem responsável legal cadastrado. Cadastre um responsável antes de iniciar a triagem!');
    }
}

// 4. Trava de segurança: O paciente é desse médico?
if (intval($paciente['id_profissional_atual']) !== intval($idMedicoLogado)) {
    responder('erro', 'Acesso negado: Este paciente está sob responsabilidade de outro profissional.');
}

// Se passou em tudo, retorna o nome e o CPF para o Front-end exibir o card verde
ob_end_clean();
header('Content-Type: application/json');
echo json_encode([
    'status' => 'sucesso',
    'dados' => [
        'nome_completo' => $paciente['nome_completo'],
        'cpf' => $paciente['cpf']
    ]
]);
exit;