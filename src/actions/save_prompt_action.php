<?php
// src/actions/save_prompt_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ORDEM CORRETA DOS INCLUDES
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../config/database.php'; // $pdo
require_once __DIR__ . '/../core/auth.php';       // require_login(), $_SESSION['user_id']

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'save_generated') {
    $_SESSION['error_message_generator'] = "Ação inválida ou acesso incorreto para salvar o prompt.";
    redirect(BASE_URL . 'generator.php');
}

$user_id = $_SESSION['user_id'];

// Título: prioriza o override, depois o título dos parâmetros, depois um placeholder
$prompt_title_override = trim($_POST['save_title_override'] ?? '');
$original_prompt_title_from_params = '';

$input_parameters_json = $_POST['input_parameters_json'] ?? '{}';
$input_parameters_array = json_decode($input_parameters_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $_SESSION['error_message_generator'] = "Erro nos parâmetros de entrada do prompt ao tentar salvar (JSON inválido).";
    error_log("Erro ao decodificar JSON em save_prompt_action para user_id {$user_id}: " . json_last_error_msg() . " JSON: " . $input_parameters_json);
    redirect(BASE_URL . 'generator.php');
}

if (is_array($input_parameters_array) && isset($input_parameters_array['prompt_title'])) {
    $original_prompt_title_from_params = trim($input_parameters_array['prompt_title']);
}

$prompt_title = !empty($prompt_title_override) ? 
                $prompt_title_override : 
                (!empty($original_prompt_title_from_params) ? 
                 $original_prompt_title_from_params : 
                 'Prompt Sem Título - ' . date('Y-m-d H:i'));

$generated_prompt_text = $_POST['generated_prompt_text_to_save'] ?? '';
$gemini_response_to_save = trim($_POST['gemini_response_to_save'] ?? ''); // Captura a resposta da API

// Validação básica
if (empty($generated_prompt_text)) {
    $_SESSION['error_message_generator'] = "Não há prompt gerado para salvar.";
    redirect(BASE_URL . 'generator.php');
}

try {
    $sql = "INSERT INTO prompts (user_id, title, input_parameters, generated_prompt_text, gemini_response) 
            VALUES (:user_id, :title, :input_parameters, :generated_prompt_text, :gemini_response)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $prompt_title);
    $stmt->bindParam(':input_parameters', $input_parameters_json); // Salva como JSON string
    $stmt->bindParam(':generated_prompt_text', $generated_prompt_text);
    
    if (empty($gemini_response_to_save)) {
        $stmt->bindValue(':gemini_response', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindParam(':gemini_response', $gemini_response_to_save);
    }

    if ($stmt->execute()) {
        $_SESSION['global_success_message'] = "Prompt salvo com sucesso no histórico!";
        
        // Limpar da sessão os dados do prompt que acabou de ser salvo
        if (isset($_SESSION['generated_prompt_text'])) unset($_SESSION['generated_prompt_text']);
        if (isset($_SESSION['last_input_parameters'])) unset($_SESSION['last_input_parameters']);
        // Limpar a resposta da API que estava guardada para este salvamento (se houver)
        // A variável de sessão $_SESSION['last_gemini_api_response_for_save'] foi uma ideia,
        // mas como estamos pegando de $_POST['gemini_response_to_save'], ela não é estritamente necessária aqui.
        // No entanto, se a definimos em call_gemini_api_action.php, podemos limpá-la.
        if (isset($_SESSION['last_gemini_api_response_for_save'])) unset($_SESSION['last_gemini_api_response_for_save']);


        redirect(BASE_URL . 'history.php'); 
    } else {
        $_SESSION['error_message_generator'] = "Erro ao salvar o prompt no banco de dados. Tente novamente.";
        error_log("Erro SQL ao salvar prompt para user_id {$user_id}. Detalhes do PDOStatement: " . print_r($stmt->errorInfo(), true));
        redirect(BASE_URL . 'generator.php');
    }

} catch (PDOException $e) {
    error_log("PDOException ao salvar prompt para user_id {$user_id}: " . $e->getMessage());
    $_SESSION['error_message_generator'] = "Erro no servidor ao processar o salvamento do prompt. Tente novamente.";
    redirect(BASE_URL . 'generator.php');
}
?>