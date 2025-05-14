 
<?php
// public/register.php
$page_title = "Registrar Nova Conta";

// Inclui o auth.php para usar require_guest()
// O header.php já inclui constants.php e inicia a sessão.
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
        unset($_SESSION['error_message']); // Limpa a mensagem após exibir
    }
    if (isset($_SESSION['success_message'])) {
        echo '<p class="success-message" style="color: green;">' . htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') . '</p>';
        unset($_SESSION['success_message']); // Limpa a mensagem após exibir
    }
    ?>

    <form action="<?php echo BASE_URL; ?>../src/actions/register_action.php" method="POST">
        <!-- Campo Nome de Usuário -->
        <label for="username">Nome de Usuário:</label>
        <input type="text" id="username" name="username" required 
               value="<?php echo isset($_SESSION['form_data']['username']) ? htmlspecialchars($_SESSION['form_data']['username'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        <?php if (isset($_SESSION['form_errors']['username'])): ?>
            <small style="color: red;"><?php echo htmlspecialchars($_SESSION['form_errors']['username'], ENT_QUOTES, 'UTF-8'); ?></small>
        <?php endif; ?>

        <!-- Campo Email -->
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required
               value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        <?php if (isset($_SESSION['form_errors']['email'])): ?>
            <small style="color: red;"><?php echo htmlspecialchars($_SESSION['form_errors']['email'], ENT_QUOTES, 'UTF-8'); ?></small>
        <?php endif; ?>

        <!-- Campo Senha -->
        <label for="password">Senha:</label>
        <input type="password" id="password" name="password" required>
        <?php if (isset($_SESSION['form_errors']['password'])): ?>
            <small style="color: red;"><?php echo htmlspecialchars($_SESSION['form_errors']['password'], ENT_QUOTES, 'UTF-8'); ?></small>
        <?php endif; ?>
        <small>Mínimo de 8 caracteres, incluindo letras e números.</small>


        <!-- Campo Confirmar Senha -->
        <label for="confirm_password">Confirmar Senha:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <?php if (isset($_SESSION['form_errors']['confirm_password'])): ?>
            <small style="color: red;"><?php echo htmlspecialchars($_SESSION['form_errors']['confirm_password'], ENT_QUOTES, 'UTF-8'); ?></small>
        <?php endif; ?>

        <!-- Termos de Uso (Opcional, mas bom para ter) -->
        <div>
            <input type="checkbox" id="terms" name="terms" required>
            <label for="terms" style="display: inline;">Eu li e aceito os <a href="#">Termos de Uso</a> e <a href="#">Política de Privacidade</a>.</label>
            <?php if (isset($_SESSION['form_errors']['terms'])): ?>
                <br><small style="color: red;"><?php echo htmlspecialchars($_SESSION['form_errors']['terms'], ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>
        
        <button type="submit">Registrar</button>
    </form>

    <p style="margin-top: 1rem;">
        Já tem uma conta? <a href="<?php echo BASE_URL; ?>login.php">Faça login aqui</a>.
    </p>
</article>

<?php
// Limpar dados de formulário e erros da sessão após exibi-los
if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']);
if (isset($_SESSION['form_errors'])) unset($_SESSION['form_errors']);

require_once __DIR__ . '/../src/templates/footer.php';
?>