<?php
require_once 'helpers.php';
configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

$headers = getallheaders();
$token = $headers['Authorization'] ?? '';
if (empty($token)) {
    responder('erro', 'Token não fornecido');}
$id_profissional = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$id_profissional) {
    responder('erro', 'Token inválido ou expirado');}

// só admin pode acessar
$nivel = buscarPorCampo($conn, 'profissional_saude', 'nivel', 'token', $token);
if ($nivel < 2) {
    responder('erro', 'Sem permissão para gerenciar usuários');}

$metodo = $_SERVER['REQUEST_METHOD'];
// GET lista todos os profissionais
if ($metodo === 'GET') {
    $sql = "SELECT id_profissional, nome_completo, registro_profissional, especialidade, email, nivel FROM profissional_saude ORDER BY nivel DESC, nome_completo ASC";
    $result = $conn->query($sql);
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;}
    
    $conn->close();
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'sucesso', 'dados' => $usuarios]);
    exit;}

// POST atualiza o nível de um profissional
if ($metodo === 'POST') {
    $dados = receberDados();
    $id_alvo = intval($dados['id_profissional'] ?? 0);
    $novo_nivel = intval($dados['nivel'] ?? 0);
    if (!$id_alvo) {
        responder('erro', 'ID do profissional não informado');}
    if (!in_array($novo_nivel, [0, 1, 2])) {
        responder('erro', 'Nível inválido');
    }
    // admin não pode rebaixar a si mesmo
    if ($id_alvo === intval($id_profissional)) {
        responder('erro', 'Você não pode alterar seu próprio nível'); }
    $sql = "UPDATE profissional_saude SET nivel = ? WHERE id_profissional = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $novo_nivel, $id_alvo);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    responder('sucesso', 'Nível atualizado com sucesso');
}
responder('erro', 'Método não reconhecido');?>