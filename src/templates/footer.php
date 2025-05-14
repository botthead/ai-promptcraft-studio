<?php
// src/templates/footer.php
?>
    </main> <!-- Fim do .container de <main> -->
    
    <footer class="container" style="margin-top: 2rem; ...">
        <small>© <?php echo date('Y'); ?> <?php echo e(SITE_NAME); ?>. Todos os direitos reservados.</small>
    </footer>

    <!-- 1. Bootstrap JS Bundle (Popper.js incluído) - DEVE VIR PRIMEIRO -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" ...></script>
    <script src="<?php echo e(BASE_URL); ?>js/main.js"></script> <!-- SEU SCRIPT DEPOIS -->
</body>
</html>

