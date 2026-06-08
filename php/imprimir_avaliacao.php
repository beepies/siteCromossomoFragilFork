    <?php
    require_once 'helpers.php';

    configurarErrorHandlers();
    $env = carregarEnv();
    $chave = $env['CHAVE_CRIPTOGRAFIA'];
    $conn = conectarBanco($env);
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';

    if (empty($token)) {
        responder('erro', 'Token não fornecido');}

    $id_profissional = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
    if (!$id_profissional) {
        responder('erro', 'Token inválido ou expirado');}

    $id_avaliacao = intval($_GET['id'] ?? 0);
    if (!$id_avaliacao) {
        responder('erro', 'ID de avaliação não informado');
    }

    // busca a avaliação com dados do paciente e do médico
    $nivel = buscarPorCampo($conn, 'profissional_saude', 'nivel', 'token', $token);

    $sql = "
    SELECT
        ac.id_avaliacao,
        ac.data_avaliacao,
        ac.score,
        ac.classificacao_risco,
        pt.nome_completo AS nome_paciente,
        pt.cpf,
        pt.data_nascimento,
        pt.sexo,
        pt.numero_inscricao,
        ps.nome_completo AS nome_medico,
        ps.registro_profissional,
        ps.especialidade,
        ps.instituicao
    FROM avaliacao_clinica ac
    JOIN paciente_titular pt ON pt.id_paciente = ac.id_paciente
    JOIN profissional_saude ps ON ps.id_profissional = ac.id_profissional
    WHERE ac.id_avaliacao = ?
    " . ($nivel < 2 ? "AND ac.id_profissional = ?" : "") . "
    ";

    $stmt = $conn->prepare($sql);
    if ($nivel < 2) {
        $stmt->bind_param("ii", $id_avaliacao, $id_profissional);
    } else {
        $stmt->bind_param("i", $id_avaliacao);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $avaliacao = $result->fetch_assoc();
    $stmt->close();

    if (!$avaliacao) {
        responder('erro', 'Avaliação não encontrada');
    }
$avaliacao['nome_paciente'] = descriptografar($avaliacao['nome_paciente'], $chave);
$avaliacao['cpf']           = descriptografar($avaliacao['cpf'], $chave);

$avaliacao['nome_medico'] = descriptografar($avaliacao['nome_medico'], $chave);
$avaliacao['instituicao'] = descriptografar($avaliacao['instituicao'], $chave);

    // busca os sintomas
    $sql = "
    SELECT s.nome_sintoma, avs.presente, avs.peso_aplicado
    FROM avaliacao_sintoma avs
    JOIN sintoma s ON s.id_sintoma = avs.id_sintoma
    WHERE avs.id_avaliacao = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_avaliacao);
    $stmt->execute();
    $result = $stmt->get_result();
    $sintomas = [];
    while ($row = $result->fetch_assoc()) {
        $sintomas[] = $row;}
    $stmt->close();
    $conn->close();
    $avaliacao['sintomas'] = $sintomas;
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'sucesso',
        'dados' => $avaliacao
    ]);
    exit;
    ?>