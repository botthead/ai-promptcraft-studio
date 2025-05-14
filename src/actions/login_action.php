<?php
// src/actions/login_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // $pdo
require_once __DIR__ . '/../core/functions.php';   // redirect()

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Acesso inválido.";
    redirect(BASE_URL . 'login.php');
}

unset($_SESSION['form_data']);
unset($_SESSION['form_errors']);

$login_identifier = trim($_POST['login_identifier'] ?? '');
$password = $_POST['password'] ?? '';

$_SESSION['form_data'] = ['login_identifier' => $login_identifier];
$errors = [];

if (empty($login_identifier)) {
    $errors['login_identifier'] = "Nome de usuário ou email é obrigatório.";
}
if (empty($password)) {
    $errors['password'] = "A senha é obrigatória.";
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['error_message'] = "Por favor, preencha todos os campos.";
    redirect(BASE_URL . 'login.php');
}

try {
    // A query SQL permanece a mesma
    $sql = "SELECT id, username, email, password_hash FROM users WHERE username = :identifier OR email = :identifier_email"; // Alterado para placeholders distintos
    $stmt = $pdo->prepare($sql);

    // Vincule os valores no array do execute()
    // Isso garante que cada placeholder é explicitamente preenchido.
    $stmt->execute([
        ':identifier' => $login_identifier,
        ':identifier_email' => $login_identifier // Usamos o mesmo valor para ambos
    ]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // Guardar o email na sessão pode ser útil para o perfil
            $_SESSION['user_email'] = $user['email']; 
            $_SESSION['success_message'] = "Login realizado com sucesso!";
            
            unset($_SESSION['form_data']);
            unset($_SESSION['form_errors']);

            $redirect_to = $_SESSION['redirect_after_login'] ?? BASE_URL . 'dashboard.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect_to);
        } else {
            $_SESSION['error_message'] = "Nome de usuário/email ou senha inválidos.";
            redirect(BASE_URL . 'login.php');
        }
    } else {
        $_SESSION['error_message'] = "Nome de usuário/email ou senha inválidos.";
        redirect(BASE_URL . 'login.php');
    }

} catch (PDOException $e) {
    error_log("PDOException ao tentar login: " . $e->getMessage() . " Query: " . $sql); // Adiciona a query ao log
    $_SESSION['error_message'] = "Ocorreu um erro no servidor. Tente novamente.";
    redirect(BASE_URL . 'login.php');
}
?>