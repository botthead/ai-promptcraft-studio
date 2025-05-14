<?php
// src/actions/update_profile_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ORDEM CORRETA E COMPLETA DOS INCLUDES:
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/auth.php';

require_login();


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    // Se não for POST ou não tiver 'action', define mensagem global e redireciona
    $_SESSION['global_error_message'] = "Acesso inválido à página de perfil.";
    redirect(BASE_URL . 'profile.php');
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'];

unset($_SESSION['form_errors_profile']); // Limpa erros de formulário anteriores
unset($_SESSION['profile_success_message']); // Limpa mensagens de sucesso anteriores
unset($_SESSION['profile_error_message']);   // Limpa mensagens de erro anteriores

// --- Ação: Atualizar Chave API ---
if ($action === 'update_api_key') {
    $gemini_api_key = trim($_POST['gemini_api_key'] ?? '');

    if (empty($gemini_api_key)) {
        $_SESSION['profile_success_message'] = "Nenhuma alteração na Chave API foi submetida. A chave existente foi mantida.";
        redirect(BASE_URL . 'profile.php');
    }

    // Validação básica da chave API (Exemplo: mínimo de 20 caracteres)
    if (strlen($gemini_api_key) < 20) { // Ajuste conforme o formato real da chave Gemini
        $_SESSION['form_errors_profile']['gemini_api_key'] = "A chave API parece muito curta. Verifique se está correta.";
        $_SESSION['profile_error_message'] = "Por favor, corrija os erros abaixo.";
        redirect(BASE_URL . 'profile.php');
    }

    $encrypted_key = encrypt_data($gemini_api_key);

    if ($encrypted_key === false) {
        $_SESSION['profile_error_message'] = "Erro crítico: Falha ao proteger sua chave API. Verifique as configurações de criptografia do servidor.";
        error_log("Falha ao criptografar API key para user_id {$user_id}. Verifique ENCRYPTION_KEY e ENCRYPTION_IV_KEY.");
        redirect(BASE_URL . 'profile.php');
    }

    try {
        $sql = "UPDATE users SET gemini_api_key_encrypted = :api_key WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':api_key', $encrypted_key);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['profile_success_message'] = "Chave API do Gemini atualizada com sucesso!";
        } else {
            $_SESSION['profile_error_message'] = "Erro ao atualizar sua chave API no banco de dados. Tente novamente.";
            error_log("Erro SQL ao atualizar API key para user_id {$user_id}.");
        }
    } catch (PDOException $e) {
        error_log("PDOException ao atualizar API key para user_id {$user_id}: " . $e->getMessage());
        $_SESSION['profile_error_message'] = "Erro no servidor ao atualizar sua chave API. Tente novamente mais tarde.";
    }
    redirect(BASE_URL . 'profile.php');

// --- Ação: Remover Chave API ---
} elseif ($action === 'remove_api_key') {
    try {
        $sql = "UPDATE users SET gemini_api_key_encrypted = NULL WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['profile_success_message'] = "Chave API do Gemini removida com sucesso!";
        } else {
            $_SESSION['profile_error_message'] = "Erro ao remover sua chave API. Tente novamente.";
            error_log("Erro SQL ao remover API key para user_id {$user_id}.");
        }
    } catch (PDOException $e) {
        error_log("PDOException ao remover API key para user_id {$user_id}: " . $e->getMessage());
        $_SESSION['profile_error_message'] = "Erro no servidor ao remover sua chave API.";
    }
    redirect(BASE_URL . 'profile.php');
    
// --- Ação: Atualizar Senha ---
} elseif ($action === 'update_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $errors = [];

    if (empty($current_password)) $errors['current_password'] = "Senha atual é obrigatória.";
    
    if (empty($new_password)) {
        $errors['new_password'] = "Nova senha é obrigatória.";
    } elseif (strlen($new_password) < 8) {
        $errors['new_password'] = "Nova senha deve ter pelo menos 8 caracteres.";
    } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $errors['new_password'] = "Nova senha deve conter letras e números.";
    }
    
    if (empty($confirm_new_password)) {
        $errors['confirm_new_password'] = "Confirmação da nova senha é obrigatória.";
    } elseif ($new_password !== $confirm_new_password) {
        $errors['confirm_new_password'] = "As novas senhas não coincidem.";
    }

    if (!empty($errors)) {
        $_SESSION['form_errors_profile'] = $errors;
        $_SESSION['profile_error_message'] = "Por favor, corrija os erros no formulário de alteração de senha.";
        redirect(BASE_URL . 'profile.php');
    }

    try {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_password, $user['password_hash'])) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update_pass = "UPDATE users SET password_hash = :new_password_hash WHERE id = :user_id";
            $stmt_update_pass = $pdo->prepare($sql_update_pass);
            $stmt_update_pass->bindParam(':new_password_hash', $new_password_hash);
            $stmt_update_pass->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt_update_pass->execute()) {
                $_SESSION['profile_success_message'] = "Senha alterada com sucesso! Por favor, faça login novamente com sua nova senha.";
                // Forçar logout para que o usuário tenha que logar com a nova senha
                // Limpar todas as variáveis de sessão
                $_SESSION = array();
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
                session_start(); // Inicia nova sessão para a mensagem
                $_SESSION['global_success_message'] = "Senha alterada com sucesso! Por favor, faça login novamente.";
                redirect(BASE_URL . 'login.php');
            } else {
                $_SESSION['profile_error_message'] = "Erro ao alterar sua senha no banco de dados. Tente novamente.";
                 error_log("Erro SQL ao atualizar senha para user_id {$user_id}.");
                 redirect(BASE_URL . 'profile.php');
            }
        } else {
            $_SESSION['form_errors_profile']['current_password'] = "Senha atual incorreta.";
            $_SESSION['profile_error_message'] = "Senha atual incorreta. Verifique e tente novamente.";
            redirect(BASE_URL . 'profile.php');
        }

    } catch (PDOException $e) {
        error_log("PDOException ao atualizar senha para user_id {$user_id}: " . $e->getMessage());
        $_SESSION['profile_error_message'] = "Erro no servidor ao alterar sua senha. Tente novamente mais tarde.";
        redirect(BASE_URL . 'profile.php');
    }

} else {
    $_SESSION['global_error_message'] = "Ação de perfil desconhecida ou inválida.";
    redirect(BASE_URL . 'profile.php');
}
?>