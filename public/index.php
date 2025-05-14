<?php
// public/index.php
$page_title = "Bem-vindo";
require_once __DIR__ . '/../src/templates/header.php';
?>

<div class="container py-5"> 
    <div class="row justify-content-center text-center">
        <div class="col-lg-8 col-xl-7"> 
            <i class="bi bi-stars text-primary" style="font-size: 3.5rem;"></i>
            <h1 class="display-4 fw-bold my-3"> 
                <?php echo e(SITE_NAME); ?>
            </h1>
            <p class="lead mb-4 text-muted" style="font-size: 1.2rem;">
                Desbloqueie o poder da Inteligência Artificial com prompts perfeitamente elaborados. Nossa ferramenta ajuda você a criar, gerenciar e otimizar seus prompts para obter os melhores resultados.
            </p>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center mb-5">
                    <a href="<?php echo e(BASE_URL); ?>register.php" class="btn btn-primary btn-lg px-4 py-3">
                        <i class="bi bi-person-plus-fill me-2"></i>Comece Gratuitamente
                    </a>
                    <a href="<?php echo e(BASE_URL); ?>login.php" class="btn btn-outline-secondary btn-lg px-4 py-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Fazer Login
                    </a>
                </div>
            <?php else: ?>
                 <div class="mb-5">
                    <p class="lead">Você já está logado, <?php echo e($_SESSION['username']); ?>!</p>
                    <a href="<?php echo e(BASE_URL); ?>dashboard.php" class="btn btn-success btn-lg px-4 py-3">
                        <i class="bi bi-speedometer2 me-2"></i>Ir para o Dashboard
                    </a>
                 </div>
            <?php endif; ?>
        </div>
    </div>

    <hr class="my-5">

    <div class="row g-4 py-5 row-cols-1 row-cols-md-3 text-center">
        <div class="col d-flex flex-column align-items-center">
            <div class="feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 rounded-3 mb-3 p-2" style="width: 4rem; height: 4rem;">
                <i class="bi bi-vector-pen"></i>
            </div>
            <h4 class="fw-semibold mb-1">Crie Prompts Otimizados</h4>
            <p class="text-muted small">Nossa interface guiada ajuda você a incluir todos os elementos importantes.</p>
        </div>
        <div class="col d-flex flex-column align-items-center">
            <div class="feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-success bg-gradient fs-2 rounded-3 mb-3 p-2" style="width: 4rem; height: 4rem;">
                <i class="bi bi-hdd-stack"></i>
            </div>
            <h4 class="fw-semibold mb-1">Organize Suas Ideias</h4>
            <p class="text-muted small">Salve e categorize seus prompts para fácil acesso e reutilização.</p>
        </div>
        <div class="col d-flex flex-column align-items-center">
            <div class="feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-info bg-gradient fs-2 rounded-3 mb-3 p-2" style="width: 4rem; height: 4rem;">
                <i class="bi bi-gem"></i>
            </div>
            <h4 class="fw-semibold mb-1">Integração com IA</h4>
            <p class="text-muted small">Envie seus prompts diretamente para APIs como a do Google Gemini.</p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../src/templates/footer.php';
?>