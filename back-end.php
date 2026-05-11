<?php
header('Content-Type: application/json');
// 1. Recebe o JSON bruto que o JS enviou
$inputJSON = file_get_contents('php://input');
$dados = json_decode($inputJSON, true);

if ($dados){
    $nome = $dados['nome'] ?? '';
    $email = $dados['email'] ?? '';
    $senha = $dados['senha'] ?? '';
    $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost'=> 12]);

    $host = "localhost";
    $user = "root";
    $pass = "PUC@1234"; 
    $db   = "meu_site";

    // Conecta ao banco de dados
    $conn = new mysqli($host, $user, $pass, $db);
    // Verifica a conexão
    if ($conn->connect_error) {
        // Se falhar a conexão, manda o erro em formato JSON
        echo json_encode(['status' => 'erro', 'mensagem' => 'Falha na conexão']);
        exit;
    }
    // Prepara a consulta SQL para inserir os dados
    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $senhaHash);

    if ($stmt->execute()) {
        // 5. SÓ AGORA você confirma o sucesso
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => "Usuário $nome cadastrado com sucesso!"
        ]);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar no banco']);
    }

    $stmt->close();
    $conn->close();
} 
else {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Dados inválidos'
    ]);
}
?>