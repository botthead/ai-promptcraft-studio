<?php
// public/generator.php
$page_title = "Gerador de Prompts";

require_once __DIR__ . '/../src/core/auth.php';
require_login(); 

require_once __DIR__ . '/../src/config/database.php'; 
require_once __DIR__ . '/../src/templates/header.php'; // header.php já faz session_start() e define e()

// LOG PARA VER A SESSÃO QUANDO generator.php CARREGA
error_log("Sessão no início de generator.php (antes de qualquer lógica): " . print_r($_SESSION, true));

$form_data = $_SESSION['form_data_generator'] ?? []; // Para repopular formulário em caso de erro de validação do gerador
$user_id = $_SESSION['user_id'];
$has_gemini_api_key = false;

// Verificar se o usuário tem uma API Key do Gemini configurada
try {
    $stmt_key_check = $pdo->prepare("SELECT gemini_api_key_encrypted FROM users WHERE id = :user_id");
    $stmt_key_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_key_check->execute();
    $user_key_info = $stmt_key_check->fetch(PDO::FETCH_ASSOC);
    if ($user_key_info && !empty($user_key_info['gemini_api_key_encrypted'])) {
        $has_gemini_api_key = true;
    }
} catch (PDOException $e) {
    error_log("Erro ao verificar API Key do Gemini para user_id {$user_id}: " . $e->getMessage());
}

