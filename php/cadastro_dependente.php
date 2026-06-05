<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);
$dados = receberDados();

// Extração e sanitização das variáveis
$numero_inscricao = trim($dados['numero_inscricao'] ?? '');
$nome_completo = trim($dados['nome_completo'] ?? '');
$data_nascimento = trim($dados['data_nascimento'] ?? '');
$sexo = trim($dados['sexo'] ?? '');
$email = trim($dados['email'] ?? '');
$cpf_titular = trim($dados['cpf_titular'] ?? '');

// Validação de campos obrigatórios
$camposObrigatorios = ['numero_inscricao', 'nome_completo', 'data_nascimento', 'sexo', 'cpf_titular'];
if (!validarCamposObrigatorios([
    'numero_inscricao' => $numero_inscricao,
    'nome_completo' => $nome_completo,
    'data_nascimento' => $data_nascimento,
    'sexo' => $sexo,
    'cpf_titular' => $cpf_titular
], $camposObrigatorios)) {
    responder('erro', 'Campos obrigatórios vazios');
}

// Validação do CPF do titular
if (!validarCPF($cpf_titular)) {
    responder('erro', 'CPF do titular inválido (deve conter 11 dígitos)');
}

// Validação do email (se preenchido)
if (!empty($email) && !validarEmail($email)) {
    responder('erro', 'E-mail do dependente inválido');
}

// Busca o ID do titular usando o CPF (função compartilhada)
$id_titular = buscarPorCampo($conn, 'paciente_titular', 'id_paciente', 'cpf', $cpf_titular);
if ($id_titular === null) {
    responder('erro', "Paciente titular não encontrado com CPF: $cpf_titular");
}

// Verifica limite de 4 dependentes (função compartilhada)
$total_dependentes = contarRegistros($conn, 'dependente', 'id_titular', $id_titular);
if ($total_dependentes >= 4) {
    responder('erro', 'Este titular já possui 4 dependentes (limite atingido)');
}

// INSERT do dependente
$sql = "INSERT INTO dependente (numero_inscricao, nome_completo, data_nascimento, sexo, email, id_titular) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    responder('erro', 'Erro ao preparar insert: ' . $conn->error);
}

$stmt->bind_param("sssssi", $numero_inscricao, $nome_completo, $data_nascimento, $sexo, $email, $id_titular);

if ($stmt->execute()) {
    responder('sucesso', "Dependente $nome_completo cadastrado com sucesso");
} else {
    $mapeoCampos = [
        'numero_inscricao' => 'Este número de inscrição já está cadastrado'
    ];
    $mensagem = tratarErroUniqueConstraint($conn, $mapeoCampos);
    responder('erro', $mensagem);
}

$stmt->close();
$conn->close();
?>
