<?php
// src/actions/call_gemini_api_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/auth.php';

function send_json_response($success, $data = [], $error_message = '') {
    header('Content-Type: application/json');
    // Limpar qualquer buffer de saída pendente para evitar quebras no JSON
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error_message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(false, [], "Método de requisição inválido.");
}

if (!is_logged_in()) {
    send_json_response(false, [], "Usuário não autenticado. Por favor, faça login.");
}

$user_id = $_SESSION['user_id'];
$prompt_text = trim($_POST['prompt_text_to_gemini'] ?? '');

if (empty($prompt_text)) {
    send_json_response(false, [], "O texto do prompt não pode estar vazio.");
}

$api_key = null;
$gemini_error_message_from_call = null; 

try {
    $stmt_key = $pdo->prepare("SELECT gemini_api_key_encrypted FROM users WHERE id = :user_id");
    $stmt_key->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_key->execute();
    $user_key_info = $stmt_key->fetch(PDO::FETCH_ASSOC);

    if ($user_key_info && !empty($user_key_info['gemini_api_key_encrypted'])) {
        $decrypted_key = decrypt_data($user_key_info['gemini_api_key_encrypted']);
        if ($decrypted_key) {
            $api_key = $decrypted_key;
        } else {
            $gemini_error_message_from_call = "Falha ao acessar sua chave API do Gemini. Verifique as configurações ou reconfigure sua chave.";
            error_log("Falha ao descriptografar API Key do Gemini para user_id {$user_id}.");
            send_json_response(false, [], $gemini_error_message_from_call);
        }
    } else {
        $gemini_error_message_from_call = "Chave API do Gemini não configurada. Por favor, configure-a no seu perfil.";
        send_json_response(false, [], $gemini_error_message_from_call);
    }
} catch (PDOException $e) {
    $gemini_error_message_from_call = "Erro ao buscar sua configuração de API Key.";
    error_log("PDOException ao buscar API Key do Gemini para user_id {$user_id}: " . $e->getMessage());
    send_json_response(false, [], $gemini_error_message_from_call);
}

if (!$api_key) {
    send_json_response(false, [], "Chave API do Gemini indisponível (erro interno).");
}

$gemini_api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=" . $api_key;
$request_body_array = [ "contents" => [ [ "parts" => [ ["text" => $prompt_text] ] ] ] ];
$json_request_body = json_encode($request_body_array);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $gemini_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request_body);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); 

$api_response_raw = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error_msg = curl_error($ch);
curl_close($ch);

$gemini_api_response_text = null;

if ($curl_error_msg) {
    $gemini_error_message_from_call = "Erro na comunicação com a API Gemini (cURL): " . e($curl_error_msg);
    error_log("cURL Error para Gemini API (user_id {$user_id}): " . $curl_error_msg);
} elseif ($api_response_raw) {
    $api_response_data = json_decode($api_response_raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $gemini_error_message_from_call = "Erro ao decodificar resposta JSON da API Gemini.";
        error_log("JSON Decode Error para Gemini API (user_id {$user_id}): " . json_last_error_msg() . " Raw: " . $api_response_raw);
    } elseif ($http_code >= 200 && $http_code < 300) {
        if (isset($api_response_data['candidates'][0]['content']['parts'][0]['text'])) {
            $gemini_api_response_text = $api_response_data['candidates'][0]['content']['parts'][0]['text'];
            $_SESSION['last_gemini_api_response_for_save'] = $gemini_api_response_text; // Salva para o formulário de save
        } elseif (isset($api_response_data['promptFeedback']['blockReason'])) {
            $block_reason = $api_response_data['promptFeedback']['blockReason'];
            $gemini_error_message_from_call = "A API Gemini bloqueou a resposta. Razão: " . e($block_reason);
            error_log("Gemini API content blocked (user_id {$user_id}): Reason: {$block_reason}");
        } else {
            $gemini_error_message_from_call = "Resposta da API Gemini recebida, mas em formato inesperado.";
            error_log("Resposta inesperada da Gemini API (user_id {$user_id}): " . $api_response_raw);
        }
    } else { // Erro HTTP da API
        $error_message = "Erro da API Gemini (HTTP " . $http_code . "): ";
        if (isset($api_response_data['error']['message'])) {
            $error_message .= e($api_response_data['error']['message']);
        } else {
            $error_message .= "Resposta de erro não detalhada.";
        }
        $gemini_error_message_from_call = $error_message;
        error_log("Erro da API Gemini (user_id {$user_id}, HTTP {$http_code}): " . $api_response_raw);
    }
} else {
    $gemini_error_message_from_call = "Nenhuma resposta recebida da API Gemini (HTTP " . $http_code . ").";
    error_log("Nenhuma resposta da Gemini API (user_id {$user_id}, HTTP {$http_code})");
}

if ($gemini_api_response_text !== null) {
    send_json_response(true, ['text' => $gemini_api_response_text]);
} else {
    send_json_response(false, [], $gemini_error_message_from_call ?: "Erro desconhecido ao processar a requisição para a API Gemini.");
}
?>