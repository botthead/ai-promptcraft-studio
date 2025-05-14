 
<?php
// src/actions/register_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui os arquivos necessários
// O caminho para src/config/database.php a partir de src/actions/register_action.php é ../config/database.php
require_once __DIR__ . '/../config/database.php'; // Fornece $pdo
require_once __DIR__ . '/../core/functions.php';   // Fornece e() e redirect()

// Garante que este script só seja acessado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Acesso inválido.";
    redirect(BASE_URL . 'register.php'); // BASE_URL já tem /public/ no final se configurado corretamente
}

// Limpar dados de formulário e erros anteriores da sessão
unset($_SESSION['form_data']);
unset($_SESSION['form_errors']);

// Coleta dos dados do formulário
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$terms_accepted = isset($_POST['terms']);

// Armazenar os dados submetidos para preencher o formulário em caso de erro
$_SESSION['form_data'] = [
    'username' => $username,
    'email' => $email
    // Não armazenar senhas na sessão
];

$errors = [];

// Validações
// Nome de usuário
if (empty($username)) {
    $errors['username'] = "O nome de usuário é obrigatório.";
} elseif (strlen($username) < 3 || strlen($username) > 50) {
    $errors['username'] = "O nome de usuário deve ter entre 3 e 50 caracteres.";
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors['username'] = "O nome de usuário pode conter apenas letras, números e underscores (_).";
} else {
    // Verificar se o nome de usuário já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    if ($stmt->fetch()) {
        $errors['username'] = "Este nome de usuário já está em uso.";
    }
}

// Email
if (empty($email)) {
    $errors['email'] = "O email é obrigatório.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Formato de email inválido.";
} else {
    // Verificar se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        $errors['email'] = "Este email já está registrado.";
    }
}

// Senha
if (empty($password)) {
    $errors['password'] = "A senha é obrigatória.";
} elseif (strlen($password) < 8) {
    $errors['password'] = "A senha deve ter pelo menos 8 caracteres.";
} elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $errors['password'] = "A senha deve conter letras e números.";
}


// Confirmar Senha
if (empty($confirm_password)) {
    $errors['confirm_password'] = "A confirmação de senha é obrigatória.";
} elseif ($password !== $confirm_password) {
    $errors['confirm_password'] = "As senhas não coincidem.";
}

// Termos
if (!$terms_accepted) {
    $errors['terms'] = "Você deve aceitar os termos de uso.";
}

// Se houver erros, redireciona de volta para o formulário de registro
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['error_message'] = "Por favor, corrija os erros abaixo.";
    redirect(BASE_URL . 'register.php');
}

// Se não houver erros, prossiga com a inserção no banco de dados
try {
    // Hash da senha
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Preparar a query SQL
    $sql = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";
    $stmt = $pdo->prepare($sql);

    // Bind dos parâmetros
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password_hash);

    // Executar a query
    if ($stmt->execute()) {
        // Sucesso! Limpar dados do formulário da sessão e redirecionar para login com mensagem de sucesso
        unset($_SESSION['form_data']);
        $_SESSION['success_message'] = "Conta criada com sucesso! Você já pode fazer login.";
        redirect(BASE_URL . 'login.php');
    } else {
        // Erro ao executar a query
        $_SESSION['error_message'] = "Ocorreu um erro ao criar sua conta. Tente novamente.";
        error_log("Erro ao registrar usuário: Falha na execução do statement INSERT."); // Log do erro no servidor
        redirect(BASE_URL . 'register.php');
    }

} catch (PDOException $e) {
    // Erro de PDO (ex: violação de constraint UNIQUE que não foi pega antes, problema de conexão)
    error_log("PDOException ao registrar usuário: " . $e->getMessage()); // Log do erro no servidor
    $_SESSION['error_message'] = "Ocorreu um erro inesperado no servidor. Tente novamente mais tarde.";
    // Poderia verificar $e->errorInfo[1] para códigos de erro específicos do MySQL (ex: 1062 para duplicidade)
    if ($e->errorInfo[1] == 1062) { // Código de erro para entrada duplicada
         $_SESSION['error_message'] = "Nome de usuário ou e-mail já existem.";
    }
    redirect(BASE_URL . 'register.php');
}
?>