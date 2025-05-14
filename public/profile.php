<?php
// public/profile.php
$page_title = "Meu Perfil e Configurações";

// Ordem dos includes: auth primeiro para proteger a página
require_once __DIR__ . '/../src/core/auth.php';
require_login(); // Garante que apenas usuários logados acessem

// Depois config e functions se necessário (header já os inclui)
require_once __DIR__ . '/../src/config/database.php'; // $pdo
// A função e() é definida no header ou functions.php
// A função decrypt_data() será chamada apenas se necessário abaixo

require_once __DIR__ . '/../src/templates/header.php'; // Inclui constants, session_start, e()

$user_id = $_SESSION['user_id'];
$user_info = null;
$gemini_api_key_status = "Não configurada";

try {
    $stmt = $pdo->prepare("SELECT username, email, gemini_api_key_encrypted FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_info && !empty($user_info['gemini_api_key_encrypted'])) {
        // A função decrypt_data precisa estar disponível aqui se formos descriptografar
        // Como incluímos functions.php no header (ou o header inclui constants.php que deve ser incluído antes de functions.php), está OK
        // Por segurança, não exibimos a chave, apenas o status.
        $gemini_api_key_status = "Configurada";
    }

} catch (PDOException $e) {
    error_log("PDOException ao carregar perfil do usuário {$user_id}: " . $e->getMessage());
    $_SESSION['global_error_message'] = "Erro ao carregar informações do perfil.";
    // A mensagem global será exibida pelo header.php
}

?>

<article>
    <header>
        <h2><?php echo e($page_title); ?></h2>
    </header>

    <?php
    // Mensagens de sucesso/erro específicas desta página (definidas por update_profile_action.php)
    if (isset($_SESSION['profile_success_message'])) {
        echo '<div class="notice success" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-green); margin-bottom: 1rem; padding: 0.75rem;"><p>' . e($_SESSION['profile_success_message']) . '</p></div>';
        unset($_SESSION['profile_success_message']);
    }
    if (isset($_SESSION['profile_error_message'])) {
        echo '<div class="notice error" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-red); margin-bottom: 1rem; padding: 0.75rem;"><p>' . e($_SESSION['profile_error_message']) . '</p></div>';
        unset($_SESSION['profile_error_message']);
    }
    ?>

    <?php if ($user_info): ?>
        <section id="account-info">
            <h3>Informações da Conta</h3>
            <p><strong>Nome de Usuário:</strong> <?php echo e($user_info['username']); ?></p>
            <p><strong>Email:</strong> <?php echo e($user_info['email']); ?></p>
        </section>

        <hr>

        <section id="api-key-config">
            <h3>Configuração da API do Google Gemini</h3>
            <p>Para utilizar funcionalidades que interagem diretamente com o Google Gemini, você precisará fornecer sua chave API.</p>
            <p>Status atual da chave: <strong><?php echo e($gemini_api_key_status); ?></strong></p>

            <form action="<?php echo e(BASE_URL); ?>../src/actions/update_profile_action.php" method="POST">
                <input type="hidden" name="action" value="update_api_key">
                
                <label for="gemini_api_key">
                    Sua Chave API do Gemini:
                    <input type="password" id="gemini_api_key" name="gemini_api_key" 
                           placeholder="<?php echo ($gemini_api_key_status == 'Configurada') ? 'Insira nova chave para atualizar (deixe em branco para manter)' : 'Insira sua chave API aqui'; ?>">
                    <small>Sua chave será armazenada de forma criptografada. Se já houver uma chave configurada, inserir uma nova irá substituí-la. Deixe em branco para não alterar a chave existente.</small>
                </label>
                <?php if (isset($_SESSION['form_errors_profile']['gemini_api_key'])): ?>
                    <small class="error-feedback" style="color: var(--pico-color-red-500);"><?php echo e($_SESSION['form_errors_profile']['gemini_api_key']); ?></small><br>
                <?php endif; ?>

                <button type="submit">Salvar Chave API</button>
                <?php if ($gemini_api_key_status == 'Configurada'): ?>
                    <button type="submit" name="action" value="remove_api_key" class="secondary outline" style="margin-left: 10px;"
                            onclick="return confirm('Tem certeza que deseja remover sua chave API do Gemini?');">Remover Chave API</button>
                <?php endif; ?>
            </form>
        </section>
        
        <hr>
        <section id="change-password">
            <h3>Alterar Senha</h3>
             <form action="<?php echo e(BASE_URL); ?>../src/actions/update_profile_action.php" method="POST">
                <input type="hidden" name="action" value="update_password">
                
                <label for="current_password">Senha Atual:</label>
                <input type="password" id="current_password" name="current_password" required>
                 <?php if (isset($_SESSION['form_errors_profile']['current_password'])): ?>
                    <small class="error-feedback" style="color: var(--pico-color-red-500);"><?php echo e($_SESSION['form_errors_profile']['current_password']); ?></small><br>
                <?php endif; ?>

                <label for="new_password">Nova Senha:</label>
                <input type="password" id="new_password" name="new_password" required>
                <small>Mínimo de 8 caracteres, incluindo letras e números.</small><br>
                 <?php if (isset($_SESSION['form_errors_profile']['new_password'])): ?>
                    <small class="error-feedback" style="color: var(--pico-color-red-500);"><?php echo e($_SESSION['form_errors_profile']['new_password']); ?></small><br>
                <?php endif; ?>

                <label for="confirm_new_password">Confirmar Nova Senha:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                 <?php if (isset($_SESSION['form_errors_profile']['confirm_new_password'])): ?>
                    <small class="error-feedback" style="color: var(--pico-color-red-500);"><?php echo e($_SESSION['form_errors_profile']['confirm_new_password']); ?></small><br>
                <?php endif; ?>
                
                <button type="submit">Alterar Senha</button>
            </form>
        </section>

    <?php else: ?>
        <p>Não foi possível carregar as informações do seu perfil. Por favor, tente recarregar a página ou entre em contato com o suporte se o problema persistir.</p>
    <?php endif; ?>
</article>

<?php
if (isset($_SESSION['form_errors_profile'])) unset($_SESSION['form_errors_profile']); // Limpa erros do formulário da sessão
require_once __DIR__ . '/../src/templates/footer.php';
?>