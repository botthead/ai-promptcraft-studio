 
<?php
// src/actions/delete_prompt_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // $pdo
require_once __DIR__ . '/../core/functions.php';   // redirect()
require_once __DIR__ . '/../core/auth.php';       // require_login(), $_SESSION['user_id']

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['prompt_id_to_delete'])) {
    $_SESSION['error_message_history'] = "Ação inválida ou ID do prompt não fornecido.";
    redirect(BASE_URL . 'history.php');
}

$prompt_id = filter_var($_POST['prompt_id_to_delete'], FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if ($prompt_id === false || $prompt_id <= 0) {
    $_SESSION['error_message_history'] = "ID do prompt inválido.";
    redirect(BASE_URL . 'history.php');
}

try {
    // Verifica se o prompt pertence ao usuário logado antes de excluir (IMPORTANTE!)
    $sql_check = "SELECT id FROM prompts WHERE id = :prompt_id AND user_id = :user_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':prompt_id', $prompt_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->execute();

    if ($stmt_check->fetch()) {
        // O prompt pertence ao usuário, pode excluir
        $sql_delete = "DELETE FROM prompts WHERE id = :prompt_id AND user_id = :user_id_del"; // Redundância de user_id por segurança
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->bindParam(':prompt_id', $prompt_id, PDO::PARAM_INT);
        $stmt_delete->bindParam(':user_id_del', $user_id, PDO::PARAM_INT); // Para a cláusula WHERE

        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                $_SESSION['success_message_history'] = "Prompt excluído com sucesso!";
            } else {
                // Isso não deveria acontecer se a verificação anterior passou, mas é uma salvaguarda
                $_SESSION['error_message_history'] = "Não foi possível excluir o prompt ou ele já foi removido.";
            }
        } else {
            $_SESSION['error_message_history'] = "Erro ao tentar excluir o prompt. Tente novamente.";
            error_log("Erro SQL ao excluir prompt_id {$prompt_id} para user_id {$user_id}");
        }
    } else {
        // O prompt não pertence ao usuário ou não existe
        $_SESSION['error_message_history'] = "Você não tem permissão para excluir este prompt ou ele não existe.";
    }

} catch (PDOException $e) {
    error_log("PDOException ao excluir prompt: " . $e->getMessage());
    $_SESSION['error_message_history'] = "Erro no servidor ao excluir o prompt. Tente novamente.";
}

redirect(BASE_URL . 'history.php');
?>