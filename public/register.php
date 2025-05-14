<?php
// public/register.php
$page_title = "Registrar Nova Conta";

require_once __DIR__ . '/../src/core/auth.php';
require_guest(); 

require_once __DIR__ . '/../src/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h2 class="card-title text-center mb-4 fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i><?php echo e($page_title); ?>
                </h2>

                <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger" role="alert">' . e($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                // Não costuma haver 'success_message' na página de registro, mas caso haja:
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success" role="alert">' . e($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                ?>

                <form action="<?php echo e(BASE_URL); ?>../src/actions/register_action.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Nome de Usuário:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control form-control-lg <?php echo isset($_SESSION['form_errors']['username']) ? 'is-invalid' : ''; ?>" 
                                   id="username" name="username" required 
                                   value="<?php echo e($_SESSION['form_data']['username'] ?? ''); ?>">
                        </div>
                        <?php if (isset($_SESSION['form_errors']['username'])): ?>
                            <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors']['username']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control form-control-lg <?php echo isset($_SESSION['form_errors']['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" required
                                   value="<?php echo e($_SESSION['form_data']['email'] ?? ''); ?>">
                        </div>
                        <?php if (isset($_SESSION['form_errors']['email'])): ?>
                            <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors']['email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Senha:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control form-control-lg <?php echo isset($_SESSION['form_errors']['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                        </div>
                        <div class="form-text">Mínimo de 8 caracteres, incluindo letras e números.</div>
                        <?php if (isset($_SESSION['form_errors']['password'])): ?>
                            <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors']['password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Senha:</label>
                        <div class="input-group">
                             <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" class="form-control form-control-lg <?php echo isset($_SESSION['form_errors']['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" required>
                        </div>
                        <?php if (isset($_SESSION['form_errors']['confirm_password'])): ?>
                            <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors']['confirm_password']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input <?php echo isset($_SESSION['form_errors']['terms']) ? 'is-invalid' : ''; ?>" 
                               type="checkbox" value="accepted" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            Eu li e aceito os <a href="#" target="_blank">Termos de Uso</a> e <a href="#" target="_blank">Política de Privacidade</a>.
                        </label>
                        <?php if (isset($_SESSION['form_errors']['terms'])): ?>
                            <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors']['terms']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle-fill me-2"></i>Registrar
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="mb-0">Já tem uma conta? <a href="<?php echo e(BASE_URL); ?>login.php">Faça login aqui</a>.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_SESSION['form_data'])) unset($_SESSION['form_data']);
if (isset($_SESSION['form_errors'])) unset($_SESSION['form_errors']);
require_once __DIR__ . '/../src/templates/footer.php';
?>