<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);
$dados = receberDados();

// Extração e sanitização das variáveis
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

// Validação de campos obrigatórios
$camposObrigatorios = ['numero_inscricao', 'nome_completo', 'cpf', 'data_nascimento', 'sexo', 'registro_profissional', 'registro_profissional_atual'];
if (!validarCamposObrigatorios([
    'numero_inscricao' => $numero_inscricao,
    'nome_completo' => $nome_completo,
    'cpf' => $cpf,
    'data_nascimento' => $data_nascimento,
    'sexo' => $sexo,
    'registro_profissional' => $registro_profissional,
    'registro_profissional_atual' => $registro_profissional_atual
], $camposObrigatorios)) {
    responder('erro', 'Campos obrigatórios vazios');
}

// Validação do CPF
if (!validarCPF($cpf)) {
    responder('erro', 'CPF inválido (deve conter 11 dígitos)');
}

// Validação do email (se preenchido)
if (!empty($email) && !validarEmail($email)) {
    responder('erro', 'E-mail inválido');
}

// Busca os profissionais pelo CRM usando função compartilhada
$id_profissional = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'registro_profissional', $registro_profissional);
if ($id_profissional === null) {
    responder('erro', "Profissional não encontrado com CRM: $registro_profissional");
}

$id_profissional_atual = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'registro_profissional', $registro_profissional_atual);
if ($id_profissional_atual === null) {
    responder('erro', "Profissional não encontrado com CRM: $registro_profissional_atual");
}

// INSERT do paciente
$sql = "INSERT INTO paciente_titular 
        (numero_inscricao, nome_completo, cpf, data_nascimento, sexo, email, telefone, endereco, id_profissional, id_profissional_atual) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    responder('erro', 'Erro ao preparar insert: ' . $conn->error);
}

$stmt->bind_param("ssssssssii", $numero_inscricao, $nome_completo, $cpf, $data_nascimento, $sexo, $email, $telefone, $endereco, $id_profissional, $id_profissional_atual);

if ($stmt->execute()) {
    responder('sucesso', "Paciente $nome_completo cadastrado com sucesso");
} else {
    $mapeoCampos = [
        'numero_inscricao' => 'Este número de inscrição já está cadastrado',
        'cpf' => 'Este CPF já está cadastrado'
    ];
    $mensagem = tratarErroUniqueConstraint($conn, $mapeoCampos);
    responder('erro', $mensagem);
}

$stmt->close();
$conn->close();
?>
