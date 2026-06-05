<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

// 1. Recebe os dados enviados pelo Front-end
$dados = receberDados();

// 2. Captura e valida o token do médico logado
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (empty($token)) {
    responder('erro', 'Acesso negado: Token não fornecido.');
}

// 3. Descobre o ID do médico logado através do token dele
// Ajustado para 'id_profissional'
$idMedicoLogado = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);

if (!$idMedicoLogado) {
    responder('erro', 'Acesso negado: Sessão inválida ou expirada.');
}

// 4. Valida se o número de inscrição do paciente foi enviado
$numeroInscricao = $dados['numero_inscricao'] ?? '';

if (empty($numeroInscricao)) {
    responder('erro', 'O número de inscrição do paciente é obrigatório.');
}

// 5. Busca o ID do profissional atual direto na tabela correta: paciente_titular
$idAtualNoBanco = buscarPorCampo($conn, 'paciente_titular', 'id_profissional_atual', 'numero_inscricao', $numeroInscricao);

if (!$idAtualNoBanco) {
    responder('erro', 'Paciente não encontrado no sistema.');
}

// 6. A TRAVA DE SEGURANÇA REAL: Compara os IDs numéricos
if (intval($idAtualNoBanco) !== intval($idMedicoLogado)) {
    responder('erro', 'Acesso negado: Você não é o profissional responsável atual por este paciente.');
}

// 7. CONVERSÃO: O front enviou o texto do registro, precisamos achar o ID desse novo profissional
$novoRegistroTexto = $dados['novo_registro_profissional_atual'] ?? '';

// Ajustado para buscar o 'id_profissional' correspondente ao 'registro_profissional' digitado
$idNovoProfissional = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'registro_profissional', $novoRegistroTexto);

if (!$idNovoProfissional) {
    responder('erro', 'O novo profissional informado não foi encontrado no sistema. Verifique o registro digitado.');
}


// TUDO VALIDADO. AGORA EXECUTA O UPDATE USANDO O ID DO NOVO PROFISSIONAL

// A query agora atualiza na tabela paciente_titular
$sql = "UPDATE paciente_titular SET 
            nome_completo = ?, 
            cpf = ?, 
            data_nascimento = ?, 
            sexo = ?, 
            email = ?, 
            telefone = ?, 
            endereco = ?,
            id_profissional_atual = ? 
        WHERE numero_inscricao = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('erro', "Erro ao preparar a atualização: " . $conn->error);
}

// Vincula os parâmetros ("sssssssis" - string, string, string, string, string, string, string, int, string)
$stmt->bind_param(
    "sssssssis", 
    $dados['nome_completo'],
    $dados['cpf'],
    $dados['data_nascimento'],
    $dados['sexo'],
    $dados['email'],
    $dados['telefone'],
    $dados['endereco'],
    $idNovoProfissional, 
    $numeroInscricao
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    responder('sucesso', 'Dados do paciente atualizados com sucesso!');
} else {
    $stmt->close();
    $conn->close();
    responder('erro', 'Erro ao atualizar os dados no banco de dados.');
}
?>