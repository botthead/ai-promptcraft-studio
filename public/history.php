<?php
// public/history.php
$page_title = "Histórico de Prompts";

require_once __DIR__ . '/../src/core/auth.php';
require_login(); 

require_once __DIR__ . '/../src/config/database.php'; 
require_once __DIR__ . '/../src/templates/header.php'; // header.php já exibe global_success_message

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
    // Define uma mensagem de erro global para ser exibida pelo header.php
    $_SESSION['global_error_message'] = "Erro ao carregar seu histórico de prompts. Tente novamente mais tarde.";
}
?>

<article>
    <header>
        <h2><?php echo e($page_title); ?></h2>
        <p>Aqui você pode ver todos os prompts que você gerou e salvou, incluindo as respostas da API Gemini quando disponíveis.</p>
    </header>

    <?php
    // Mensagens específicas para ações de exclusão nesta página
    if (!empty($_SESSION['success_message_history'])): ?>
        <div class="notice success" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-green); margin-bottom: 1rem; padding:0.75rem;">
            <p><?php echo e($_SESSION['success_message_history']); ?></p>
        </div>
        <?php unset($_SESSION['success_message_history']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error_message_history'])): ?>
        <div class="notice error" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-red); margin-bottom: 1rem; padding:0.75rem;">
            <p><?php echo e($_SESSION['error_message_history']); ?></p>
        </div>
        <?php unset($_SESSION['error_message_history']); ?>
    <?php endif; ?>


    <?php if (!empty($prompts)): ?>
        <figure> <!-- Usar figure para tabelas é uma boa prática com PicoCSS -->
            <table>
                <thead>
                    <tr>
                        <th scope="col">Título</th>
                        <th scope="col">Prompt Gerado (Início)</th>
                        <th scope="col">Criado em</th>
                        <th scope="col" style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prompts as $prompt_idx => $prompt): ?>
                        <tr>
                            <td><?php echo e($prompt['title'] ?: '<em>Sem Título</em>'); ?></td>
                            <td>
                                <?php
                                $preview_length = 80;
                                $prompt_preview = mb_substr(strip_tags($prompt['generated_prompt_text']), 0, $preview_length);
                                if (mb_strlen($prompt['generated_prompt_text']) > $preview_length) {
                                    $prompt_preview .= '...';
                                }
                                echo e($prompt_preview);
                                ?>
                            </td>
                            <td>
                                <?php
                                try {
                                    $date = new DateTime($prompt['created_at']);
                                    echo e($date->format('d/m/Y H:i'));
                                } catch (Exception $ex) {
                                    echo e($prompt['created_at']); // Fallback se a data for inválida
                                }
                                ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <!-- Botão para reutilizar (leva para generator.php com dados) -->
                                <a href="<?php echo e(BASE_URL); ?>generator.php?reuse_prompt_id=<?php echo (int)$prompt['id']; ?>" 
                                   role="button" class="outline secondary" title="Reutilizar este Prompt" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                   <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16" style="vertical-align: text-bottom;">
                                      <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                                      <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                                   </svg>
                                   Reutilizar
                                </a>
                                
                                <form action="<?php echo e(BASE_URL); ?>../src/actions/delete_prompt_action.php" method="POST" style="display: inline-block; margin-left: 5px;">
                                    <input type="hidden" name="prompt_id_to_delete" value="<?php echo (int)$prompt['id']; ?>">
                                    <button type="submit" class="outline contrast" title="Excluir este Prompt" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;" 
                                            onclick="return confirm('Tem certeza que deseja excluir este prompt? Esta ação não pode ser desfeita.');">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16" style="vertical-align: text-bottom;">
                                          <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                          <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                        </svg>
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php if (!empty($prompt['gemini_response'])): ?>
                        <tr class="prompt-details-row">
                            <td colspan="4">
                                <details>
                                    <summary style="cursor: pointer; color: var(--pico-primary); font-size: 0.9rem;">
                                        Ver Resposta da API Gemini
                                        <small style="font-weight:normal; color: var(--pico-muted-color);">(Clique para expandir/recolher)</small>
                                    </summary>
                                    <article style="margin-top: 0.5rem; padding: 1rem; border: 1px solid var(--pico-muted-border-color); border-radius: var(--pico-border-radius); background-color: var(--pico-card-background-color);">
                                        <pre style="white-space: pre-wrap; word-wrap: break-word; max-height: 300px; overflow-y: auto; font-size: 0.9em;"><?php echo e($prompt['gemini_response']); ?></pre>
                                    </article>
                                </details>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </figure>
    <?php else: ?>
        <p>Você ainda não salvou nenhum prompt. <a href="<?php echo e(BASE_URL); ?>generator.php">Crie seu primeiro prompt agora!</a></p>
    <?php endif; ?>
</article>

<style>
/* Estilo opcional para a linha de detalhes */
.prompt-details-row td {
    padding-top: 0.25rem; /* Reduzido */
    padding-bottom: 1rem;
    border-top: 1px dashed var(--pico-muted-border-color); /* Linha tracejada para separar */
}
.prompt-details-row details > summary {
    padding: 0.25rem 0;
    outline: none; /* Remove o outline padrão do focus no Firefox */
}
.prompt-details-row details > summary:focus {
    /* Estilo de foco customizado se desejado, ou deixar o padrão do navegador */
}
.prompt-details-row details[open] > summary {
    margin-bottom: 0.5rem;
}
.prompt-details-row details > summary small {
    font-weight: normal;
}
/* Ajustar tamanho dos botões e ícones na tabela */
/* Os estilos inline nos botões já fazem isso, mas pode ser centralizado aqui se preferir */
</style>

<?php
require_once __DIR__ . '/../src/templates/footer.php';
?>