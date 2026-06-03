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
        $sql = "SELECT id_profissional, nome_completo, email, senha_profissional FROM profissional_saude WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação do banco: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $usuario = $resultado->fetch_assoc();

        if (!$usuario || !password_verify($senha, $usuario['senha_profissional'])) {
            responder('erro', "E-mail ou senha incorretos");
        }

        // Responde com sucesso e inclui os dados do usuário
        responder('sucesso', "Login realizado com sucesso", ['usuario' => [
            'id' => $usuario['id_profissional'],
            'nome' => $usuario['nome_completo']
        ]]);

        $stmt->close();
    } catch (Exception $e) {
        responder('erro', $e->getMessage());
    }
} else {
    responder('erro', 'Dados inválidos ou JSON malformado');
}
