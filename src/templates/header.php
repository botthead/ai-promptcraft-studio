<?php
// src/templates/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Este require_once deve vir primeiro para definir BASE_URL, SITE_NAME etc.
require_once dirname(__DIR__) . '/config/constants.php';

// Função e() para sanitizar, se não definida globalmente antes
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}
$current_page_basename = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ' : ''; echo e(SITE_NAME); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts (Exemplo: Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Seu CSS customizado (DEVE VIR DEPOIS do Bootstrap) -->
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>css/style.css">

    <!-- Estilos inline mínimos para garantir a estrutura flex do body -->
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-family: 'Inter', sans-serif; /* Reforça a fonte global */
            background-color: #f8f9fa; /* Cor de fundo global */
        }
        main.flex-shrink-0 { /* Garante que o main não encolha e empurre o footer */
            flex-grow: 1;
        }
        .navbar {
             box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); /* Sombra sutil para o navbar */
        }
        /* Outros estilos globais podem ir para style.css */
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
            <div class="container">
                <a class="navbar-brand fw-bold" href="<?php echo e(BASE_URL); ?>index.php">
                    <i class="bi bi-stars me-2" style="color: var(--bs-primary);"></i><?php echo e(SITE_NAME); ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page_basename == 'dashboard.php') ? 'active fw-semibold' : ''; ?>" href="<?php echo e(BASE_URL); ?>dashboard.php">
                                    <i class="bi bi-layout-wtf me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page_basename == 'generator.php') ? 'active fw-semibold' : ''; ?>" href="<?php echo e(BASE_URL); ?>generator.php">
                                    <i class="bi bi-magic me-1"></i>Gerador Prompt
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page_basename == 'ebook_planner.php') ? 'active fw-semibold' : ''; ?>" href="<?php echo e(BASE_URL); ?>ebook_planner.php">
                                    <i class="bi bi-journal-plus me-1"></i>Planner eBook
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page_basename == 'history.php') ? 'active fw-semibold' : ''; ?>" href="<?php echo e(BASE_URL); ?>history.php">
                                    <i class="bi bi-clock-history me-1"></i>Histórico
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo ($current_page_basename == 'profile.php') ? 'active fw-semibold' : ''; ?>" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i><?php echo e($_SESSION['username'] ?? 'Usuário'); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                                    <li>
                                        <a class="dropdown-item <?php echo ($current_page_basename == 'profile.php') ? 'active' : ''; ?>" href="<?php echo e(BASE_URL); ?>profile.php">
                                            <i class="bi bi-person-lines-fill me-2"></i>Meu Perfil
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo e(BASE_URL); ?>logout_handler.php">
                                            <i class="bi bi-box-arrow-right me-2"></i>Sair
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page_basename == 'login.php') ? 'active fw-semibold' : ''; ?>" href="<?php echo e(BASE_URL); ?>login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-primary btn-sm ms-lg-2" href="<?php echo e(BASE_URL); ?>register.php" role="button">Registrar-se</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-shrink-0">
        <?php
        // Exibir mensagens de feedback globais
        if (isset($_SESSION['global_success_message'])) {
            echo '<div class="container pt-3"><div class="alert alert-success alert-dismissible fade show" role="alert">' .
                 e($_SESSION['global_success_message']) .
                 '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                 '</div></div>';
            unset($_SESSION['global_success_message']);
        }
        if (isset($_SESSION['global_error_message'])) {
            echo '<div class="container pt-3"><div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                 e($_SESSION['global_error_message']) .
                 '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                 '</div></div>';
            unset($_SESSION['global_error_message']);
        }
        ?>
        <!-- O conteúdo específico da página (geralmente dentro de um <div class="container py-X">) virá APÓS esta tag <main> e ANTES do footer.php -->