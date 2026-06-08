<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

$token = getallheaders()['Authorization'] ?? '';
if (empty($token)) responder('erro', 'Token não fornecido');

$id_medico = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$id_medico) responder('erro', 'Sessão inválida ou expirada');

$nivel = buscarPorCampo($conn, 'profissional_saude', 'nivel', 'token', $token);
if ($nivel < 1) responder('erro', 'Sem permissão');
$numero_inscricao = trim($_POST['numero_inscricao'] ?? '');
if (empty($numero_inscricao)) responder('erro', 'Número de inscrição não informado');
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    responder('erro', 'Nenhuma foto enviada');
}
// valida tipo
$tipospermitidos = ['image/jpeg', 'image/png', 'image/webp'];
$tipo = mime_content_type($_FILES['foto']['tmp_name']);
if (!in_array($tipo, $tipospermitidos)) {
    responder('erro', 'Formato inválido. Use JPG, PNG ou WEBP');
}
$fotoBlob = file_get_contents($_FILES['foto']['tmp_name']);
$sql = "UPDATE paciente_titular SET foto = ? WHERE numero_inscricao = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $fotoBlob, $numero_inscricao);

if ($stmt->execute()) {
    responder('sucesso', 'Foto salva com sucesso');} else {
    responder('erro', 'Erro ao salvar foto');
}
$stmt->close();
$conn->close();
?>