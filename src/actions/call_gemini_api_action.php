<?php
// src/actions/call_gemini_api_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
error_log("--- Iniciando call_gemini_api_action.php ---");

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/auth.php';

require_login();

error_log("POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['prompt_text_to_gemini'])) {
    error_log("Validação inicial falhou: Método não é POST ou prompt_text_to_gemini não está setado.");
    $_SESSION['gemini_api_error'] = "Requisição inválida para a API Gemini.";
    redirect(BASE_URL . 'generator.php');
}

$user_id = $_SESSION['user_id'];
$prompt_text = trim($_POST['prompt_text_to_gemini']);
error_log("Prompt recebido: " . $prompt_text);

if (empty($prompt_text)) {
    error_log("Prompt vazio.");
    $_SESSION['gemini_api_error'] = "O texto do prompt não pode estar vazio.";
    redirect(BASE_URL . 'generator.php');
}

unset($_SESSION['gemini_api_response']);
unset($_SESSION['gemini_api_error']);

$api_key = null;
try {
    $stmt_key = $pdo->prepare("SELECT gemini_api_key_encrypted FROM users WHERE id = :user_id");
    $stmt_key->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_key->execute();
    $user_key_info = $stmt_key->fetch(PDO::FETCH_ASSOC);

    if ($user_key_info && !empty($user_key_info['gemini_api_key_encrypted'])) {
        error_log("API Key Criptografada encontrada: " . substr($user_key_info['gemini_api_key_encrypted'], 0, 10) . "...");
        $decrypted_key = decrypt_data($user_key_info['gemini_api_key_encrypted']);
        if ($decrypted_key) {
            $api_key = $decrypted_key;
            error_log("API Key Descriptografada com sucesso.");
        } else {
            error_log("Falha ao descriptografar API Key para user_id {$user_id}.");
            $_SESSION['gemini_api_error'] = "Falha ao acessar sua chave API do Gemini. Verifique as configurações de criptografia ou reconfigure sua chave no perfil.";
            redirect(BASE_URL . 'generator.php');
        }
    } else {
        error_log("API Key não configurada para user_id {$user_id}.");
        $_SESSION['gemini_api_error'] = "Chave API do Gemini não configurada. Por favor, configure-a no seu perfil.";
        redirect(BASE_URL . 'generator.php');
    }

} catch (PDOException $e) {
    error_log("PDOException ao buscar API Key: " . $e->getMessage());
    $_SESSION['gemini_api_error'] = "Erro ao buscar sua configuração de API Key.";
    redirect(BASE_URL . 'generator.php');
}

if (!$api_key) {
    error_log("API Key indisponível após tentativa de descriptografia (dupla checagem).");
    $_SESSION['gemini_api_error'] = "Chave API do Gemini indisponível (erro interno).";
    redirect(BASE_URL . 'generator.php');
}

error_log("API Key a ser usada (início): " . substr($api_key, 0, 5) . "...");

// 4. Preparar e Enviar Requisição para a API Gemini (usando cURL)
// ESTE BLOCO ESTÁ CORRETAMENTE POSICIONADO AQUI:
$gemini_api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=" . $api_key;

$request_body = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt_text]
            ]
        ]
    ],
    // "generationConfig" => [
    //   "temperature" => 0.9,
    //   "maxOutputTokens" => 2048,
    // ]
];
$json_request_body = json_encode($request_body);

error_log("Preparando chamada cURL para (URL sem a chave completa para segurança do log): " . explode('?key=', $gemini_api_url)[0] . "?key=SUA_CHAVE_AQUI");
error_log("Corpo da requisição cURL: " . $json_request_body);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $gemini_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request_body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);

$api_response_raw = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

error_log("cURL HTTP Code: " . $http_code);
error_log("cURL Error: " . $curl_error);
if (strlen($api_response_raw) < 1000) {
    error_log("cURL Raw Response: " . $api_response_raw);
} else {
    error_log("cURL Raw Response (início): " . substr($api_response_raw, 0, 200) . "...");
}

// 5. Processar Resposta da API
if ($curl_error) {
    $_SESSION['gemini_api_error'] = "Erro na comunicação com a API Gemini (cURL): " . e($curl_error);
    error_log("cURL Error para Gemini API (user_id {$user_id}): " . $curl_error);
} elseif ($api_response_raw) {
    $api_response_data = json_decode($api_response_raw, true);

    if ($http_code >= 200 && $http_code < 300) {
        if (isset($api_response_data['candidates'][0]['content']['parts'][0]['text'])) {
            $_SESSION['gemini_api_response'] = $api_response_data['candidates'][0]['content']['parts'][0]['text'];
            error_log("Resposta Gemini OK (início): " . substr($_SESSION['gemini_api_response'], 0, 100) . "...");
        } elseif (isset($api_response_data['promptFeedback']['blockReason'])) {
            $block_reason = $api_response_data['promptFeedback']['blockReason'];
            $safety_ratings_info = "";
            if(isset($api_response_data['promptFeedback']['safetyRatings'])){
                foreach($api_response_data['promptFeedback']['safetyRatings'] as $rating){
                    $safety_ratings_info .= $rating['category'] . ": " . $rating['probability'] . "; ";
                }
            }
            $_SESSION['gemini_api_error'] = "A API Gemini bloqueou a resposta. Razão: " . e($block_reason) . ". Detalhes: " . e(rtrim($safety_ratings_info, '; '));
            error_log("Gemini API content blocked (user_id {$user_id}): Reason: {$block_reason}. Ratings: {$safety_ratings_info}");
        } else {
            $_SESSION['gemini_api_error'] = "Resposta da API Gemini recebida, mas em formato inesperado.";
            error_log("Resposta inesperada da Gemini API (user_id {$user_id}): " . $api_response_raw);
        }
    } else {
        $error_message = "Erro da API Gemini (HTTP " . $http_code . "): ";
        if (isset($api_response_data['error']['message'])) {
            $error_message .= e($api_response_data['error']['message']);
        } else {
            $error_message .= "Resposta de erro não detalhada.";
        }
        $_SESSION['gemini_api_error'] = $error_message;
        error_log("Erro da API Gemini (user_id {$user_id}, HTTP {$http_code}): " . $api_response_raw);
    }
} else {
    $_SESSION['gemini_api_error'] = "Nenhuma resposta recebida da API Gemini (HTTP " . $http_code . "). Verifique a conexão ou o endpoint da API.";
    error_log("Nenhuma resposta da Gemini API (user_id {$user_id}, HTTP {$http_code})");
}

// 6. Redirecionar de volta para generator.php
error_log("Sessão antes do redirect em call_gemini_api_action: " . print_r($_SESSION, true));
redirect(BASE_URL . 'generator.php');
?>