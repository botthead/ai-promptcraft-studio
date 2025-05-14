<?php
// public/login.php
$page_title = "Acessar sua Conta";

require_once __DIR__ . '/../src/core/auth.php';
require_guest(); // Redireciona para o dashboard se já estiver logado

require_once __DIR__ . '/../src/templates/header.php';
?>

<div class="container py-5"> 
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4"> 
            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-box-arrow-in-right display-4 text-primary"></i>
                        <h1 class="h3 fw-bold mt-2 mb-0"><?php echo e($page_title); ?></h1>
                    </div>

                    <?php
                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="alert alert-danger" role="alert">' . e($_SESSION['error_message']) . '</div>';
                        unset($_SESSION['error_message']);
                    }
                    if (isset($_SESSION['success_message'])) { // Ex: após registro bem-sucedido
                        echo '<div class="alert alert-success" role="alert">' . e($_SESSION['success_message']) . '</div>';
                        unset($_SESSION['success_message']);
                    }
                    ?>

                    <form action="<?php echo e(BASE_URL); ?>../src/actions/login_action.php" method="POST" novalidate>
                        <div class="mb-3">
                            <label for="login_identifier" class="form-label">Nome de Usuário ou Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                <input type="text" class="form-control <?php echo isset($_SESSION['form_errors']['login_identifier']) ? 'is-invalid' : ''; ?>" 
                                       id="login_identifier" name="login_identifier" required
                                       value="<?php echo e($_SESSION['form_data']['login_identifier'] ?? ''); ?>" autofocus>
                            </div>
                            <?php if (isset($_SESSION['form_errors']['login_identifier'])): ?>
                                <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors']['login_identifier']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control <?php echo isset($_SESSION['form_errors']['password']) ? 'is-invalid' : ''; ?>" 
                                       id="password" name="password" required>
                            </div>
                            <?php if (isset($_SESSION['form_errors']['password'])): ?>
                                <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors']['password']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Entrar
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="mb-0 text-muted">Não tem uma conta? <a href="<?php echo e(BASE_URL); ?>register.php" class="fw-medium">Registre-se aqui</a>.</p>
                            <!-- <p class="mt-2 small"><a href="#" class="text-muted">Esqueceu sua senha?</a></p> -->
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']);
if (isset($_SESSION['form_errors'])) unset($_SESSION['form_errors']);
require_once __DIR__ . '/../src/templates/footer.php';
?>