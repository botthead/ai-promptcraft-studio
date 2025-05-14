<?php
// public/api_handler.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . '/'); 
}

$action_file = BASE_PATH . 'src/actions/call_gemini_api_action.php';

if (file_exists($action_file)) {
    require_once $action_file;
} else {
    header('Content-Type: application/json');
    http_response_code(404); 
    echo json_encode([
        'success' => false,
        'error' => 'Endpoint de API não encontrado (ação interna não localizada).'
    ]);
    exit;
}
?>