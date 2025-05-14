<?php
// src/templates/header.php
// Este arquivo é incluído por arquivos dentro de public/
// Ex: public/index.php faz require_once __DIR__ . '/../src/templates/header.php';

// Inclui as constantes (que definem BASE_URL, SITE_NAME, etc.)
// O caminho para src/config/constants.php a partir de src/templates/header.php é ../config/constants.php
require_once dirname(__DIR__) . '/config/constants.php';

// Inicia a sessão se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Função e() para sanitizar a saída (se não estiver em functions.php ou se functions.php não for incluído antes)
// É melhor garantir que functions.php seja incluído antes de usar e(),
// mas por segurança, podemos defini-la aqui se não existir.
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR"> <!-- Alterado para pt-BR para maior especificidade -->
<head>
    <meta charset="UTF-8"> <!-- Padrão HTML5 para charset -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ' : ''; echo e(SITE_NAME); ?></title>
    
    <!-- Pico.css CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@latest/css/pico.min.css">
    
    <!-- Seu CSS customizado (DEVE VIR DEPOIS do Pico.css para sobrescrever/adicionar estilos) -->
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>css/style.css">
    
    <!-- Você pode adicionar outros links de CSS ou meta tags aqui -->
</head>
<body>
    <header class="container-fluid" style="padding-bottom: 0; margin-bottom: 1rem; border-bottom: 1px solid var(--muted-border-color);">
        <nav> <!-- Removido container-fluid daqui, pois o header já é. Nav pode ser container normal -->
            <ul>
                <li><a href="<?php echo e(BASE_URL); ?>index.php"><strong><?php echo e(SITE_NAME); ?></strong></a></li>
            </ul>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo e(BASE_URL); ?>dashboard.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'aria-current="page"' : ''; ?>>Dashboard</a></li>
                    <li><a href="<?php echo e(BASE_URL); ?>generator.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'generator.php') ? 'aria-current="page"' : ''; ?>>Gerador</a></li>
                    <li><a href="<?php echo e(BASE_URL); ?>history.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'history.php') ? 'aria-current="page"' : ''; ?>>Histórico</a></li>
                    <li><a href="<?php echo e(BASE_URL); ?>profile.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'aria-current="page"' : ''; ?>>Perfil</a></li>
                    <li><a href="<?php echo e(BASE_URL); ?>logout_handler.php" role="button" class="secondary outline">Sair</a></li> <!-- Usando logout_handler.php -->
                <?php else: ?>
                    <li><a href="<?php echo e(BASE_URL); ?>login.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'aria-current="page"' : ''; ?>>Login</a></li>
                    <li><a href="<?php echo e(BASE_URL); ?>register.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'register.php') ? 'aria-current="page"' : ''; ?> role="button">Registrar-se</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="container"> <!-- Container principal para o conteúdo da página -->
        <?php
        // Exibir mensagens de feedback globais (não específicas de um formulário)
        // Essas são mensagens que podem ser definidas por qualquer action antes de um redirect.
        if (isset($_SESSION['global_success_message'])) {
            echo '<article class="success-message" style="background-color: var(--pico-color-green-100); color: var(--pico-color-green-700); border-left: 5px solid var(--pico-color-green-700); padding: 1rem;">' . e($_SESSION['global_success_message']) . '</article>';
            unset($_SESSION['global_success_message']);
        }
        if (isset($_SESSION['global_error_message'])) {
            echo '<article class="error-message" style="background-color: var(--pico-color-red-100); color: var(--pico-color-red-700); border-left: 5px solid var(--pico-color-red-700); padding: 1rem;">' . e($_SESSION['global_error_message']) . '</article>';
            unset($_SESSION['global_error_message']);
        }
        // Nota: Mensagens específicas de formulários (como form_errors, form_data) devem ser tratadas nas próprias páginas dos formulários.
        ?>