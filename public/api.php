<?php
// public/api.php
// Este arquivo age como um roteador/entry point para todas as requisições AJAX de API

// Inicie a sessão (necessário para autenticação e dados do usuário)
session_start();

// Inclua arquivos essenciais
require_once __DIR__ . '/../src/Config/Constants.php'; // Para BASE_URL, etc.
require_once __DIR__ . '/../src/Config/Database.php'; // Para $pdo, se necessário pelas actions
require_once __DIR__ . '/../src/Core/functions.php';   // Funções utilitárias (e, nl2br etc.)
require_once __DIR__ . '/../src/Core/auth_check.php'; // Verifica se o usuário está logado (ou lida com não logados)

// Define o cabeçalho para indicar que a resposta será JSON
header('Content-Type: application/json');

// Verifica se a requisição é POST (API geralmente usa POST, PUT, DELETE)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método não permitido.', 'error_type' => 'method_not_allowed']);
    exit;
}

// Verifica se o usuário está autenticado ANTES de incluir qualquer script de ação protegido
// auth_check.php pode redirecionar para páginas HTML, mas para API queremos retornar JSON 401.
// Ajuste auth_check.php ou adicione uma checagem aqui:
if (!isset($_SESSION['user_id'])) {
     http_response_code(401); // Unauthorized
     echo json_encode(['success' => false, 'message' => 'Autenticação necessária.', 'error_type' => 'auth_required']);
     exit;
}
// Se chegou aqui, o usuário está autenticado e podemos prosseguir

// Obtém a 'action' (ação solicitada) do corpo da requisição POST
// Usamos REQUEST para cobrir POST/GET, mas API geralmente só usa POST.
$action = $_REQUEST['action'] ?? ''; // Use $_POST se quiser garantir que seja POST

// Roteamento simples baseado na 'action'
switch ($action) {
    case 'call_gemini':
        // Inclui o script PHP que contém a lógica para chamar a API Gemini
        require __DIR__ . '/../src/Actions/call_gemini_api_action.php';
        // O script incluído (`call_gemini_api_action.php`)
        // DEVE processar os dados de $_POST, chamar a API Gemini
        // e enviar a resposta final no formato JSON usando echo json_encode(...);
        // Ele NÃO deve chamar exit; ou die(); a menos que haja um erro CRÍTICO.
        break;

    // TODO: Adicione outros casos para futuras ações de API aqui (ex: save_prompt)
    case 'save_prompt_api': // Exemplo: uma action separada para salvar via API
         require __DIR__ . '/../src/Actions/save_prompt_action.php'; // Use o script de ação existente ou crie um específico para API
         break;
    // ... outras actions ...

    default:
        // Ação desconhecida ou não fornecida
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Ação de API desconhecida ou faltando.', 'error_type' => 'invalid_action']);
        exit; // Interrompe a execução após enviar o erro
}

// Se o script incluído não chamou exit(), a execução continua,
// mas assume-se que ele já enviou a resposta JSON.
?>