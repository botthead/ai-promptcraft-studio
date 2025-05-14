 
<?php
// public/history.php
$page_title = "Histórico de Prompts";

require_once __DIR__ . '/../src/core/auth.php';
require_login(); // Garante que apenas usuários logados acessem

require_once __DIR__ . '/../src/config/database.php'; // Para acesso ao $pdo
// require_once __DIR__ . '/../src/core/functions.php'; // Para e() se não estiver no header

require_once __DIR__ . '/../src/templates/header.php';

// Buscar prompts do usuário logado
$user_id = $_SESSION['user_id'];
$prompts = []; // Inicializa como array vazio

try {
    // TODO: Adicionar paginação no futuro
    $sql = "SELECT id, title, generated_prompt_text, created_at 
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
    // Você pode optar por não redirecionar e exibir a mensagem na própria página,
    // mas se for um erro crítico, redirecionar ou mostrar uma página de erro pode ser melhor.
    // Por ora, vamos deixar a mensagem ser exibida e a página mostrar "Nenhum prompt".
}
?>

<article>
    <header>
        <h2><?php echo e($page_title); ?></h2>
        <p>Aqui você pode ver todos os prompts que você gerou e salvou.</p>
    </header>

    <?php if (!empty($_SESSION['success_message_history'])): ?>
        <div class="notice success" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-green); margin-bottom: 1rem;"> <!-- Usando classes PicoCSS implícitas -->
            <p><?php echo e($_SESSION['success_message_history']); ?></p>
        </div>
        <?php unset($_SESSION['success_message_history']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error_message_history'])): ?>
        <div class="notice error" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-red); margin-bottom: 1rem;">
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
                        <th scope="col">Início do Prompt</th>
                        <th scope="col">Criado em</th>
                        <th scope="col" style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prompts as $prompt): ?>
                        <tr>
                            <td><?php echo e($prompt['title'] ?: '<em>Sem Título</em>'); ?></td>
                            <td>
                                <?php
                                // Exibir apenas uma parte do prompt para economizar espaço
                                $preview_length = 100; // Número de caracteres para o preview
                                $prompt_preview = mb_substr(strip_tags($prompt['generated_prompt_text']), 0, $preview_length);
                                if (mb_strlen($prompt['generated_prompt_text']) > $preview_length) {
                                    $prompt_preview .= '...';
                                }
                                echo e($prompt_preview);
                                ?>
                            </td>
                            <td>
                                <?php
                                // Formatar a data
                                $date = new DateTime($prompt['created_at']);
                                echo e($date->format('d/m/Y H:i'));
                                ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <!-- Botão para reutilizar (leva para generator.php com dados) -->
                                <a href="<?php echo e(BASE_URL); ?>generator.php?reuse_prompt_id=<?php echo (int)$prompt['id']; ?>" 
                                   role="button" class="outline secondary" title="Reutilizar este Prompt">
                                   <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16" style="vertical-align: text-bottom;">
                                      <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                                      <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                                   </svg>
                                   Reutilizar
                                </a>
                                
                                <!-- Formulário para Excluir -->
                                <form action="<?php echo e(BASE_URL); ?>../src/actions/delete_prompt_action.php" method="POST" style="display: inline-block; margin-left: 5px;">
                                    <input type="hidden" name="prompt_id_to_delete" value="<?php echo (int)$prompt['id']; ?>">
                                    <button type="submit" class="outline contrast" title="Excluir este Prompt" 
                                            onclick="return confirm('Tem certeza que deseja excluir este prompt? Esta ação não pode ser desfeita.');">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16" style="vertical-align: text-bottom;">
                                          <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                          <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                        </svg>
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </figure>
    <?php else: ?>
        <p>Você ainda não salvou nenhum prompt. <a href="<?php echo e(BASE_URL); ?>generator.php">Crie seu primeiro prompt agora!</a></p>
    <?php endif; ?>

</article>

<?php
require_once __DIR__ . '/../src/templates/footer.php';
?>