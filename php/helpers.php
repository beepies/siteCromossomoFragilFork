<?php
/**
 * helpers.php - Funções auxiliares compartilhadas entre endpoints
 * DRY: evita duplicação de código em múltiplos arquivos
 */

// ====== CONFIGURAÇÃO E INICIALIZAÇÃO ======

/**
 * Configura error handlers para retornar JSON em caso de erro
 * Previne que HTML de erro quebre a resposta JSON
 */
function configurarErrorHandlers() {
    ob_start();
    
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'erro',
            'mensagem' => "[$errno] $errstr em $errfile:$errline"
        ]);
        exit;
    });

    set_exception_handler(function($e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'erro',
            'mensagem' => $e->getMessage()
        ]);
        exit;
    });

    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'erro',
                'mensagem' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);
        }
    });
}

/**
 * Carrega variáveis do arquivo .env
 * DRY: reutilizado em vários arquivos
 * @return array Array associativo com variáveis do .env
 */
function carregarEnv() {
    $env = [];
    $envFile = __DIR__ . '/../.env';
    
    if (file_exists($envFile)) {
        $lines = file($envFile);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }
    }
    
    return $env;
}

/**
 * Conecta ao banco de dados
 * @param array $env Variáveis de ambiente
 * @return mysqli Conexão com o banco
 */
function conectarBanco($env) {
    header('Content-Type: application/json');
    
    $host = $env['DB_HOST'] ?? 'localhost';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';
    $db   = $env['DB_NAME'] ?? 'sindrome_x';

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        ob_end_clean();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Conexão falhou: ' . $conn->connect_error]);
        exit;
    }
    
    return $conn;
}

/**
 * Recebe e decodifica dados JSON do POST
 * @return array|null Array com dados ou null se inválido
 */
function receberDados() {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    if (!$dados) {
        ob_end_clean();
        echo json_encode(['status' => 'erro', 'mensagem' => 'JSON inválido']);
        exit;
    }
    
    return $dados;
}

/**
 * Responde ao cliente com mensagem JSON formatada
 * @param string $status 'sucesso' ou 'erro'
 * @param string $mensagem Mensagem a retornar
 */
function responder($status, $mensagem) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'mensagem' => $mensagem]);
    exit;
}

// ====== VALIDAÇÕES ======

/**
 * Valida CPF (verifica apenas comprimento)
 * @param string $cpf CPF a validar (com ou sem formatação)
 * @return bool true se válido
 */
function validarCPF($cpf) {
    $cpfLimpo = preg_replace('/\D/', '', $cpf);
    return strlen($cpfLimpo) === 11;
}

/**
 * Valida email
 * @param string $email Email a validar
 * @return bool true se válido
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida campos obrigatórios
 * @param array $campos Array associativo ['nomeCampo' => valor]
 * @param array $obrigatorios Array com nomes dos campos obrigatórios
 * @return bool true se todos preenchidos
 */
function validarCamposObrigatorios($campos, $obrigatorios) {
    foreach ($obrigatorios as $campo) {
        if (empty($campos[$campo] ?? '')) {
            return false;
        }
    }
    return true;
}

/**
 * Trata erros de duplicidade do MySQL (UNIQUE constraints)
 * @param mysqli $conn Conexão com banco
 * @param array $mapeoCampos Array ['campo_db' => 'mensagem_erro']
 * @return string Mensagem de erro apropriada
 */
function tratarErroUniqueConstraint($conn, $mapeoCampos = []) {
    if ($conn->errno !== 1062) {
        return 'Erro ao salvar no banco';
    }
    
    $erro = $conn->error;
    
    // Se não há mapeamento, retorna genérico
    if (empty($mapeoCampos)) {
        return 'Dados duplicados no banco';
    }
    
    // Procura qual campo causou o erro
    foreach ($mapeoCampos as $campoDb => $mensagem) {
        if (strpos($erro, $campoDb) !== false) {
            return $mensagem;
        }
    }
    
    return 'Dados duplicados no banco';
}

// ====== QUERIES COMUNS ======

/**
 * Busca informação por campo em uma tabela
 * @param mysqli $conn Conexão com banco
 * @param string $tabela Nome da tabela
 * @param string $campoRetorno Campo a retornar (ex: id_profissional)
 * @param string $campoBusca Campo onde buscar (ex: registro_profissional)
 * @param string $valor Valor a buscar
 * @return int|null ID encontrado ou null
 */
function buscarPorCampo($conn, $tabela, $campoRetorno, $campoBusca, $valor) {
    $sql = "SELECT $campoRetorno FROM $tabela WHERE $campoBusca = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        responder('erro', 'Erro ao preparar busca: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $valor);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 0) {
        return null;
    }
    
    $row = $resultado->fetch_assoc();
    $stmt->close();
    
    return $row[$campoRetorno];
}

/**
 * Conta registros em uma tabela com condição
 * @param mysqli $conn Conexão com banco
 * @param string $tabela Nome da tabela
 * @param string $campo Campo da condição
 * @param int $valor Valor da condição
 * @return int Quantidade de registros
 */
function contarRegistros($conn, $tabela, $campo, $valor) {
    $sql = "SELECT COUNT(*) as total FROM $tabela WHERE $campo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $valor);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $stmt->close();
    
    return $row['total'];
}

// ====== CRIPTOGRAFIA ======

/**
 * Criptografa um valor usando AES-256-CBC
 * @param string $valor Dado a ser criptografado
 * @param string $chave Chave secreta (deve vir do .env)
 * @return string|null Dado criptografado em base64 ou null se vazio
 */
function criptografar($valor, $chave) {
    if (empty($valor)) return null;
    
    $algoritmo = 'aes-256-cbc';
    $iv_tamanho = openssl_cipher_iv_length($algoritmo);
    $iv = openssl_random_pseudo_bytes($iv_tamanho);
    
    $criptografado = openssl_encrypt($valor, $algoritmo, $chave, 0, $iv);
    
    // Unifica o IV e o texto criptografado em base64 para salvar com segurança
    return base64_encode($iv . $criptografado);
}

/**
 * Descriptografa um valor criptografado em AES-256-CBC
 * @param string $valor_criptografado Dado criptografado salvo no banco
 * @param string $chave Chave secreta (deve vir do .env)
 * @return string|null Dado original ou null se falhar
 */
function descriptografar($valor_criptografado, $chave) {
    if (empty($valor_criptografado)) return null;
    
    $algoritmo = 'aes-256-cbc';
    $dados_decodificados = base64_decode($valor_criptografado);
    $iv_tamanho = openssl_cipher_iv_length($algoritmo);
    
    // Separa o IV do texto criptografado real
    $iv = substr($dados_decodificados, 0, $iv_tamanho);
    $texto_cripto = substr($dados_decodificados, $iv_tamanho);
    
    return openssl_decrypt($texto_cripto, $algoritmo, $chave, 0, $iv);
}
?>
