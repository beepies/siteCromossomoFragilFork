<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

$token = getallheaders()['Authorization'] ?? '';
if (empty($token)) {
    http_response_code(401);
    exit;}

$id_medico = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$id_medico) {
    http_response_code(403);
    exit;}

$numero_inscricao = trim($_GET['inscricao'] ?? '');
if (empty($numero_inscricao)) {
    http_response_code(400);
    exit;}

$sql = "SELECT foto FROM paciente_titular WHERE numero_inscricao = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $numero_inscricao);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row || !$row['foto']) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/jpeg');
header('Cache-Control: private, max-age=3600');
echo $row['foto'];
exit;
?>