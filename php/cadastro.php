<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);
$dados = receberDados();

// Extração das variáveis
$nome = trim($dados['nome'] ?? '');
$registroProfissional = trim($dados['Registro_profissional'] ?? '');
$especialidade = trim($dados['Especialidade'] ?? '');
$email = trim($dados['email'] ?? '');
$telefone = trim($dados['telefone'] ?? '');
$instituicao = trim($dados['Instituicao'] ?? '');
$senha = $dados['senha'] ?? '';

// Validação básica 
if (!validarCamposObrigatorios([
    'nome' => $nome,
    'email' => $email,
    'senha' => $senha,
    'registroProfissional' => $registroProfissional,
    'especialidade' => $especialidade,
    'telefone' => $telefone,
    'instituicao' => $instituicao
], ['nome', 'email', 'senha', 'registroProfissional', 'especialidade', 'telefone', 'instituicao'])) {
    responder('erro', 'Preencha todos os campos');
}

$senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);

$sql = "INSERT INTO profissional_saude (nome_completo, email, registro_profissional, especialidade, telefone, instituicao, senha_profissional) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('erro', "Erro na preparação do banco: " . $conn->error);
}

$stmt->bind_param("sssssss", $nome, $email, $registroProfissional, $especialidade, $telefone, $instituicao, $senhaHash);

if ($stmt->execute()) {
    responder('sucesso', "Usuário $nome cadastrado com sucesso");
} else {
    $mapeoCampos = [
        'email' => 'Este e-mail já está cadastrado',
        'telefone' => 'Este telefone já está cadastrado',
        'registro_profissional' => 'Este registro profissional já está cadastrado'
    ];
    $mensagem = tratarErroUniqueConstraint($conn, $mapeoCampos);
    responder('erro', $mensagem);
}

$stmt->close();
$conn->close();
?>
