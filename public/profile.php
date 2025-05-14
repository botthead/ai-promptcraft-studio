<?php
// public/profile.php
$page_title = "Meu Perfil e Configurações";

require_once __DIR__ . '/../src/core/auth.php';
require_login();

require_once __DIR__ . '/../src/config/database.php'; 
require_once __DIR__ . '/../src/templates/header.php';

$user_id = $_SESSION['user_id'];
$user_info = null;
$gemini_api_key_status_html = "<span class='badge bg-warning text-dark'>Não configurada</span>"; // Padrão

try {
    $stmt = $pdo->prepare("SELECT username, email, gemini_api_key_encrypted FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_info && !empty($user_info['gemini_api_key_encrypted'])) {
        $gemini_api_key_status_html = "<span class='badge bg-success'><i class='bi bi-check-circle-fill me-1'></i>Configurada</span>";
    }

} catch (PDOException $e) {
    error_log("PDOException ao carregar perfil do usuário {$user_id}: " . $e->getMessage());
    // A mensagem global de erro já é tratada no header.php
}
?>

<div class="container py-4"> 
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8"> 
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="display-5 fw-bold mb-0">
                    <i class="bi bi-person-badge me-2"></i><?php echo e($page_title); ?>
                </h1>
            </div>

            <?php
            // Mensagens de feedback específicas do perfil já são tratadas pelas globais no header.php
            // Se você tinha $_SESSION['profile_success_message'] etc., elas seriam pegas pelas globais.
            ?>

            <?php if ($user_info): ?>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0 fw-semibold"><i class="bi bi-person-vcard me-2"></i>Informações da Conta</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Nome de Usuário:</dt>
                                    <dd class="col-sm-7"><?php echo e($user_info['username']); ?></dd>
                                    <dt class="col-sm-5">Email:</dt>
                                    <dd class="col-sm-7"><?php echo e($user_info['email']); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0 fw-semibold"><i class="bi bi-key-fill me-2"></i>API do Google Gemini</h5>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">Sua chave API é usada para interagir com os modelos Gemini e é armazenada de forma criptografada.</p>
                                <p>Status: <?php echo $gemini_api_key_status_html; ?></p>
                                
                                <form action="<?php echo e(BASE_URL); ?>../src/actions/update_profile_action.php" method="POST">
                                    <input type="hidden" name="action" value="update_api_key">
                                    <div class="mb-3">
                                        <label for="gemini_api_key" class="form-label visually-hidden">Nova Chave API Gemini:</label>
                                        <input type="password" class="form-control" id="gemini_api_key" name="gemini_api_key" 
                                               placeholder="<?php echo ($user_info && !empty($user_info['gemini_api_key_encrypted'])) ? 'Nova chave (deixe em branco para manter)' : 'Insira sua chave API aqui'; ?>">
                                        <?php if (isset($_SESSION['form_errors_profile']['gemini_api_key'])): ?>
                                            <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors_profile']['gemini_api_key']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Salvar Chave</button>
                                    <?php if ($user_info && !empty($user_info['gemini_api_key_encrypted'])): ?>
                                        <button type="submit" name="action" value="remove_api_key" class="btn btn-outline-danger btn-sm ms-2"
                                                onclick="return confirm('Tem certeza que deseja remover sua chave API do Gemini?');">
                                            <i class="bi bi-trash me-1"></i>Remover
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                     <div class="card-header bg-light">
                        <h5 class="mb-0 fw-semibold"><i class="bi bi-shield-lock-fill me-2"></i>Alterar Senha</h5>
                    </div>
                    <div class="card-body">
                         <form action="<?php echo e(BASE_URL); ?>../src/actions/update_profile_action.php" method="POST" novalidate>
                            <input type="hidden" name="action" value="update_password">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="current_password" class="form-label">Senha Atual:</label>
                                    <input type="password" class="form-control <?php echo isset($_SESSION['form_errors_profile']['current_password']) ? 'is-invalid' : ''; ?>" 
                                           id="current_password" name="current_password" required>
                                    <?php if (isset($_SESSION['form_errors_profile']['current_password'])): ?>
                                        <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors_profile']['current_password']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">Nova Senha:</label>
                                    <input type="password" class="form-control <?php echo isset($_SESSION['form_errors_profile']['new_password']) ? 'is-invalid' : ''; ?>" 
                                           id="new_password" name="new_password" required>
                                    <div class="form-text small">Mínimo de 8 caracteres, letras e números.</div>
                                    <?php if (isset($_SESSION['form_errors_profile']['new_password'])): ?>
                                        <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors_profile']['new_password']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_new_password" class="form-label">Confirmar Nova Senha:</label>
                                    <input type="password" class="form-control <?php echo isset($_SESSION['form_errors_profile']['confirm_new_password']) ? 'is-invalid' : ''; ?>" 
                                           id="confirm_new_password" name="confirm_new_password" required>
                                    <?php if (isset($_SESSION['form_errors_profile']['confirm_new_password'])): ?>
                                        <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors_profile']['confirm_new_password']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-key me-2"></i>Alterar Senha</button>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    Não foi possível carregar as informações do seu perfil.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if (isset($_SESSION['form_errors_profile'])) unset($_SESSION['form_errors_profile']);
require_once __DIR__ . '/../src/templates/footer.php';
?>