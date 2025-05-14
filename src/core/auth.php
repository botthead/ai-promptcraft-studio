 
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php'; // Para acesso ao $pdo se necessário no futuro
require_once __DIR__ . '/functions.php';

/**
 * Verifica se o usuário está logado.
 * @return bool True se logado, false caso contrário.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Requer que o usuário esteja logado. Se não, redireciona para a página de login.
 * @param string $redirect_url URL para redirecionar após o login (opcional).
 */
function require_login($redirect_url = null) {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $redirect_url ?? $_SERVER['REQUEST_URI'];
        $_SESSION['error_message'] = "Você precisa estar logado para acessar esta página.";
        redirect(BASE_URL . 'login.php');
    }
}

/**
 * Requer que o usuário NÃO esteja logado (para páginas de login/registro).
 * Se estiver logado, redireciona para o dashboard.
 */
function require_guest() {
    if (is_logged_in()) {
        redirect(BASE_URL . 'dashboard.php');
    }
}
?>