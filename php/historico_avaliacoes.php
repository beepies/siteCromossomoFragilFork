<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

$headers = getallheaders();
$token = $headers['Authorization'] ?? '';
if (empty($token)) {
    responder('erro', 'Token não fornecido');}

// Valida médico
$id_profissional = buscarPorCampo(
    $conn,
    'profissional_saude',
    'id_profissional',
    'token',
    $token
);

if (!$id_profissional) {
    responder('erro', 'Token inválido ou expirado');
}

$numero_inscricao = trim($_GET['inscricao'] ?? '');
$nome_filtro = trim($_GET['nome'] ?? '');

if (empty($numero_inscricao) && empty($nome_filtro)) {
    responder('erro', 'Informe o número de inscrição ou nome do paciente');
}

if (!empty($numero_inscricao)) {
    $sql = "SELECT id_paciente FROM paciente_titular WHERE numero_inscricao = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $numero_inscricao);
} else {
    $sql = "SELECT id_paciente FROM paciente_titular WHERE nome_completo LIKE ?";
    $stmt = $conn->prepare($sql);
    $nome_like = '%' . $nome_filtro . '%';
    $stmt->bind_param("s", $nome_like);
}
$stmt->execute();
$result = $stmt->get_result();
$paciente = $result->fetch_assoc();
$stmt->close();

if (!$paciente) {
    responder('erro', 'Paciente não encontrado');
}

$id_paciente = $paciente['id_paciente'];

$data_filtro = trim($_GET['data'] ?? '');

$nivel = buscarPorCampo($conn, 'profissional_saude', 'nivel', 'token', $token);

$sql = "
SELECT
    ac.id_avaliacao,
    ac.data_avaliacao,
    ac.score,
    ac.classificacao_risco,
    pt.nome_completo
FROM avaliacao_clinica ac
JOIN paciente_titular pt ON pt.id_paciente = ac.id_paciente
WHERE ac.id_paciente = ?
" . ($nivel < 2 ? "AND ac.id_profissional = ? " : "") . "
" . (!empty($data_filtro) ? "AND DATE(ac.data_avaliacao) = ? " : "") . "
ORDER BY ac.data_avaliacao DESC
";

$stmt = $conn->prepare($sql);

if ($nivel < 2 && !empty($data_filtro)) {
    $stmt->bind_param("iis", $id_paciente, $id_profissional, $data_filtro);
} elseif ($nivel < 2) {
    $stmt->bind_param("ii", $id_paciente, $id_profissional);
} elseif (!empty($data_filtro)) {
    $stmt->bind_param("is", $id_paciente, $data_filtro);
} else {
    $stmt->bind_param("i", $id_paciente);
}
$stmt->execute();

$result = $stmt->get_result();

$historico = [];

while ($avaliacao = $result->fetch_assoc()) {

    $sqlSintomas = "
    SELECT
        s.nome_sintoma,
        avs.presente
    FROM avaliacao_sintoma avs
    INNER JOIN sintoma s
        ON s.id_sintoma = avs.id_sintoma
    WHERE avs.id_avaliacao = ?
    ";

    $stmtSintomas = $conn->prepare($sqlSintomas);
    $stmtSintomas->bind_param(
        "i",
        $avaliacao['id_avaliacao']
    );

    $stmtSintomas->execute();

    $resultadoSintomas =
        $stmtSintomas->get_result();

    $sintomas = [];

    while ($sintoma =
        $resultadoSintomas->fetch_assoc()) {

        $sintomas[] = [
            'nome' => $sintoma['nome_sintoma'],
            'presente' => (bool)$sintoma['presente']
        ];
    }

    $stmtSintomas->close();

    $avaliacao['sintomas'] = $sintomas;

    $historico[] = $avaliacao;
}

$stmt->close();
$conn->close();

ob_end_clean();
header('Content-Type: application/json');
echo json_encode([
    'status' => 'sucesso',
    'mensagem' => 'Histórico carregado com sucesso',
    'dados' => ['historico' => $historico]
]);
exit;