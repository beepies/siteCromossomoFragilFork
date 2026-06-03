<?php
require_once 'config.php';

$dados = receberDados();

if ($dados) {
    // 1. Extração das variáveis
    $nome  = trim($dados['nome'] ?? '');
    $registroProfissional = trim($dados['Registro_profissional'] ?? '');
    $especialidade = trim($dados['Especialidade'] ?? '');
    $email = trim($dados['email'] ?? '');
    $telefone = trim($dados['telefone'] ?? '');
    $instituicao = trim($dados['Instituicao'] ?? '');
    $senha = $dados['senha'] ?? '';

    // 2. Validação básica 
    if (empty($nome) || empty($email) || empty($senha) || empty($registroProfissional) || empty($especialidade) || empty($telefone) || empty($instituicao)) {
        responder('erro', 'Preencha todos os campos');
    }

    $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);

    try {

        $sql = "INSERT INTO 
        profissional_saude (nome_completo, email, registro_profissional, especialidade, telefone, instituicao, senha_profissional) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação do banco: " . $conn->error);
        }

        $stmt->bind_param("sssssss", $nome, $email, $registroProfissional, $especialidade, $telefone, $instituicao, $senhaHash);

        if ($stmt->execute()) {
            responder('sucesso', "Usuário $nome cadastrado com sucesso");
        } else {
            if ($conn->errno === 1062) {
                $erro = $conn->error;
                if (strpos($erro, 'email') !== false) {
                    responder('erro', "Este e-mail já está cadastrado");
                } elseif (strpos($erro, 'telefone') !== false) {
                    responder('erro', "Este telefone já está cadastrado");
                } elseif (strpos($erro, 'registro_profissional') !== false) {
                    responder('erro', "Este registro profissional já está cadastrado");
                } else {
                    responder('erro', "Campo duplicado: " . $erro);
                }
            } else {
                throw new Exception("Erro ao salvar no banco: " . $conn->error);
            }
        }

        $stmt->close();
    } catch (Exception $e) {
        responder('erro', $e->getMessage());
    }
} else {
    responder('erro', 'Dados inválidos ou JSON malformado: ' . json_last_error_msg());
}
