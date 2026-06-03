<?php
ob_start();
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'erro',
        'mensagem' => "[$errno] $errstr em $errfile:$errline"
    ]);
    exit;
});

set_exception_handler(function($e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'erro',
            'mensagem' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

// Carrega variáveis do arquivo .env
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
}

header('Content-Type: application/json');

$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$db   = $env['DB_NAME'] ?? 'sindrome_x';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode(['status' => 'erro', 'mensagem' => 'Conexão falhou: ' . $conn->connect_error]);
    exit;
}

$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) {
    ob_end_clean();
    echo json_encode(['status' => 'erro', 'mensagem' => 'JSON inválido']);
    exit;
}

$numero_inscricao = trim($dados['numero_inscricao'] ?? '');
$nome_completo = trim($dados['nome_completo'] ?? '');
$cpf = trim($dados['cpf'] ?? '');
$data_nascimento = trim($dados['data_nascimento'] ?? '');
$sexo = trim($dados['sexo'] ?? '');
$email = trim($dados['email'] ?? '');
$telefone = trim($dados['telefone'] ?? '');
$endereco = trim($dados['endereco'] ?? '');
$registro_profissional = trim($dados['registro_profissional'] ?? '');
$registro_profissional_atual = trim($dados['registro_profissional_atual'] ?? '');

if (empty($numero_inscricao) || empty($nome_completo) || empty($cpf) || empty($data_nascimento) || empty($sexo) || empty($registro_profissional) || empty($registro_profissional_atual)) {
    ob_end_clean();
    echo json_encode(['status' => 'erro', 'mensagem' => 'Campos obrigatórios vazios']);
    exit;
}

$stmt1 = $conn->prepare("SELECT id_profissional FROM profissional_saude WHERE registro_profissional = ?");
$stmt1->bind_param("s", $registro_profissional);
$stmt1->execute();
$resultado1 = $stmt1->get_result();

if ($resultado1->num_rows === 0) {
    ob_end_clean();
    echo json_encode(['status' => 'erro', 'mensagem' => 'Profissional 1 não encontrado: ' . $registro_profissional]);
    exit;
}

$prof1 = $resultado1->fetch_assoc();
$id_profissional = $prof1['id_profissional'];
$stmt1->close();

$stmt2 = $conn->prepare("SELECT id_profissional FROM profissional_saude WHERE registro_profissional = ?");
$stmt2->bind_param("s", $registro_profissional_atual);
$stmt2->execute();
$resultado2 = $stmt2->get_result();

if ($resultado2->num_rows === 0) {
    ob_end_clean();
    echo json_encode(['status' => 'erro', 'mensagem' => 'Profissional 2 não encontrado: ' . $registro_profissional_atual]);
    exit;
}

$prof2 = $resultado2->fetch_assoc();
$id_profissional_atual = $prof2['id_profissional'];
$stmt2->close();

$sql = "INSERT INTO paciente_titular (numero_inscricao, nome_completo, cpf, data_nascimento, sexo, email, telefone, endereco, id_profissional, id_profissional_atual) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssii", $numero_inscricao, $nome_completo, $cpf, $data_nascimento, $sexo, $email, $telefone, $endereco, $id_profissional, $id_profissional_atual);

if ($stmt->execute()) {
    ob_end_clean();
    echo json_encode(['status' => 'sucesso', 'mensagem' => "Paciente $nome_completo cadastrado com sucesso"]);
} else {
    ob_end_clean();
    if ($conn->errno === 1062) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Dados duplicados no banco']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar: ' . $stmt->error]);
    }
}

$stmt->close();
$conn->close();
?>
