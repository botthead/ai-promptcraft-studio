<?php
// public/index.php
$page_title = "Bem-vindo";
// Incluir o header. O header já inclui constants.php e inicia a sessão.
// O caminho para src/templates/header.php a partir de public/index.php é ../src/templates/header.php
require_once __DIR__ . '/../src/templates/header.php';

// O header já tem o código para exibir diferentes links de navegação se logado ou não.
// O BASE_URL de constants.php deve ser usado para todos os links e assets.
?>

<article> <!-- Usar <article> para o conteúdo principal da página é semanticamente bom com PicoCSS -->
    <header>
        <h1><?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?></h1>
    </header>
    <p>Desbloqueie o poder da Inteligência Artificial com prompts perfeitamente elaborados.</p>
    <p>Nossa ferramenta ajuda você a criar, gerenciar e otimizar seus prompts para obter os melhores resultados de modelos como o Gemini.</p>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="grid" style="margin-top: 20px;"> <!-- Usando grid do PicoCSS para botões lado a lado -->
            <div><a href="<?php echo BASE_URL; ?>register.php" role="button" class="contrast">Comece Agora Gratuitamente</a></div>
            <div><a href="<?php echo BASE_URL; ?>login.php" role="button" class="secondary">Já tenho uma conta</a></div>
        </div>
    <?php else: ?>
         <p>Você está logado. Vá para o seu <a href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a>.</p>
    <?php endif; ?>
</article>

<section>
    <h2>Por que usar o <?php echo htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8'); ?>?</h2>
    <div class="grid">
        <article>
            <h3>Crie Prompts Otimizados</h3>
            <p>Nossa interface guiada ajuda você a incluir todos os elementos importantes para um prompt eficaz.</p>
        </article>
        <article>
            <h3>Organize Suas Ideias</h3>
            <p>Salve e categorize seus prompts para fácil acesso e reutilização.</p>
        </article>
        <article>
            <h3>Integração Futura</h3>
            <p>Planejado para integrar diretamente com APIs de IA como a do Google Gemini.</p>
        </article>
    </div>
</section>

<?php
// Incluir o footer
require_once __DIR__ . '/../src/templates/footer.php';
?>