<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);
$conn->query("SET time_zone = '-03:00'");

//pega o token do header
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (empty($token)) {
    responder('erro', 'Token não fornecido');}
// Busca o médico pelo token
$id_profissional = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$id_profissional) {
    responder('erro', 'Token inválido ou expirado');
}

$dados = receberDados();

$numero_inscricao = trim($dados['numero_inscricao'] ?? '');
$timestamp        = $dados['timestamp'] ?? date('Y-m-d H:i:s');
$sintomas         = $dados['dados'] ?? [];

if (empty($numero_inscricao) || empty($sintomas)) {
    responder('erro', 'Dados incompletos');}

$data_avaliacao = date('Y-m-d H:i:s', strtotime($timestamp));

// Busca 
$id_paciente = buscarPorCampo($conn, 'paciente_titular', 'id_paciente', 'numero_inscricao', $numero_inscricao);

if (!$id_paciente) {
    responder('erro', 'Paciente não encontrado com o número de inscrição fornecido.');}

// Cria a avaliacao_clinica
$sql = "INSERT INTO avaliacao_clinica (id_profissional, id_paciente, data_avaliacao) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    responder('erro', 'Erro ao preparar avaliação: ' . $conn->error);}
$stmt->bind_param("iis", $id_profissional, $id_paciente, $data_avaliacao);
if (!$stmt->execute()) {
    responder('erro', 'Erro ao salvar avaliação: ' . $stmt->error);
}

$id_avaliacao = $conn->insert_id;
$stmt->close();

// Insere cada sintoma em avaliacao_sintoma
foreach ($sintomas as $id_str => $presente) {
    $id_sintoma = buscarPorCampo($conn, 'sintoma', 'id_sintoma', 'nome_sintoma', $id_str);

    if (!$id_sintoma) {
        $mapa = [
            'atraso_fala'      => 'Atraso na fala',
            'dif_aprendizagem' => 'Dificuldades de aprendizagem',
            'deficit_atencao'  => 'Déficit de atenção',
            'def_intelectual'  => 'Deficiência intelectual',
            'hiperatividade'   => 'Hiperatividade',
            'comp_agressivo'   => 'Comportamento agressivo',
            'contato_visual'   => 'Evita contato visual',
            'contato_fisico'   => 'Evita contato físico',
            'mov_repetitivos'  => 'Movimentos repetitivos e rítmicos',
            'hipermobilidade'  => 'Hipermobilidade articular',
            'macroorquidia'    => 'Macroorquidia',
            'face_orelhas'     => 'Face alongada / orelhas salientes',
        ];

        $nome_sintoma = $mapa[$id_str] ?? null;
        if (!$nome_sintoma) continue;

        $id_sintoma = buscarPorCampo($conn, 'sintoma', 'id_sintoma', 'nome_sintoma', $nome_sintoma);
        if (!$id_sintoma) continue; }
    
    $presente_bool = (int) $presente;
    $sql = "INSERT INTO avaliacao_sintoma (id_avaliacao, id_sintoma, presente) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $id_avaliacao, $id_sintoma, $presente_bool);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
responder('sucesso', 'Triagem salva com sucesso');
?>