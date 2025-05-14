<?php
// public/generator.php
$page_title = "Gerador de Prompts";

require_once __DIR__ . '/../src/Core/auth.php';
require_login();

require_once __DIR__ . '/../src/Config/database.php';
require_once __DIR__ . '/../src/Config/Constants.php'; // Inclui BASE_URL e ENCRYPTION_KEY
require_once __DIR__ . '/../src/Core/functions.php';   // Inclui e() e decryptData()

// header.php já faz session_start() e define e()
require_once __DIR__ . '/../src/Templates/header.php';

error_log("Sessão no início de generator.php: " . print_r($_SESSION, true));

// Recupera dados do formulário da sessão (usados após redirect de generate_prompt_action)
$form_data = $_SESSION['form_data_generator'] ?? [];
$user_id = $_SESSION['user_id'];
$has_gemini_api_key = false;

// Verifica se o usuário tem uma API Key Gemini configurada
try {
    $stmt_key_check = $pdo->prepare("SELECT gemini_api_key_encrypted FROM users WHERE id = :user_id");
    $stmt_key_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_key_check->execute();
    $user_key_info = $stmt_key_check->fetch(PDO::FETCH_ASSOC);
    if ($user_key_info && !empty($user_key_info['gemini_api_key_encrypted'])) {
        // Apenas verifica se existe, a descriptografia será feita na action segura
        $has_gemini_api_key = true;
    }
} catch (PDOException $e) {
    error_log("Erro ao verificar API Key do Gemini para user_id {$user_id}: " . $e->getMessage());
    // Não define has_gemini_api_key como true em caso de erro DB
}

// Lógica para reutilizar um prompt do histórico via GET parameter
if (isset($_GET['reuse_prompt_id']) && empty($_SESSION['form_data_generator'])) {
    $reuse_prompt_id = filter_var($_GET['reuse_prompt_id'], FILTER_VALIDATE_INT);
    if ($reuse_prompt_id) {
        try {
            // Busca o prompt no histórico, garantindo que pertence ao usuário logado
            $sql_reuse = "SELECT prompt_title, input_parameters FROM prompts_history WHERE id = :prompt_id AND user_id = :user_id";
            $stmt_reuse = $pdo->prepare($sql_reuse);
            $stmt_reuse->bindParam(':prompt_id', $reuse_prompt_id, PDO::PARAM_INT);
            $stmt_reuse->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_reuse->execute();
            $prompt_to_reuse = $stmt_reuse->fetch(PDO::FETCH_ASSOC);

            if ($prompt_to_reuse) {
                $input_params_reused = json_decode($prompt_to_reuse['input_parameters'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($input_params_reused)) {
                    // Mescla os parâmetros salvos no formulário atual
                    $form_data = array_merge($form_data, $input_params_reused);
                    // Carrega o título, se não for um título padrão e o campo estiver vazio
                    if (empty($form_data['prompt_title']) && !empty($prompt_to_reuse['prompt_title']) && strpos($prompt_to_reuse['prompt_title'], 'Prompt Sem Título - ') !== 0 && strpos($prompt_to_reuse['prompt_title'], 'Prompt Gerado') !== 0) {
                       $form_data['prompt_title'] = $prompt_to_reuse['prompt_title'];
                    }
                    $_SESSION['success_message_generator'] = "Dados do prompt carregados para reutilização!";
                } else {
                    $_SESSION['error_message_generator'] = "Erro ao carregar os parâmetros do prompt para reutilização (JSON inválido ou nulo).";
                     error_log("Erro JSON input_parameters para prompt_id {$reuse_prompt_id}: " . json_last_error_msg());
                }
            } else {
                $_SESSION['error_message_generator'] = "Prompt para reutilização não encontrado ou não permitido para o seu usuário.";
                 error_log("Tentativa de reutilizar prompt ID {$reuse_prompt_id} por user ID {$user_id} falhou.");
            }
        } catch (PDOException $e) {
            $_SESSION['error_message_generator'] = "Erro de banco de dados ao buscar prompt para reutilização.";
             error_log("Erro DB ao buscar prompt para reutilização: " . $e->getMessage());
        }
        // Remove o parâmetro da URL para evitar recarregamento infinito
        header("Location: " . BASE_URL . "public/generator.php");
        exit;
    }
}

// Recupera o prompt gerado e os últimos parâmetros usados (para salvar no histórico) da sessão
$generated_prompt_text_display = $_SESSION['generated_prompt_text'] ?? '';
$last_input_parameters_for_save = $_SESSION['last_input_parameters'] ?? null; // Contém todos os campos do formulário da ÚLTIMA submissão para gerar o prompt
$last_gemini_response_for_save = $_SESSION['last_gemini_api_response_for_save'] ?? null; // Contém a resposta da API Gemini da ÚLTIMA chamada AJAX

// Limpar sessões específicas do fluxo de geração síncrona/API (serão usadas apenas para popular a página APÓS um redirect)
// O fluxo AJAX subsequente (Enviar para Gemini) não usa estas sessões diretamente.
if (isset($_SESSION['form_data_generator'])) unset($_SESSION['form_data_generator']);
if (isset($_SESSION['form_errors_generator'])) unset($_SESSION['form_errors_generator']);
if (isset($_SESSION['generated_prompt_text'])) unset($_SESSION['generated_prompt_text']);
// Manter last_input_parameters e last_gemini_api_response_for_save se a página foi carregada VIA POST (após generate_prompt_action)
// Se for um GET normal, limpar para não mostrar dados antigos.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['reuse_prompt_id'])) {
     if (isset($_SESSION['last_input_parameters'])) {
        error_log("Limpando last_input_parameters no GET generator.php");
        unset($_SESSION['last_input_parameters']);
     }
     if (isset($_SESSION['last_gemini_api_response_for_save'])) {
         error_log("Limpando last_gemini_api_response_for_save no GET generator.php");
         unset($_SESSION['last_gemini_api_response_for_save']);
     }
}


