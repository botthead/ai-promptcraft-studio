<?php
// src/templates/footer.php
// Garanta que não há NENHUM texto ou comentário solto antes da tag <?php de abertura.
// A variável $current_page_basename é definida no header.php
?>
    </main> <!-- Fim do <main class="flex-shrink-0"> que foi aberto no header.php -->
    
    <footer class="footer mt-auto py-4 bg-light border-top">
        <div class="container">
            <div class="row">
                <!-- Coluna de Navegação (para usuários logados) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="col-md-6 col-lg-4 mb-3 mb-md-0">
                    <h5 class="fw-semibold mb-2">Navegação Rápida</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo e(BASE_URL); ?>dashboard.php" class="text-muted text-decoration-none footer-link <?php echo ($current_page_basename == 'dashboard.php') ? 'active-footer-link' : ''; ?>">Dashboard</a></li>
                        <li><a href="<?php echo e(BASE_URL); ?>generator.php" class="text-muted text-decoration-none footer-link <?php echo ($current_page_basename == 'generator.php') ? 'active-footer-link' : ''; ?>">Gerador de Prompt</a></li>
                        <li><a href="<?php echo e(BASE_URL); ?>ebook_planner.php" class="text-muted text-decoration-none footer-link <?php echo ($current_page_basename == 'ebook_planner.php') ? 'active-footer-link' : ''; ?>">Planner de eBook</a></li>
                        <li><a href="<?php echo e(BASE_URL); ?>history.php" class="text-muted text-decoration-none footer-link <?php echo ($current_page_basename == 'history.php') ? 'active-footer-link' : ''; ?>">Histórico</a></li>
                        <li><a href="<?php echo e(BASE_URL); ?>profile.php" class="text-muted text-decoration-none footer-link <?php echo ($current_page_basename == 'profile.php') ? 'active-footer-link' : ''; ?>">Meu Perfil</a></li>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Coluna de Informações/Contato (ajuste conforme necessário) -->
                <div class="col-md-6 <?php echo isset($_SESSION['user_id']) ? 'col-lg-4' : 'col-lg-6'; ?> mb-3 mb-md-0">
                    <h5 class="fw-semibold mb-2"><?php echo e(SITE_NAME); ?></h5>
                    <p class="text-muted small">Otimizando sua criatividade com Inteligência Artificial.</p>
                    <!-- Adicione links para redes sociais ou contato se desejar -->
                </div>

                <!-- Coluna de Copyright e Links Legais -->
                <div class="<?php echo isset($_SESSION['user_id']) ? 'col-lg-4' : 'col-lg-6'; ?> text-lg-end">
                    <p class="text-muted small mb-1">© <?php echo date('Y'); ?> <?php echo e(SITE_NAME); ?>. Todos os direitos reservados.</p>
                    <ul class="list-inline small">
                        <li class="list-inline-item"><a href="#" class="text-muted text-decoration-none footer-link">Política de Privacidade</a></li>
                        <li class="list-inline-item"><a href="#" class="text-muted text-decoration-none footer-link">Termos de Uso</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle (Popper.js incluído) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- Seu main.js global (se tiver scripts que se aplicam a várias páginas) -->
    <!-- Se o seu JavaScript for específico para cada página (como o de generator.php), -->
    <!-- é melhor incluí-lo no final da própria página, ANTES DESTA LINHA DE INCLUDE DO FOOTER -->
    <!-- OU DEPOIS do Bootstrap JS Bundle, se o script customizado depender do Bootstrap. -->
    <!-- A versão mais recente do generator.php coloca o script customizado APÓS o footer.php ser incluído. -->
     <script src="<?php echo e(BASE_URL); ?>js/main.js"></script> <!-- Incluído para consistência -->

</body>
</html>
<?php
// NADA APÓS ESTA TAG DE FECHAMENTO DO PHP.
?>