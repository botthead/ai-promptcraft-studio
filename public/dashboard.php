<?php
// public/dashboard.php
$page_title = "Dashboard";

require_once __DIR__ . '/../src/core/auth.php';
require_login(); 

require_once __DIR__ . '/../src/templates/header.php'; // Abre <main class="flex-shrink-0">
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-5 fw-bold">
                <i class="bi bi-speedometer2 me-2"></i>Bem-vindo ao seu Dashboard, <?php echo e($_SESSION['username'] ?? 'Usuário'); ?>!
            </h1>
            <p class="lead text-muted">
                Este é o seu painel central no <?php echo e(SITE_NAME); ?>. Acesse as funcionalidades abaixo para começar.
            </p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <div class="col">
            <div class="card h-100 shadow-sm hover-lift">
                <div class="card-body text-center d-flex flex-column">
                    <div class="mb-3 mt-2">
                        <i class="bi bi-magic text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="card-title fw-semibold">Gerador de Prompts</h5>
                    <p class="card-text text-muted small mb-auto">Crie prompts otimizados para suas IAs favoritas com nosso assistente.</p>
                    <a href="<?php echo e(BASE_URL); ?>generator.php" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle-fill me-2"></i>Criar Novo Prompt
                    </a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card h-100 shadow-sm hover-lift">
                <div class="card-body text-center d-flex flex-column">
                    <div class="mb-3 mt-2">
                        <i class="bi bi-journal-richtext text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="card-title fw-semibold">Planejador de eBook</h5>
                    <p class="card-text text-muted small mb-auto">Estruture seu próximo eBook passo a passo com assistência de IA.</p>
                    <a href="<?php echo e(BASE_URL); ?>ebook_planner.php" class="btn btn-success mt-3">
                        <i class="bi bi-pencil-square me-2"></i>Acessar Planner
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card h-100 shadow-sm hover-lift">
                <div class="card-body text-center d-flex flex-column">
                    <div class="mb-3 mt-2">
                        <i class="bi bi-clock-history text-info" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="card-title fw-semibold">Histórico de Prompts</h5>
                    <p class="card-text text-muted small mb-auto">Revise, reutilize ou exclua os prompts que você já criou e salvou.</p>
                    <a href="<?php echo e(BASE_URL); ?>history.php" class="btn btn-info mt-3">
                        <i class="bi bi-list-task me-2"></i>Ver Histórico
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card h-100 shadow-sm hover-lift">
                <div class="card-body text-center d-flex flex-column">
                    <div class="mb-3 mt-2">
                        <i class="bi bi-person-gear text-warning" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="card-title fw-semibold">Meu Perfil</h5>
                    <p class="card-text text-muted small mb-auto">Gerencie suas informações, altere sua senha e configure sua chave API.</p>
                    <a href="<?php echo e(BASE_URL); ?>profile.php" class="btn btn-warning mt-3">
                        <i class="bi bi-sliders me-2"></i>Configurar Perfil
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
// Estilos específicos para esta página podem ir para style.css ou aqui se forem muito pontuais
// A classe .hover-lift já foi definida no style.css do header.php como exemplo, 
// mas o ideal é centralizar em public/css/style.css.
// Se não estiver lá, adicione em public/css/style.css:
/*
.hover-lift {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.1);
}
*/
require_once __DIR__ . '/../src/templates/footer.php';
?>