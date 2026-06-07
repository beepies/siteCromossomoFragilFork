<?php
require_once 'helpers.php';

configurarErrorHandlers();
$env = carregarEnv();
$conn = conectarBanco($env);

// 1. Validação de Acesso (Token)
$token = getallheaders()['Authorization'] ?? '';
$id_profissional = buscarPorCampo($conn, 'profissional_saude', 'id_profissional', 'token', $token);
if (!$id_profissional) responder('erro', 'Token inválido');

// 2. Extração
$dados = receberDados();
$numero_inscricao = trim($dados['numero_inscricao'] ?? '');
$sintomas = $dados['dados'] ?? [];

// 3. Busca de Paciente
$id_paciente = buscarPorCampo($conn, 'paciente_titular', 'id_paciente', 'numero_inscricao', $numero_inscricao);
if (!$id_paciente) responder('erro', 'Paciente não encontrado');

// 4. Início da Transação (Garante integridade)
$conn->begin_transaction();

try {
    // Insere a avaliação
    $stmt = $conn->prepare("INSERT INTO avaliacao_clinica (id_profissional, id_paciente, data_avaliacao) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $id_profissional, $id_paciente);
    $stmt->execute();
    $id_avaliacao = $conn->insert_id;
    $stmt->close();

    // Preparação da Query de sintomas (Apenas UMA vez)
    $stmtSintoma = $conn->prepare("INSERT INTO avaliacao_sintoma (id_avaliacao, id_sintoma, presente) VALUES (?, ?, ?)");

    // Dicionário dinâmico (mais versátil)
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

    foreach ($sintomas as $key => $presente) {
        $nome = $mapa[$key] ?? null;
        if (!$nome) continue;

        $id_sintoma = buscarPorCampo($conn, 'sintoma', 'id_sintoma', 'nome_sintoma', $nome);
        if ($id_sintoma) {
            $p = (int)$presente;
            $stmtSintoma->bind_param("iii", $id_avaliacao, $id_sintoma, $p);
            $stmtSintoma->execute();
        }
    }
    
    $stmtSintoma->close();
    $conn->commit(); // Salva tudo de uma vez
    responder('sucesso', 'Triagem salva com sucesso');

} catch (Exception $e) {
    $conn->rollback(); // Desfaz tudo se der erro
    responder('erro', 'Erro ao salvar triagem: ' . $e->getMessage());
}
?>