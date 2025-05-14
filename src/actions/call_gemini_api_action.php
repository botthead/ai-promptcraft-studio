<?php
// src/Actions/call_gemini_api_action.php
// Este script é INCLUÍDO por public/api.php. Ele NÃO deve ser acessado diretamente.
// Assume que a sessão já foi iniciada e a autenticação já foi verificada em api.php.
// Assume que headers('Content-Type: application/json') já foi definido em api.php.

// Não chame session_start() ou header() aqui (a menos que seja para um erro específico que api.php não trata)
// Não chame exit() ou die() no final, a menos que seja um erro crítico antes de gerar JSON.

use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Enums\MimeType;
use GeminiAPI\Resources\Parts\ImagePart;

// Certifique-se que a chave API e o cliente Gemini estão configurados
// A chave deve vir do perfil do usuário logado, não de uma constante global por segurança (TODO)
// Por enquanto, vamos buscar a chave do usuário logado na sessão/BD
// A variável $pdo já deve estar disponível, vindo de public/api.php
$user_id = $_SESSION['user_id'] ?? null; // Obtenha user_id da sessão

if (!$user_id) {
    // Isso NÃO deve acontecer se auth_check em api.php funcionar, mas é uma segurança.
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado para chamar a API.', 'error_type' => 'auth_required']);
    return; // Use return em vez de exit/die quando incluído
}

// TODO: Buscar a chave API Gemini do usuário logado no banco de dados
try {
    $stmt = $pdo->prepare("SELECT gemini_api_key_encrypted FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['gemini_api_key_encrypted'])) {
        http_response_code(400); // Bad Request ou 402 Payment Required (depende da lógica)
        echo json_encode(['success' => false, 'message' => 'Chave API Gemini não configurada no seu perfil.', 'error_type' => 'api_key_missing']);
         return;
    }

    // TODO: Descriptografar a chave API (usando a chave de criptografia de Constants.php e funções de functions.php)
    $gemini_api_key = decryptData($user['gemini_api_key_encrypted'], ENCRYPTION_KEY); // Implemente decryptData na functions.php

    if (!$gemini_api_key) {
         http_response_code(500); // Internal Server Error
         echo json_encode(['success' => false, 'message' => 'Erro ao descriptografar a chave API.', 'error_type' => 'decryption_failed']);
         return;
    }


    // Instanciar o cliente Gemini (assumindo que a biblioteca via Composer está instalada)
    // require_once __DIR__ . '/../../vendor/autoload.php'; // Assumindo vendor está na raiz do projeto
     $client = new Client($gemini_api_key);

} catch (\PDOException $e) {
     error_log("Erro PDO ao buscar chave API do usuário: " . $e->getMessage());
     http_response_code(500);
     echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao buscar chave API.', 'error_type' => 'db_error']);
     return;
} catch (\Exception $e) {
     error_log("Erro geral ao configurar API Gemini: " . $e->getMessage());
     http_response_code(500);
     echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao configurar a API.', 'error_type' => 'general_api_setup_error']);
     return;
}


// --- Processar os dados recebidos do AJAX via $_POST ---
// Estes são os dados enviados pelo FormData em main.js
$promptText = $_POST['prompt_text'] ?? '';
$theme = $_POST['theme'] ?? ''; // Exemplo: obtenha outros campos se necessário
$style = $_POST['style'] ?? '';
$tone = $_POST['tone'] ?? '';
$action_name = $_POST['action'] ?? 'call_gemini'; // Ação solicitada (deve ser 'call_gemini')

if (empty($promptText)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'O texto do prompt está vazio.', 'error_type' => 'empty_prompt']);
    return;
}

// Preparar o conteúdo para enviar para a API Gemini
// A API Gemini aceita um array de 'parts'. Pode ser só texto, ou texto + imagem.
$parts = [new TextPart($promptText)];
// TODO: Se seu formulário incluir upload de imagem, adicione aqui as ImageParts

// --- Chamar a API Gemini ---
$generatedText = '';
$modelUsed = 'gemini-1.5-pro'; // Defina o modelo a ser usado (ou pegue de um input/config)

try {
    // Usa o cliente instanciado para enviar o prompt para o modelo
    $response = $client->generativeModel($modelUsed)->generateContent(...$parts); // Usa splat operator (...) para passar o array parts

    // Obtém o texto gerado da resposta
    $generatedText = $response->text();

    // TODO: Salvar histórico na tabela prompts_history (assumindo que já existe)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO prompts_history (user_id, prompt_title, input_parameters, prompt_text, generated_text, model_used)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        // TODO: Obtenha um título se existir no formulário
        $prompt_title = $_POST['title'] ?? 'Prompt Gerado'; // Título opcional
        // Salva os parâmetros de input originais como JSON
        $input_params_json = json_encode([
            'theme' => $theme,
            'style' => $style,
            'tone' => $tone,
            // ... outros campos do formulário ...
        ]);
        $stmt->execute([
            $user_id,
            $prompt_title,
            $input_params_json, // Salva como JSON
            $promptText,
            $generatedText,
            $modelUsed
        ]);
         error_log("Histórico do prompt salvo com sucesso.");

    } catch (\PDOException $e) {
        // Loga o erro do banco, mas não necessariamente falha toda a requisição API
        error_log("ERRO ao salvar histórico do prompt no BD: " . $e->getMessage());
        // Você pode decidir se isso deve causar um erro 'success: false' na resposta JSON ou não
    }


    // --- Retornar a resposta da API Gemini em JSON ---
    echo json_encode([
        'success' => true,
        'message' => 'Prompt gerado com sucesso!',
        'prompt_text_sent' => $promptText, // Retorna o prompt enviado para referência
        'generated_text' => $generatedText,
        'model_used' => $modelUsed,
        'input_parameters' => json_decode($input_params_json, true) // Retorna os parâmetros de input (opcional)
    ]);

} catch (\Exception $e) {
    // Captura erros na chamada da API Gemini
    error_log("ERRO ao chamar API Gemini: " . $e->getMessage());

    // Tenta identificar o tipo de erro para retornar uma mensagem útil
    $errorMessage = 'Erro ao comunicar com a API Gemini.';
    $errorType = 'gemini_api_error';

    if (strpos($e->getMessage(), 'API key not valid') !== false) {
         $errorMessage = 'Sua chave API Gemini parece ser inválida.';
         $errorType = 'api_key_invalid';
    } elseif (strpos($e->getMessage(), 'Quota exceeded') !== false) {
         $errorMessage = 'Cota da API Gemini excedida. Verifique seu plano.';
         $errorType = 'quota_exceeded';
    }
    // TODO: Adicionar mais verificações de tipos de erro comuns da API

    http_response_code(500); // Internal Server Error ou um código mais específico se aplicável (e.g., 402 Payment Required para quota)
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'error_type' => $errorType,
        // Opcional: incluir detalhes do erro em ambiente de dev (NÃO em prod)
        // 'debug' => $e->getMessage()
    ]);
}

// Não chame exit() aqui
?>