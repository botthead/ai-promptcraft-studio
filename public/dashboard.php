<?php
// public/dashboard.php
$page_title = "Dashboard";

require_once __DIR__ . '/../src/core/auth.php';
require_login(); 

require_once __DIR__ . '/../src/templates/header.php';
?>

<article>
    <header>
        <h2>Bem-vindo ao seu Dashboard, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuário', ENT_QUOTES, 'UTF-8'); ?>!</h2>
    </header>
    <p>Este é o seu painel central no AI PromptCraft Studio. A partir daqui, você pode acessar todas as funcionalidades.</p>
    
    <div class="grid" style="margin-top: 2rem;">
        <article>
            <h4><a href="<?php echo BASE_URL; ?>generator.php" role="button" class="contrast">Criar Novo Prompt</a></h4>
            <p>Acesse nosso assistente para construir prompts otimizados para suas IAs favoritas.</p>
        </article>
        <article>
            <h4><a href="<?php echo BASE_URL; ?>history.php" role="button">Ver Histórico de Prompts</a></h4>
            <p>Revise, reutilize ou exclua os prompts que você já criou e salvou.</p>
        </article>
        <article>
            <h4><a href="<?php echo BASE_URL; ?>profile.php" role="button">Configurar Perfil</a></h4>
            <p>Gerencie suas informações pessoais e configurações da API.</p>
        </article>
    </div>

    <!-- Outros recursos ou informações podem ser adicionados aqui no futuro -->

</article>

<?php
require_once __DIR__ . '/../src/templates/footer.php';
?>