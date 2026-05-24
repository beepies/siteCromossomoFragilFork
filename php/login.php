<?php
require_once 'config.php';

$dados = receberDados();

if ($dados) {
    //Extração das variáveis
    $email = trim($dados['email'] ?? '');
    $senha = $dados['senha'] ?? '';

    //Validação básica 
    if (empty($email) || empty($senha)) {
        responder('erro', 'Preencha todos os campos');
    }

    try {
        //Query para verificar se o usuário existe e obter a senha hash
        $sql = "SELECT id_profissional, nome_completo, email, senha FROM profissional_saude WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação do banco: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $usuario = $resultado->fetch_assoc();

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            responder('erro', "E-mail ou senha incorretos");
        }

        // Gerar um token único para esta sessão
        $token_unico = false;
        $token = '';
        // Garante que o token gerado não esteja em uso por outro usuário
        while (!$token_unico) {
            $token = bin2hex(random_bytes(32));

            // Verifica se esse token já está sendo usado por alguém
            $check = $conn->prepare("SELECT id_profissional FROM profissional_saude WHERE token = ?");
            $check->bind_param("s", $token);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $token_unico = true; // Achamos um token que ninguém tem
            }
        }
        // Define a expiração do token (24 horas)
        $expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));
        // Salva o token no banco para este usuário
        $sql_update = "UPDATE profissional_saude SET token = ?, data_expiracao = ? WHERE id_profissional = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $token, $expiracao, $usuario['id_profissional']);
        $stmt_update->execute();
        // Responde com sucesso e inclui o token e dados do usuário
        responder('sucesso', "Login realizado com sucesso", ['usuario' => [
            'id' => $usuario['id_profissional'],
            'nome' => $usuario['nome_completo']
        ], 'token' => $token]);

        $stmt->close();
    } catch (Exception $e) {
        responder('erro', $e->getMessage());
    }
} else {
    responder('erro', 'Dados inválidos ou JSON malformado');
}