error_log("Sessão antes de renderizar o formulário em generator.php: " . print_r($_SESSION, true));

?>

<article>
    <header>
        <h2><?php echo e($page_title); ?></h2>
        <p>Preencha os campos abaixo para criar seu prompt otimizado.</p>
    </header>

    <?php
    // Exibe mensagens de erro ou sucesso da sessão
    if (isset($_SESSION['error_message_generator'])) {
        echo '<div class="notice error" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-red); margin-bottom: 1rem; padding:0.75rem;"><p>' . e($_SESSION['error_message_generator']) . '</p></div>';
        unset($_SESSION['error_message_generator']);
    }
    if (isset($_SESSION['success_message_generator'])) {
        echo '<div class="notice success" style="background-color: var(--pico-form-element-background-color); border-left-color: var(--pico-color-green); margin-bottom: 1rem; padding:0.75rem;"><p>' . e($_SESSION['success_message_generator']) . '</p></div>';
        unset($_SESSION['success_message_generator']);
    }
    ?>

    <form id="prompt-generator-form" action="<?php echo e(BASE_URL); ?>src/Actions/generate_prompt_action.php" method="POST">
        <div class="grid">
            <label for="prompt_title">
                Título do Prompt (Opcional):
                <input type="text" id="prompt_title" name="prompt_title"
                       value="<?php echo e($form_data['prompt_title'] ?? ''); ?>"
                       placeholder="Um título para identificar seu prompt">
            </label>
            <label for="target_ia">
                IA Alvo (Ex: Gemini, ChatGPT):
                <input type="text" id="target_ia" name="target_ia"
                       value="<?php echo e($form_data['target_ia'] ?? 'Gemini'); ?>" required>
            </label>
        </div>

        <label for="objective">
            Objetivo Principal do Prompt:
            <input type="text" id="objective" name="objective" required
                   placeholder="Ex: Criar um roteiro para vídeo, gerar um email de marketing, resumir um texto"
                   value="<?php echo e($form_data['objective'] ?? ''); ?>">
            <?php if (isset($_SESSION['form_errors_generator']['objective'])): ?>
                <small class="error-feedback" style="color: var(--pico-color-red-500); display: block; margin-top: -0.5rem; margin-bottom: 0.5rem;"><?php echo e($_SESSION['form_errors_generator']['objective']); ?></small>
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
                    <option value="Formal" <?php echo (($form_data['tone_style'] ?? '') === 'Formal') ? 'selected' : ''; ?>>Formal</option>
                    <option value="Informal" <?php echo (($form_data['tone_style'] ?? '') === 'Informal') ? 'selected' : ''; ?>>Informal</option>
                    <option value="Amigável" <?php echo (($form_data['tone_style'] ?? '') === 'Amigável') ? 'selected' : ''; ?>>Amigável</option>
                    <option value="Profissional" <?php echo (($form_data['tone_style'] ?? '') === 'Profissional') ? 'selected' : ''; ?>>Profissional</option>
                    <option value="Técnico" <?php echo (($form_data['tone_style'] ?? '') === 'Técnico') ? 'selected' : ''; ?>>Técnico</option>
                    <option value="Criativo" <?php echo (($form_data['tone_style'] ?? '') === 'Criativo') ? 'selected' : ''; ?>>Criativo</option>
                    <option value="Persuasivo" <?php echo (($form_data['tone_style'] ?? '') === 'Persuasivo') ? 'selected' : ''; ?>>Persuasivo</option>
                    <option value="Cômico" <?php echo (($form_data['tone_style'] ?? '') === 'Cômico') ? 'selected' : ''; ?>>Cômico</option>
                    <option value="Urgente" <?php echo (($form_data['tone_style'] ?? '') === 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
                     <option value="Neutro" <?php echo (($form_data['tone_style'] ?? '') === 'Neutro') ? 'selected' : ''; ?>>Neutro</option>
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
                <option value="Parágrafos" <?php echo (($form_data['output_format'] ?? '') === 'Parágrafos') ? 'selected' : ''; ?>>Parágrafos</option>
                <option value="Lista com marcadores" <?php echo (($form_data['output_format'] ?? '') === 'Lista com marcadores') ? 'selected' : ''; ?>>Lista com marcadores</option>
                <option value="Lista numerada" <?php echo (($form_data['output_format'] ?? '') === 'Lista numerada') ? 'selected' : ''; ?>>Lista numerada</option>
                <option value="Tabela" <?php echo (($form_data['output_format'] ?? '') === 'Tabela') ? 'selected' : ''; ?>>Tabela</option>
                <option value="Bloco de código" <?php echo (($form_data['output_format'] ?? '') === 'Bloco de código') ? 'selected' : ''; ?>>Bloco de código</option>
                <option value="Diálogo" <?php echo (($form_data['output_format'] ?? '') === 'Diálogo') ? 'selected' : ''; ?>>Diálogo</option>
                <option value="Email" <?php echo (($form_data['output_format'] ?? '') === 'Email') ? 'selected' : ''; ?>>Email</option>
                <option value="Roteiro" <?php echo (($form_data['output_format'] ?? '') === 'Roteiro') ? 'selected' : ''; ?>>Roteiro</option>
                 <option value="JSON" <?php echo (($form_data['output_format'] ?? '') === 'JSON') ? 'selected' : ''; ?>>JSON</option>
                 <option value="XML" <?php echo (($form_data['output_format'] ?? '') === 'XML') ? 'selected' : ''; ?>>XML</option>
            </select>
        </label>

         <label for="language">
            Idioma Desejado para a Saída:
            <input type="text" id="language" name="language"
                   placeholder="Ex: Português (Brasil), Inglês, Espanhol"
                   value="<?php echo e($form_data['language'] ?? 'Português (Brasil)'); ?>">
        </label>


        <label for="context_restrictions">
            Contexto Adicional, Exemplos ou Restrições (o que incluir/evitar):
            <textarea id="context_restrictions" name="context_restrictions" rows="5"
                      placeholder="Ex: Evitar jargões técnicos. Incluir um call-to-action no final. Mencionar o produto X."><?php echo e($form_data['context_restrictions'] ?? ''); ?></textarea>
        </label>

        <div class="grid">
            <button type="submit" name="action" value="generate">Gerar Novo Prompt (Visualização)</button>
        </div>
         <small>Use o botão acima para visualizar o prompt gerado com base nos seus inputs. Use o botão "Enviar para Gemini" (abaixo) para usar o prompt gerado e obter uma resposta da IA (requer chave API).</small>
    </form>

    <hr>

    <!-- Seção de interação com o prompt gerado e API Gemini -->
    <section id="generated-prompt-interaction-section" style="margin-top: 2rem; <?php echo empty($generated_prompt_text_display) ? 'display: none;' : ''; ?>">
        <h3>Prompt Gerado (para copiar ou enviar para IA):</h3>
        <article>
             <!-- O ID é 'generated-prompt-text-display' para facilitar a cópia -->
            <pre><code id="generated-prompt-text-display" style="white-space: pre-wrap; word-wrap: break-word;"><?php echo e($generated_prompt_text_display); ?></code></pre>
        </article>

        <div style="margin-top: 1rem; margin-bottom:1.5rem;" class="grid">
            <button onclick="copyToClipboard('generated-prompt-text-display')" class="secondary outline">
                <svg xmlns="http://www.w3.org/20/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 5px;"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/></svg>
                Copiar Prompt
            </button>

            <?php if ($has_gemini_api_key): ?>
                <button id="send-to-gemini-btn" class="contrast">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-stars" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 5px;"><path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.593 5.3l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.3A1.73 1.73 0 0 0 2.31 4.207l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.407 2.31zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774.258c.346-.115.617-.386.732-.732z"/></svg>
                    Enviar para Gemini
                </button>
            <?php else: ?>
                <button disabled title="Configure sua API Key do Gemini no Perfil para usar esta funcionalidade.">Enviar para Gemini</button>
                <small><a href="<?php echo e(BASE_URL); ?>public/profile.php">Configure sua API Key</a></small>
            <?php endif; ?>
        </div>

         <!-- Formulário oculto para salvar o prompt gerado (não a resposta da IA) -->
         <!-- Este formulário salva apenas o prompt gerado e os inputs que o criaram -->
        <?php if (!empty($generated_prompt_text_display) && $last_input_parameters_for_save !== null): ?>
        <form id="savePromptForm" action="<?php echo e(BASE_URL); ?>src/Actions/save_prompt_action.php" method="POST" style="margin-top: 20px; border-top: 1px dashed var(--muted-border-color); padding-top: 20px;">
            <!-- Passa os parâmetros que geraram este prompt -->
             <input type="hidden" name="input_parameters_json" value="<?php echo e(json_encode($last_input_parameters_for_save)); ?>">
             <!-- Passa o texto do prompt gerado para salvar -->
            <input type="hidden" name="generated_prompt_text_to_save" value="<?php echo e($generated_prompt_text_display); ?>">
            <!-- Opcional: passa a resposta da API se houver (embora o botão no modal seja o ideal para isso) -->
             <?php if ($last_gemini_response_for_save): ?>
                 <input type="hidden" name="gemini_response_to_save" value="<?php echo e(json_encode($last_gemini_response_for_save)); ?>">
             <?php endif; ?>


            <label for="save_title_override">Salvar o **Prompt Gerado** no histórico com o título (opcional):</label>
            <input type="text" name="save_title_override" value="<?php echo e($last_input_parameters_for_save['prompt_title'] ?? ''); ?>" placeholder="Título para o histórico (ex: Prompt para Roteiro de Vídeo)">

            <button type="submit" name="action" value="save_generated_prompt_text" class="secondary">Salvar Prompt Gerado</button>
             <small>Isso salva apenas o texto do prompt gerado e os parâmetros que você usou acima. Para salvar a resposta da IA, use o botão 'Salvar Resposta' na janela que aparece após 'Enviar para Gemini'.</small>
        </form>
        <?php endif; ?>

    </section>
</article> <!-- Fim do <article> principal da página -->

<!-- Modal para exibir a resposta da API Gemini -->
<!-- Use classes Bootstrap para o modal -->
<div class="modal fade" id="geminiResponseModal" tabindex="-1" aria-labelledby="geminiModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="geminiModalTitle">Resposta da Gemini API</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalBody">
        <!-- Conteúdo dinâmico será carregado aqui (spinner, resposta da API ou erro) -->
         <div id="gemini-spinner-modal" style="text-align: center;">
            <article aria-busy="true">Consultando Gemini, por favor aguarde...</article>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <!-- Botões de ação como "Copiar Resposta" ou "Salvar Resposta" serão adicionados dinamicamente ao modalBody -->
         <!-- Exemplo (será inserido via JS): -->
         <!-- <button class="btn btn-secondary btn-sm" onclick="copyModalContent()">Copiar Resposta</button> -->
         <!-- <button id="saveGeminiResponseBtn" class="btn btn-primary btn-sm">Salvar Resposta</button> -->
      </div>
    </div>
  </div>
</div>


<?php
// Agora incluímos o footer que contém a tag </body>, </html> e **os scripts Bootstrap**.
require_once __DIR__ . '/../src/Templates/footer.php';
// Scripts que dependem de Bootstrap devem vir APÓS esta linha.
?>

<!-- Bloco de Script ÚNICO para esta página -->
<script>
    // Define a BASE_URL globalmente para uso nos scripts JS
    // ISSO DEVE SER FEITO EM UM ARQUIVO .PHP OU EM UM BLOCO <script> NO ARQUIVO .PHP
    // ANTES DE QUALQUER SCRIPT EXTERNO OU BLOCO QUE PRECISE DELA.
    // Como este script está no final do arquivo PHP, ele será executado DEPOIS do footer
    // que carrega o Bootstrap, e a variável BASE_URL_JS estará disponível para ele.
    const BASE_URL_JS = '<?php echo e(BASE_URL); ?>'; // PHP renderiza este valor

    // Função de log JS (como antes)
    function error_log_js(message) { console.log("[GENERATOR.JS LOG] " + message); }
    error_log_js("Script de generator.php (após footer) está sendo executado.");

    // Funções auxiliares (se não estiverem já definidas globalmente em um script anterior)
    // Estas são versões JS simples das funções PHP e() e nl2br()
    window.e = function(text) { // Simula htmlspecialchars para JS
        if (text === null || text === undefined) return '';
        return String(text).replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"').replace(/'/g, ''');
    };
     window.nl2br = function(text) { // Simula nl2br para JS
        if (text === null || text === undefined) return '';
        return String(text).replace(/\n/g, '<br>');
    };
     // Função para copiar texto de um elemento
     window.copyToClipboard = function(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            const textToCopy = element.innerText;
            navigator.clipboard.writeText(textToCopy).then(function() {
                alert('Texto copiado para a área de transferência!');
            }, function(err) {
                console.error('Erro ao copiar texto: ', err);
                alert('Erro ao copiar texto. Por favor, copie manualmente.');
            });
        } else {
            error_log_js(`Elemento com ID '${elementId}' não encontrado para copiar.`);
        }
    };
    // Função para copiar o conteúdo dentro do modalBody (Resposta da IA)
    window.copyModalContent = function() {
       copyToClipboard('gemini-response-content'); // gemini-response-content será o ID de onde a resposta da IA é exibida dentro do modalBody
    };


    // Seleção de Elementos DOM
    const promptGeneratorForm = document.getElementById('prompt-generator-form'); // O formulário principal de inputs
    const sendToGeminiBtn = document.getElementById('send-to-gemini-btn'); // Botão para enviar para API
    const generatedPromptSection = document.getElementById('generated-prompt-interaction-section'); // Seção de resultado gerado
    const generatedPromptTextDisplay = document.getElementById('generated-prompt-text-display'); // Elemento <pre><code>
    const savePromptForm = document.getElementById('savePromptForm'); // Formulário para salvar o prompt gerado

    // Elementos do Modal (requer Bootstrap JS)
    const geminiModalElement = document.getElementById('geminiResponseModal'); // O elemento div principal do modal
    const modalBody = document.getElementById('modalBody'); // O div onde a resposta será exibida
    const modalTitle = document.getElementById('geminiModalTitle'); // O título do modal

    let geminiModal = null; // Variável para a instância do Modal Bootstrap

    // Garante que o DOM está pronto antes de instanciar o Modal ou adicionar listeners
    // Embora o script esteja no final do body, DOMContentLoaded ainda é boa prática.
    document.addEventListener('DOMContentLoaded', function() {
        error_log_js("DOMContentLoaded disparado.");

        // Instanciar o Modal Bootstrap APÓS o DOM estar pronto e Bootstrap JS carregado
        if (geminiModalElement) {
            try {
                // AGORA bootstrap DEVE ESTAR DEFINIDO porque footer.php (com Bootstrap JS) foi incluído antes deste script
                geminiModal = new bootstrap.Modal(geminiModalElement);
                error_log_js("Instância do Modal Bootstrap criada.");
            } catch (e) {
                error_log_js("ERRO ao instanciar Modal Bootstrap: " + e.message);
                console.error(e); // Loga o erro JS completo
            }
        } else {
            error_log_js("Elemento do Modal (geminiResponseModal) não encontrado.");
        }

        // Event Listener para o Botão "Enviar para Gemini"
        if (sendToGeminiBtn && geminiModalElement && modalBody && geminiModal) {
            error_log_js("Todos os elementos para o listener 'sendToGeminiBtn' foram encontrados. Adicionando listener...");
            sendToGeminiBtn.addEventListener('click', function(e) {
                e.preventDefault();

                error_log_js("'Enviar para Gemini' CLICADO!");

                // Certifica-se que há um prompt gerado para enviar
                const promptTextToSend = generatedPromptTextDisplay.innerText.trim();
                 if (!promptTextToSend) {
                     modalBody.innerHTML = '<p class="text-warning">Por favor, gere o prompt primeiro usando o botão "Gerar Novo Prompt (Visualização)".</p>';
                      if (geminiModal) geminiModal.show(); // Mostra o modal com a mensagem
                     return; // Sai da função
                 }


                // Desabilita botão e atualiza modal body
                sendToGeminiBtn.disabled = true;
                sendToGeminiBtn.setAttribute('aria-busy', 'true'); // Adiciona indicador de busy
                modalTitle.textContent = 'Consultando Gemini...'; // Atualiza título do modal
                modalBody.innerHTML = `
                    <div id="gemini-spinner-modal" style="text-align: center;">
                         <article aria-busy="true">Enviando prompt e aguardando resposta da IA...</article>
                     </div>
                `; // Limpa conteúdo anterior e mostra spinner/mensagem

                // Mostra o modal imediatamente para feedback visual
                if (geminiModal) {
                     error_log_js("geminiModal.show() chamado antes da requisição AJAX.");
                     geminiModal.show();
                }


                // Coleta os inputs originais do formulário para enviar junto (para histórico)
                // Isso garante que salvamos no histórico COM os inputs exatos que geraram o prompt.
                const formData = new FormData();
                 // Itera sobre os elementos do formulário principal e adiciona ao FormData
                 // Exclui botões, etc., foca em inputs de dados
                 const formElements = promptGeneratorForm.querySelectorAll('input, select, textarea');
                 const inputParameters = {}; // Objeto para armazenar inputs originais

                 formElements.forEach(element => {
                      if (element.name && element.value && element.type !== 'submit' && element.type !== 'button') {
                            formData.append(element.name, element.value);
                            inputParameters[element.name] = element.value; // Salva para o JSON input_parameters
                      } else if (element.name && element.type === 'select-one' && element.value === "") {
                           // Lida com selects vazios se necessário salvar
                           formData.append(element.name, "");
                           inputParameters[element.name] = "";
                      }
                 });


                // Adiciona o prompt de texto gerado visualizado na tela
                formData.append('generated_prompt_text', promptTextToSend); // Este é o texto real enviado para a API

                // Adiciona a ação para o endpoint da API (importante para api.php rotear)
                formData.append('action', 'call_gemini'); // Indica a ação para api.php

                // Adiciona os parâmetros de input originais como um JSON string (para salvar no histórico)
                 formData.append('input_parameters_json', JSON.stringify(inputParameters));


                error_log_js(`Enviando requisição AJAX para ${BASE_URL_JS}public/api.php com action=${formData.get('action')}`);
                error_log_js("Dados FormData keys: " + Array.from(formData.keys()).join(', '));
                // Note: Não logar formData.values() diretamente, pois pode conter a chave API ou dados sensíveis se não for cauteloso.

                // Usa a Fetch API para a requisição AJAX
                fetch(BASE_URL_JS + 'public/api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    error_log_js(`Fetch response status: ${response.status}`);
                    // Verifica se a resposta foi bem sucedida no nível HTTP (status 2xx)
                    if (!response.ok) {
                        // Se não foi OK, tenta ler a resposta como texto para logar
                         return response.text().then(text => {
                            // Lança um erro com o status e o texto da resposta para ser pego pelo .catch()
                            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}. Resposta: ${text}`);
                         });
                    }
                    // Se foi OK (2xx), assume que a resposta é JSON e a parseia
                    return response.json();
                })
                .then(data => {
                    // Processa a resposta JSON recebida do PHP (do api.php que incluiu call_gemini_api_action.php)
                    error_log_js("Resposta JSON do Backend recebida:");
                    console.log(data); // Loga a estrutura completa da resposta

                    modalTitle.textContent = 'Resposta da Gemini API'; // Restaura o título do modal

                    if (data.success) {
                        // Se o backend retornou sucesso (chamada da API e salvamento de histórico foram bem-sucedidos ou tratados)
                        modalBody.innerHTML = `
                            <p><strong>Prompt Enviado para IA:</strong></p>
                            <pre style="white-space: pre-wrap; word-wrap: break-word; max-height: 150px; overflow-y: auto; border: 1px solid var(--pico-muted-border-color); padding: 0.75rem;">${e(data.prompt_text_sent)}</pre>
                            <p><strong>Resposta da ${e(data.model_used || 'IA')}:</strong></p>
                            <div id="gemini-response-content" style="white-space: pre-wrap; word-wrap: break-word; max-height: 300px; overflow-y: auto;"><p>${nl2br(e(data.generated_text))}</p></div>
                            <div style="margin-top: 1rem;">
                                <button class="secondary outline btn-sm" onclick="copyModalContent()">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 5px;"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/></svg>
                                     Copiar Resposta
                                </button>
                                <!-- Botão para salvar a resposta específica no histórico -->
                                <button id="saveGeminiResponseBtn" class="contrast btn-sm">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-check" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 5px;"><path fill-rule="evenodd" d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5V2zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1H4z"/></svg>
                                     Salvar Resposta
                                </button>
                            </div>
                        `;

                        // Adiciona o event listener para o botão "Salvar Resposta" DENTRO do modal body
                        const saveGeminiResponseBtn = document.getElementById('saveGeminiResponseBtn');
                        if (saveGeminiResponseBtn) {
                            saveGeminiResponseBtn.addEventListener('click', function() {
                                error_log_js("Botão 'Salvar Resposta' clicado.");
                                // Desabilita o botão para evitar cliques duplicados
                                this.disabled = true;
                                this.setAttribute('aria-busy', 'true');
                                this.textContent = 'Salvando...';


                                // Prepara os dados para salvar (agora incluindo generated_text e model_used)
                                const saveData = new FormData();
                                saveData.append('action', 'save_generated_response'); // Nova ação para salvar resposta + prompt + inputs
                                saveData.append('prompt_text_sent', data.prompt_text_sent); // O prompt que foi enviado
                                saveData.append('generated_text', data.generated_text); // A resposta da IA
                                saveData.append('model_used', data.model_used); // O modelo usado
                                saveData.append('input_parameters_json', JSON.stringify(data.input_parameters || {})); // Inputs originais como JSON string
                                 // Opcional: Obter um título para o histórico
                                 const saveTitle = document.querySelector('input[name="prompt_title"]').value || 'Prompt e Resposta Salvos';
                                 saveData.append('prompt_title', saveTitle);


                                // Chama o endpoint API novamente, mas com a ação de salvar
                                fetch(BASE_URL_JS + 'public/api.php', {
                                    method: 'POST',
                                    body: saveData
                                })
                                .then(response => {
                                     // Verifica se a resposta foi bem sucedida no nível HTTP (status 2xx)
                                    if (!response.ok) {
                                         return response.text().then(text => {
                                            throw new Error(`Erro HTTP ao salvar: ${response.status} ${response.statusText}. Resposta: ${text}`);
                                         });
                                    }
                                    return response.json();
                                })
                                .then(saveResponse => {
                                    if(saveResponse.success) {
                                        alert('Prompt e Resposta salvos com sucesso!');
                                        saveGeminiResponseBtn.textContent = 'Salvo!';
                                        saveGeminiResponseBtn.classList.remove('contrast');
                                        saveGeminiResponseBtn.classList.add('secondary'); // Muda a cor
                                    } else {
                                        alert('Erro ao salvar: ' + saveResponse.message);
                                        saveGeminiResponseBtn.textContent = 'Erro ao Salvar';
                                    }
                                })
                                .catch(error => {
                                    error_log_js("Erro ao salvar via AJAX: " + error.message);
                                    alert('Erro ao salvar: ' + error.message);
                                    saveGeminiResponseBtn.textContent = 'Erro ao Salvar';
                                })
                                .finally(() => {
                                    saveGeminiResponseBtn.disabled = false;
                                    saveGeminiResponseBtn.removeAttribute('aria-busy');
                                });
                            }); // Fim do listener do botão Salvar Resposta
                        }


                    } else {
                        // Se o backend retornou erro (data.success === false)
                        let errorMessage = data.message || 'Ocorreu um erro desconhecido no backend.';
                        let errorDetails = data.details || ''; // Se houver detalhes adicionais do erro
                        modalBody.innerHTML = `<p class="text-danger">Erro: ${e(errorMessage)}</p>`;

                         if(errorDetails) {
                             modalBody.innerHTML += `<small>Detalhes: ${e(errorDetails)}</small>`;
                         }

                         // Sugere ação baseada no tipo de erro
                        if (data.error_type === 'api_key_missing' || data.error_type === 'api_key_invalid' || data.error_type === 'quota_exceeded') {
                            modalBody.innerHTML += `<p>Por favor, verifique sua chave API Gemini e cota no seu <a href="${BASE_URL_JS}public/profile.php">perfil</a> ou na plataforma Google AI Studio.</p>`;
                        } else if (data.error_type === 'auth_required') {
                            modalBody.innerHTML = `<p class="text-danger">Sua sessão expirou ou você não está logado. Por favor, <a href="${BASE_URL_JS}public/login.php">faça login</a>.</p>`;
                        } else if (data.error_type === 'method_not_allowed' || data.error_type === 'invalid_action' || data.error_type === 'empty_prompt') {
                             // Erros de requisição do cliente
                            modalBody.innerHTML += `<p>Verifique os dados enviados ou contate o suporte se persistir.</p>`;
                         }
                         // Outros erros de servidor/API...
                         else if (data.error_type === 'gemini_api_error') {
                             // Erro específico da API Gemini não tratado acima
                             modalBody.innerHTML += `<p>A API retornou um erro. O problema pode estar no prompt ou na configuração da API.</p>`;
                         }
                    }
                })
                .catch(error => {
                    // Captura erros na comunicação de rede ou erros lançados nos `.then`
                    error_log_js("ERRO geral na chamada AJAX ou processamento da resposta: " + error.message);
                    console.error(error); // Loga o erro completo no console do navegador

                    modalTitle.textContent = 'Erro'; // Atualiza o título para indicar erro
                    // Limpa e exibe mensagem de erro genérica
                    modalBody.innerHTML = `<p class="text-danger">Ocorreu um erro na comunicação com o servidor ou no processamento da resposta.</p><p>Detalhes: ${e(error.message)}</p>`;

                    // Tenta analisar o erro HTTP para mensagens mais úteis
                    if (error.message.includes("Erro HTTP: 401")) {
                        modalBody.innerHTML += `<p>Sua sessão expirou ou você não está logado. Por favor, <a href="${BASE_URL_JS}public/login.php">faça login</a>.</p>`;
                    } else if (error.message.includes("Erro HTTP: 403")) {
                        modalBody.innerHTML += `<p>Acesso negado ao endpoint da API. Verifique as permissões ou se você está logado corretamente.</p>`;
                    } else if (error.message.includes("Erro HTTP: 404")) {
                         modalBody.innerHTML += `<p>O endpoint da API não foi encontrado. Verifique a URL (${BASE_URL_JS}public/api.php).</p>`;
                    } else if (error.message.includes("Erro HTTP: 500")) {
                         modalBody.innerHTML += `<p>Ocorreu um erro interno no servidor durante o processamento. Verifique os logs do servidor.</p>`;
                    }
                })
                .finally(() => {
                    // Esta parte sempre executa, após sucesso ou falha na requisição
                    sendToGeminiBtn.disabled = false; // Reabilita o botão
                    sendToGeminiBtn.removeAttribute('aria-busy'); // Remove indicador de busy
                    // O spinner dentro do modalBody já foi substituído ou está prestes a ser.
                    error_log_js("Requisição AJAX finalizada (finally block).");
                });
            }); // Fim do event listener do botão
        } else {
            error_log_js("NÃO foi possível adicionar event listener para 'sendToGeminiBtn'. Elementos necessários faltando ou modal não instanciado.");
            if (!sendToGeminiBtn) error_log_js("Causa: Botão 'sendToGeminiBtn' não encontrado.");
            if (!geminiModalElement) error_log_js("Causa: Elemento 'geminiResponseModal' não encontrado.");
             if (!modalBody) error_log_js("Causa: Elemento 'modalBody' não encontrado.");
            if (!geminiModal) error_log_js("Causa: Instância do Modal Bootstrap não criada.");
        }

        // Lógica para mostrar a seção de prompt gerado se houver texto ao carregar a página
        // (isso acontecerá se o usuário vier de generate_prompt_action.php via POST+redirect)
        if (generatedPromptTextDisplay && generatedPromptTextDisplay.innerText.trim() !== '') {
             if (generatedPromptSection) {
                generatedPromptSection.style.display = 'block';
                error_log_js("Exibindo seção de prompt gerado.");
             }
             // Se houver dados de resposta Gemini salvos na sessão (após generate_prompt_action chamar a API E salvar),
             // podemos pré-popular ou mostrar o modal com a última resposta.
             // No entanto, a lógica de chamar a API AGORA é via AJAX. A sessão
             // $_SESSION['last_gemini_api_response_for_save'] só seria usada se a página
             // generator.php fosse o destino DIRETO de generate_prompt_action.php APÓS a API Call.
             // Com o fluxo AJAX, a resposta da API é tratada no .then() do fetch.
             // A sessão last_gemini_api_response_for_save é limpa no topo em GET requests.
             // Em POST requests (após a submissão do FORM para gerar prompt text), ela PODE existir
             // se generate_prompt_action.php foi modificado para CHAMAR A API e salvar na sessão antes do redirect.
             // Vamos ASSUMIR que generate_prompt_action.php apenas gera o texto do prompt
             // e a chamada da API é SOMENTE via o botão AJAX. Portanto, não precisamos pré-popular o modal aqui.
        }


    }); // Fim DOMContentLoaded

    error_log_js("Fim da definição do script de generator.php.");

</script>

<?php
// NADA DEVE SER ESCRITO OU ECOADO APÓS O FINAL DESTE ARQUIVO.
// O footer.php já fechou as tags </body> e </html>.
?>