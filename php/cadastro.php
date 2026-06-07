<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$chave = $env['CHAVE_CRIPTOGRAFIA']; // Necessário para a função criptografar()
$conn = conectarBanco($env);
$dados = receberDados();

// 1. EXTRAÇÃO (Dados brutos/puros)
$nome = trim($dados['nome'] ?? '');
$registroProfissional = trim($dados['Registro_profissional'] ?? '');
$especialidade = trim($dados['Especialidade'] ?? '');
$email = trim($dados['email'] ?? '');
$telefone = trim($dados['telefone'] ?? '');
$instituicao = trim($dados['Instituicao'] ?? '');
$senha = $dados['senha'] ?? '';

// 2. VALIDAÇÃO (Usando os dados originais/brutos)
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

// 3. HASH DA SENHA (Senha NUNCA deve ser criptografada com AES reversível, apenas com Hash unidirecional)
$senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);

// 4. CRIPTOGRAFIA (Apenas campos de exibição, NÃO criptografe identificadores de busca)
$nome_cripto = criptografar($nome, $chave);
$telefone_cripto = criptografar($telefone, $chave);
$instituicao_cripto = criptografar($instituicao, $chave);

// OBS: $email e $registroProfissional são mantidos como texto puro
// para permitir a busca no login e validação de duplicidade.

// 5. INSERT
$sql = "INSERT INTO profissional_saude 
        (nome_completo, email, registro_profissional, especialidade, telefone, instituicao, senha_profissional) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
// ...
$stmt->bind_param("sssssss", 
    $nome_cripto, 
    $email, // <-- Enviando e-mail puro
    $registroProfissional, // <-- Enviando CRM puro
    $especialidade, 
    $telefone_cripto, 
    $instituicao_cripto, 
    $senhaHash
);

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