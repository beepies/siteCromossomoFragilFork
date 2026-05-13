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
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação do banco");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $usuario = $resultado->fetch_assoc();

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            responder('erro', "E-mail ou senha incorretos");
        }

        $stmt->close();
    } catch (Exception $e) {
        responder('erro', $e->getMessage());
    }
} else {
    responder('erro', 'Dados inválidos ou JSON malformado');
}
