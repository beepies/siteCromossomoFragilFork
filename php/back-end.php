<?php
require_once 'config.php';

$dados = receberDados();

if ($dados) {
    // 1. Extração das variáveis
    $nome  = trim($dados['nome'] ?? '');
    $email = trim($dados['email'] ?? '');
    $senha = $dados['senha'] ?? '';

    // 2. Validação básica 
    if (empty($nome) || empty($email) || empty($senha)) {
        responder('erro', 'Preencha todos os campos');
    }

    $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

    try {

        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação do banco");
        }

        $stmt->bind_param("sss", $nome, $email, $senhaHash);

        if ($stmt->execute()) {
            responder('sucesso', "Usuário $nome cadastrado com sucesso");
        } else {
            // Verificação de e-mail duplicado
            if ($conn->errno === 1062) {
                responder('erro', "Este e-mail já está cadastrado");
            } else {
                responder('erro', "Erro ao salvar no banco");
            }
        }

        $stmt->close();
    } catch (Exception $e) {
        responder('erro', $e->getMessage());
    }
} else {
    responder('erro', 'Dados inválidos ou JSON malformado');
}