// Lógica para reutilizar prompt
if (isset($_GET['reuse_prompt_id']) && empty($_SESSION['form_data_generator']) /* Só carrega se não houver dados de um erro anterior de submissão DESTE formulário*/) {
    $reuse_prompt_id = filter_var($_GET['reuse_prompt_id'], FILTER_VALIDATE_INT);
    if ($reuse_prompt_id) {
        try {
            $sql_reuse = "SELECT title, input_parameters FROM prompts WHERE id = :prompt_id AND user_id = :user_id";
            $stmt_reuse = $pdo->prepare($sql_reuse);
            $stmt_reuse->bindParam(':prompt_id', $reuse_prompt_id, PDO::PARAM_INT);
            $stmt_reuse->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_reuse->execute();
            $prompt_to_reuse = $stmt_reuse->fetch(PDO::FETCH_ASSOC);

            if ($prompt_to_reuse) {
                $input_params_reused = json_decode($prompt_to_reuse['input_parameters'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($input_params_reused)) {
                    $form_data = array_merge($form_data, $input_params_reused); // Mescla, reutilizados têm prioridade
                     // Se o título não veio explicitamente do JSON de input_parameters, usa o título da tabela prompts
                    if (empty($form_data['prompt_title']) && !empty($prompt_to_reuse['title'])) {
                        // Verifica se o título da tabela não é o placeholder genérico
                        if (strpos($prompt_to_reuse['title'], 'Prompt Sem Título - ') !== 0) {
                           $form_data['prompt_title'] = $prompt_to_reuse['title'];
                        }
                    }
                    $_SESSION['success_message_generator'] = "Dados do prompt carregados para reutilização!";
                } else {
                    $_SESSION['error_message_generator'] = "Erro ao carregar os parâmetros do prompt para reutilização (JSON inválido).";
                    error_log("Erro ao decodificar JSON para reuse_prompt_id {$reuse_prompt_id}: " . json_last_error_msg());
                }
            } else {
                $_SESSION['error_message_generator'] = "Prompt para reutilização não encontrado ou você não tem permissão.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message_generator'] = "Erro de banco de dados ao buscar prompt para reutilização.";
            error_log("PDOException ao buscar reuse_prompt_id {$reuse_prompt_id}: " . $e->getMessage());
        }
    }
}

// Capturar o prompt gerado e os parâmetros da última geração (se vieram da action de gerar)
$generated_prompt_text_display = $_SESSION['generated_prompt_text'] ?? '';
$last_input_parameters_for_save = $_SESSION['last_input_parameters'] ?? null;

// Capturar resposta da API Gemini e erros da sessão
$gemini_api_response_display = $_SESSION['gemini_api_response'] ?? '';
$gemini_api_error_display = $_SESSION['gemini_api_error'] ?? '';

// Limpar da sessão APÓS capturar para exibição nesta requisição
if (isset($_SESSION['gemini_api_response'])) unset($_SESSION['gemini_api_response']);
if (isset($_SESSION['gemini_api_error'])) unset($_SESSION['gemini_api_error']);

?>

<article>
    <header>
        <h2><?php echo e($page_title); ?></h2>
        <p>Preencha os campos abaixo para criar seu prompt otimizado.</p>
    </header>

    <?php
    if (isset($_SESSION['error_message_generator'])) {
        echo '<div class="notice error" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-red); margin-bottom: 1rem; padding:0.75rem;"><p>' . e($_SESSION['error_message_generator']) . '</p></div>';
        unset($_SESSION['error_message_generator']);
    }
    if (isset($_SESSION['success_message_generator'])) {
        echo '<div class="notice success" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-green); margin-bottom: 1rem; padding:0.75rem;"><p>' . e($_SESSION['success_message_generator']) . '</p></div>';
        unset($_SESSION['success_message_generator']);
    }
    ?>

    <form id="prompt-generator-form" action="<?php echo e(BASE_URL); ?>../src/actions/generate_prompt_action.php" method="POST">
        <div class="grid">
            <label for="prompt_title">
                Título do Prompt (Opcional):
                <input type="text" id="prompt_title" name="prompt_title" 
                       value="<?php echo e($form_data['prompt_title'] ?? ''); ?>">
            </label>
            <label for="target_ia">
                IA Alvo (Ex: Gemini, ChatGPT):
                <input type="text" id="target_ia" name="target_ia" 
                       value="<?php echo e($form_data['target_ia'] ?? 'Gemini'); ?>">
            </label>
        </div>

        <label for="objective">
            Objetivo Principal do Prompt:
            <input type="text" id="objective" name="objective" required
                   placeholder="Ex: Criar um roteiro para vídeo, gerar um email de marketing, resumir um texto"
                   value="<?php echo e($form_data['objective'] ?? ''); ?>">
            <?php if (isset($_SESSION['form_errors_generator']['objective'])): ?>
                <small class="error-feedback" style="color: var(--pico-color-red-500);"><?php echo e($_SESSION['form_errors_generator']['objective']); ?></small><br>
            <?php endif; ?>
        </label>

        <div class="grid">
            <label for="audience">
                Público-Alvo:
                <input type="text" id="audience" name="audience"
                       placeholder="Ex: Iniciantes, especialistas, crianças de 10 anos"
                       value="<?php echo e($form_data['audience'] ?? ''); ?>">
            </label>
            <label for="tone_style">
                Tom/Estilo:
                <select id="tone_style" name="tone_style">
                    <option value="" <?php echo empty($form_data['tone_style']) ? 'selected' : ''; ?>>-- Selecione --</option>
                    <option value="Formal" <?php echo ($form_data['tone_style'] ?? '') == 'Formal' ? 'selected' : ''; ?>>Formal</option>
                    <option value="Informal" <?php echo ($form_data['tone_style'] ?? '') == 'Informal' ? 'selected' : ''; ?>>Informal</option>
                    <option value="Amigável" <?php echo ($form_data['tone_style'] ?? '') == 'Amigável' ? 'selected' : ''; ?>>Amigável</option>
                    <option value="Profissional" <?php echo ($form_data['tone_style'] ?? '') == 'Profissional' ? 'selected' : ''; ?>>Profissional</option>
                    <option value="Técnico" <?php echo ($form_data['tone_style'] ?? '') == 'Técnico' ? 'selected' : ''; ?>>Técnico</option>
                    <option value="Criativo" <?php echo ($form_data['tone_style'] ?? '') == 'Criativo' ? 'selected' : ''; ?>>Criativo</option>
                    <option value="Persuasivo" <?php echo ($form_data['tone_style'] ?? '') == 'Persuasivo' ? 'selected' : ''; ?>>Persuasivo</option>
                    <option value="Cômico" <?php echo ($form_data['tone_style'] ?? '') == 'Cômico' ? 'selected' : ''; ?>>Cômico</option>
                    <option value="Urgente" <?php echo ($form_data['tone_style'] ?? '') == 'Urgente' ? 'selected' : ''; ?>>Urgente</option>
                </select>
            </label>
        </div>

        <label for="keywords">
            Palavras-chave/Conceitos Essenciais (separados por vírgula):
            <input type="text" id="keywords" name="keywords"
                   placeholder="Ex: sustentabilidade, inovação, marketing digital"
                   value="<?php echo e($form_data['keywords'] ?? ''); ?>">
        </label>

        <label for="output_format">
            Formato de Saída Desejado:
            <select id="output_format" name="output_format">
                <option value="" <?php echo empty($form_data['output_format']) ? 'selected' : ''; ?>>-- Selecione --</option>
                <option value="Parágrafos" <?php echo ($form_data['output_format'] ?? '') == 'Parágrafos' ? 'selected' : ''; ?>>Parágrafos</option>
                <option value="Lista com marcadores" <?php echo ($form_data['output_format'] ?? '') == 'Lista com marcadores' ? 'selected' : ''; ?>>Lista com marcadores</option>
                <option value="Lista numerada" <?php echo ($form_data['output_format'] ?? '') == 'Lista numerada' ? 'selected' : ''; ?>>Lista numerada</option>
                <option value="Tabela" <?php echo ($form_data['output_format'] ?? '') == 'Tabela' ? 'selected' : ''; ?>>Tabela</option>
                <option value="Bloco de código" <?php echo ($form_data['output_format'] ?? '') == 'Bloco de código' ? 'selected' : ''; ?>>Bloco de código</option>
                <option value="Diálogo" <?php echo ($form_data['output_format'] ?? '') == 'Diálogo' ? 'selected' : ''; ?>>Diálogo</option>
                <option value="Email" <?php echo ($form_data['output_format'] ?? '') == 'Email' ? 'selected' : ''; ?>>Email</option>
                <option value="Roteiro" <?php echo ($form_data['output_format'] ?? '') == 'Roteiro' ? 'selected' : ''; ?>>Roteiro</option>
            </select>
        </label>

        <label for="context_restrictions">
            Contexto Adicional, Exemplos ou Restrições (o que incluir/evitar):
            <textarea id="context_restrictions" name="context_restrictions" rows="5"
                      placeholder="Ex: Evitar jargões técnicos. Incluir um call-to-action no final. Mencionar o produto X."><?php echo e($form_data['context_restrictions'] ?? ''); ?></textarea>
        </label>
            
        <div class="grid">
            <button type="submit" name="action" value="generate">Gerar Novo Prompt</button>
        </div>
    </form>

    <hr>

    <section id="generated-prompt-interaction-section" style="margin-top: 2rem; <?php echo empty($generated_prompt_text_display) ? 'display: none;' : ''; ?>">
        <h3>Prompt Gerado:</h3>
        <article>
            <pre><code id="generated-prompt-text-display" style="white-space: pre-wrap; word-wrap: break-word;"><?php echo e($generated_prompt_text_display); ?></code></pre>
        </article>
        
        <div style="margin-top: 1rem; margin-bottom:1.5rem;" class="grid">
            <button onclick="copyToClipboard('generated-prompt-text-display')" class="secondary outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 5px;"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/></svg>
                Copiar Prompt
            </button>

            <?php if ($has_gemini_api_key): ?>
                <button id="send-to-gemini-btn" class="contrast">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-stars" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 5px;"><path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.593 5.3l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.3A1.73 1.73 0 0 0 2.31 4.207l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.407 2.31zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774.258c.346-.115.617-.386.732-.732z"/></svg>
                    Enviar para Gemini
                </button>
            <?php else: ?>
                <button disabled title="Configure sua API Key do Gemini no Perfil para usar esta funcionalidade.">Enviar para Gemini</button>
                <small><a href="<?php echo e(BASE_URL); ?>profile.php">Configure sua API Key</a></small>
            <?php endif; ?>
        </div>
        
        <div id="gemini-spinner" style="display: none; text-align: center; margin: 20px 0;">
            <article aria-busy="true">Consultando Gemini, por favor aguarde...</article>
        </div>

        <!-- Área para exibir a resposta da API Gemini -->
        <div id="gemini-response-area" style="margin-top: 1.5rem;">
            <?php if (!empty($gemini_api_error_display)): ?>
                <div class="notice error" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-red); padding:0.75rem;"><p><strong>Erro ao contatar Gemini:</strong> <?php echo e($gemini_api_error_display); ?></p></div>
            <?php endif; ?>
            <?php if (!empty($gemini_api_response_display)): ?>
                <h4>Resposta do Gemini:</h4>
                <article><pre style="white-space: pre-wrap; word-wrap: break-word;"><?php echo e($gemini_api_response_display); ?></pre></article>
            <?php endif; ?>
        </div>

        <?php if (!empty($generated_prompt_text_display) && $last_input_parameters_for_save !== null): ?>
        <form action="<?php echo e(BASE_URL); ?>../src/actions/save_prompt_action.php" method="POST" style="margin-top: 20px; border-top: 1px dashed var(--muted-border-color); padding-top: 20px;">
            <input type="hidden" name="prompt_title_to_save" value="<?php echo e($last_input_parameters_for_save['prompt_title'] ?? ''); ?>">
            <input type="hidden" name="input_parameters_json" value="<?php echo e(json_encode($last_input_parameters_for_save)); ?>">
            <input type="hidden" name="generated_prompt_text_to_save" value="<?php echo e($generated_prompt_text_display); ?>">
            
            <label for="save_title_override">Salvar este prompt no histórico com o título (opcional):</label>
            <input type="text" name="save_title_override" value="<?php echo e($last_input_parameters_for_save['prompt_title'] ?? ''); ?>" placeholder="Título para o histórico">
            
            <button type="submit" name="action" value="save_generated">Salvar no Histórico</button>
        </form>
        <?php endif; ?>
    </section>

</article>

<script>
function copyToClipboard(elementId) {
    const textToCopy = document.getElementById(elementId).innerText;
    if (!textToCopy) {
        alert('Nada para copiar.');
        return;
    }
    navigator.clipboard.writeText(textToCopy).then(function() {
        alert('Texto copiado para a área de transferência!');
    }, function(err) {
        console.error('Erro ao copiar: ', err);
        alert('Falha ao copiar. Verifique as permissões do navegador ou copie manually.');
    });
}

const sendToGeminiBtn = document.getElementById('send-to-gemini-btn');
if (sendToGeminiBtn) {
    sendToGeminiBtn.addEventListener('click', function() {
        const promptText = document.getElementById('generated-prompt-text-display').innerText;
        const spinner = document.getElementById('gemini-spinner');
        const responseArea = document.getElementById('gemini-response-area');

        if (!promptText.trim()) {
            alert('Não há prompt gerado para enviar.');
            return;
        }

        responseArea.innerHTML = ''; // Limpar respostas/erros anteriores
        spinner.style.display = 'block';
        sendToGeminiBtn.setAttribute('aria-busy', 'true');
        sendToGeminiBtn.disabled = true;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo e(BASE_URL); ?>../src/actions/call_gemini_api_action.php';

        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'prompt_text_to_gemini';
        hiddenField.value = promptText;
        form.appendChild(hiddenField);

        document.body.appendChild(form);
        form.submit();
    });
}
</script>

<?php
// Limpeza final de sessões que são específicas para o fluxo de geração/reutilização DESTE formulário
if (isset($_SESSION['form_data_generator'])) unset($_SESSION['form_data_generator']);
if (isset($_SESSION['form_errors_generator'])) unset($_SESSION['form_errors_generator']);

// Limpa o prompt gerado e os parâmetros da última GERAÇÃO se o usuário está apenas
// visitando a página (GET) e não vindo de uma ação de reutilização ou de uma submissão POST
// que resultou na exibição destes dados. As respostas da API já foram limpas acima.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['reuse_prompt_id'])) {
    if (isset($_SESSION['generated_prompt_text'])) unset($_SESSION['generated_prompt_text']);
    if (isset($_SESSION['last_input_parameters'])) unset($_SESSION['last_input_parameters']);
}

require_once __DIR__ . '/../src/templates/footer.php';
?>