 
<?php
// src/actions/logout_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php'; // Para BASE_URL
require_once __DIR__ . '/../core/functions.php';   // Para redirect()

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Se usar cookies de sessão, também é bom removê-los
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir a sessão.
session_destroy();

// Redirecionar para a página de login ou inicial com uma mensagem (opcional)
// session_start(); // Reinicia a sessão para poder passar a mensagem
// $_SESSION['success_message'] = "Você saiu com sucesso.";
redirect(BASE_URL . 'login.php'); // Ou BASE_URL . 'index.php'
?>