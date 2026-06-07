<?php
require_once 'helpers.php';
configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);
$dados = receberDados();

$token = getallheaders()['Authorization'] ?? '';
if (empty($token)) responder('erro', 'Token não fornecido');
$idMedico = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$idMedico) responder('erro', 'Sessão inválida ou expirada');
$nivel = buscarPorCampo($conn, 'profissional_saude', 'nivel', 'token', $token);
if ($nivel < 1) responder('erro', 'Sem permissão para editar responsáveis');

$id_responsavel = intval($dados['id_responsavel'] ?? 0);
if (!$id_responsavel) responder('erro', 'ID do responsável não informado');
$nome_completo = trim($dados['nome_completo'] ?? '');
$telefone = trim($dados['telefone'] ?? '');
$parentesco = trim($dados['parentesco'] ?? '');
$email = trim($dados['email'] ?? '');
if (empty($nome_completo)) responder('erro', 'Nome completo é obrigatório');
if (!empty($email) && !validarEmail($email)) responder('erro', 'E-mail inválido');

$sql = "UPDATE responsavel_legal SET nome_completo = ?, telefone = ?, parentesco = ?, email = ? WHERE id_responsavel = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $nome_completo, $telefone, $parentesco, $email, $id_responsavel);
if ($stmt->execute()) {
    responder('sucesso', 'Responsável atualizado com sucesso');
} else {
    responder('erro', 'Erro ao atualizar responsável');
}
$stmt->close();
$conn->close();
?>