<?php
// public/history.php
$page_title = "Histórico de Prompts";

require_once __DIR__ . '/../src/core/auth.php';
require_login(); 

require_once __DIR__ . '/../src/config/database.php'; 
require_once __DIR__ . '/../src/templates/header.php';

$user_id = $_SESSION['user_id'];
$prompts = [];

try {
    $sql = "SELECT id, title, generated_prompt_text, gemini_response, created_at 
            FROM prompts 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PDOException ao buscar histórico de prompts para user_id {$user_id}: " . $e->getMessage());
    // A mensagem global de erro já é tratada no header.php
}
?>

<div class="container py-4"> 
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-5 fw-bold mb-0">
            <i class="bi bi-list-stars me-2"></i><?php echo e($page_title); ?>
        </h1>
        <a href="<?php echo e(BASE_URL); ?>generator.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Criar Novo Prompt
        </a>
    </div>
    <p class="lead text-muted mb-4">
        Revise e gerencie seus prompts salvos. Respostas da API Gemini também são exibidas quando disponíveis.
    </p>

    <?php
    // Mensagens específicas para ações de exclusão nesta página (se necessário, senão as globais bastam)
    // ...
    ?>

    <?php if (!empty($prompts)): ?>
        <div class="list-group shadow-sm">
            <?php foreach ($prompts as $prompt): ?>
                <div class="list-group-item list-group-item-action flex-column align-items-start mb-2 border-0 shadow-sm rounded">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1 fw-semibold text-primary"><?php echo e($prompt['title'] ?: '<em>Prompt Sem Título</em>'); ?></h5>
                        <small class="text-muted">
                            <?php
                            try { $date = new DateTime($prompt['created_at']); echo e($date->format('d/m/Y H:i')); }
                            catch (Exception $ex) { echo e($prompt['created_at']); }
                            ?>
                        </small>
                    </div>
                    <p class="mb-1 text-muted small">
                        <?php
                        $preview_length = 150;
                        $prompt_preview = mb_substr(strip_tags($prompt['generated_prompt_text']), 0, $preview_length);
                        if (mb_strlen($prompt['generated_prompt_text']) > $preview_length) {
                            $prompt_preview .= '...';
                        }
                        echo e($prompt_preview);
                        ?>
                    </p>
                    
                    <?php if (!empty($prompt['gemini_response'])): ?>
                    <details class="mt-2 mb-2">
                        <summary class="small text-info" style="cursor: pointer;">
                            <i class="bi bi-robot me-1"></i>Ver Resposta da API Gemini
                        </summary>
                        <div class="mt-2 p-2 border rounded bg-light-subtle">
                            <pre style="white-space: pre-wrap; word-wrap: break-word; max-height: 200px; overflow-y: auto; font-size: 0.85em;"><?php echo e($prompt['gemini_response']); ?></pre>
                        </div>
                    </details>
                    <?php endif; ?>

                    <div class="mt-2 text-end">
                        <a href="<?php echo e(BASE_URL); ?>generator.php?reuse_prompt_id=<?php echo (int)$prompt['id']; ?>" 
                           class="btn btn-sm btn-outline-secondary me-1" title="Reutilizar este Prompt">
                           <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-md-inline">Reutilizar</span>
                        </a>
                        <form action="<?php echo e(BASE_URL); ?>../src/actions/delete_prompt_action.php" method="POST" class="d-inline"
                              onsubmit="return confirm('Tem certeza que deseja excluir este prompt?');">
                            <input type="hidden" name="prompt_id_to_delete" value="<?php echo (int)$prompt['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir este Prompt">
                                <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Excluir</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>Você ainda não salvou nenhum prompt. 
            <a href="<?php echo e(BASE_URL); ?>generator.php" class="alert-link">Crie seu primeiro prompt agora!</a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../src/templates/footer.php';
?>