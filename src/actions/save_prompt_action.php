 
<?php
// src/actions/save_prompt_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // $pdo
require_once __DIR__ . '/../core/functions.php';   // redirect(), e()
require_once __DIR__ . '/../core/auth.php';       // require_login(), $_SESSION['user_id']

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'save_generated') {
    $_SESSION['error_message_generator'] = "Ação inválida ou acesso incorreto.";
    redirect(BASE_URL . 'generator.php');
}

$user_id = $_SESSION['user_id'];
$prompt_title = trim($_POST['save_title_override'] ?? $_POST['prompt_title_to_save'] ?? 'Prompt Sem Título');
$input_parameters_json = $_POST['input_parameters_json'] ?? '{}'; // Deve ser um JSON válido
$generated_prompt_text = $_POST['generated_prompt_text_to_save'] ?? '';

// Validação básica
if (empty($generated_prompt_text)) {
    $_SESSION['error_message_generator'] = "Não há prompt gerado para salvar.";
    redirect(BASE_URL . 'generator.php');
}

// Decodificar o JSON para verificar se é válido (opcional, mas bom)
$input_parameters_array = json_decode($input_parameters_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $_SESSION['error_message_generator'] = "Erro nos parâmetros de entrada do prompt.";
    error_log("Erro ao decodificar JSON em save_prompt_action: " . json_last_error_msg() . " JSON: " . $input_parameters_json);
    redirect(BASE_URL . 'generator.php');
}
// Se o título não veio do override, pega do array de parâmetros
if (empty(trim($_POST['save_title_override'] ?? '')) && !empty($input_parameters_array['prompt_title'])) {
    $prompt_title = trim($input_parameters_array['prompt_title']);
}
if (empty($prompt_title)) { // Garante que não seja vazio
    $prompt_title = 'Prompt Sem Título - ' . date('Y-m-d H:i');
}


try {
    $sql = "INSERT INTO prompts (user_id, title, input_parameters, generated_prompt_text) 
            VALUES (:user_id, :title, :input_parameters, :generated_prompt_text)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $prompt_title);
    $stmt->bindParam(':input_parameters', $input_parameters_json); // Salva como JSON string
    $stmt->bindParam(':generated_prompt_text', $generated_prompt_text);

    if ($stmt->execute()) {
        $_SESSION['success_message_generator'] = "Prompt salvo com sucesso no histórico!";
        // Limpar o prompt gerado da sessão para não reaparecer se ele voltar para o gerador
        unset($_SESSION['generated_prompt_text']);
        unset($_SESSION['last_input_parameters']);
        redirect(BASE_URL . 'history.php'); // Redireciona para o histórico para ver o prompt salvo
    } else {
        $_SESSION['error_message_generator'] = "Erro ao salvar o prompt. Tente novamente.";
        error_log("Erro SQL ao salvar prompt para user_id {$user_id}");
        redirect(BASE_URL . 'generator.php');
    }

} catch (PDOException $e) {
    error_log("PDOException ao salvar prompt: " . $e->getMessage());
    $_SESSION['error_message_generator'] = "Erro no servidor ao salvar o prompt. Tente novamente.";
    redirect(BASE_URL . 'generator.php');
}
?>