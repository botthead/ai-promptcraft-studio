 
<?php
// public/login.php
$page_title = "Login";

require_once __DIR__ . '/../src/core/auth.php'; // Para require_guest()
require_guest(); // Se o usuário já estiver logado, redireciona para o dashboard

require_once __DIR__ . '/../src/templates/header.php';
?>

<article>
    <header>
        <h2><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h2>
    </header>

    <?php
    // Exibir mensagens de erro ou sucesso da sessão, se houver
    if (isset($_SESSION['error_message'])) {
        echo '<p class="error-message" style="color: red;">' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') . '</p>';
        unset($_SESSION['error_message']);
    }
    if (isset($_SESSION['success_message'])) {
        echo '<p class="success-message" style="color: green;">' . htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') . '</p>';
        unset($_SESSION['success_message']);
    }
    ?>

    <form action="<?php echo BASE_URL; ?>../src/actions/login_action.php" method="POST">
        <!-- Campo Nome de Usuário ou Email -->
        <label for="login_identifier">Nome de Usuário ou Email:</label>
        <input type="text" id="login_identifier" name="login_identifier" required
               value="<?php echo isset($_SESSION['form_data']['login_identifier']) ? htmlspecialchars($_SESSION['form_data']['login_identifier'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        <?php if (isset($_SESSION['form_errors']['login_identifier'])): ?>
            <small style="color: red;"><?php echo htmlspecialchars($_SESSION['form_errors']['login_identifier'], ENT_QUOTES, 'UTF-8'); ?></small>
        <?php endif; ?>

        <!-- Campo Senha -->
        <label for="password">Senha:</label>
        <input type="password" id="password" name="password" required>
        <?php if (isset($_SESSION['form_errors']['password'])): ?>
            <small style="color: red;"><?php echo htmlspecialchars($_SESSION['form_errors']['password'], ENT_QUOTES, 'UTF-8'); ?></small>
        <?php endif; ?>

        <!-- (Opcional) Lembrar-me -->
        <!-- <label for="remember_me">
            <input type="checkbox" id="remember_me" name="remember_me">
            Lembrar-me
        </label> -->
        
        <button type="submit">Login</button>
    </form>

    <p style="margin-top: 1rem;">
        Não tem uma conta? <a href="<?php echo BASE_URL; ?>register.php">Registre-se aqui</a>.
    </p>
    <!-- <p>
        <a href="#">Esqueceu sua senha?</a> (Funcionalidade futura)
    </p> -->
</article>

<?php
// Limpar dados de formulário e erros da sessão após exibi-los
if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']);
if (isset($_SESSION['form_errors'])) unset($_SESSION['form_errors']);

require_once __DIR__ . '/../src/templates/footer.php';
?>