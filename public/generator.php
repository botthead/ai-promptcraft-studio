<?php
// public/generator.php
$page_title = "Gerador de Prompts";

require_once __DIR__ . '/../src/core/auth.php';
require_login(); 

require_once __DIR__ . '/../src/config/database.php'; 
require_once __DIR__ . '/../src/templates/header.php'; 

// ... (lógica PHP inicial para $form_data, $has_gemini_api_key, reutilização, etc., SEM MUDANÇAS) ...
// ... (Captura e limpeza de $generated_prompt_text_display, $last_input_parameters_for_save) ...
// ... (Limpeza de $_SESSION['gemini_api_response'] e $_SESSION['gemini_api_error']) ...
// COPIE A LÓGICA PHP DO TOPO DA VERSÃO ANTERIOR COMPLETA DE GENERATOR.PHP AQUI

?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <header class="mb-4">
                <h1 class="display-5 fw-bold">
                    <i class="bi bi-lightbulb-fill me-2"></i><?php echo e($page_title); ?>
                </h1>
                <p class="lead text-muted">Preencha os campos abaixo para criar seu prompt otimizado e interagir com a IA.</p>
            </header>

            <?php
            // Exibição de mensagens de erro/sucesso do gerador
            if (isset($_SESSION['error_message_generator'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . e($_SESSION['error_message_generator']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['error_message_generator']);
            }
            if (isset($_SESSION['success_message_generator'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . e($_SESSION['success_message_generator']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['success_message_generator']);
            }
            ?>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form id="prompt-generator-form" action="<?php echo e(BASE_URL); ?>../src/actions/generate_prompt_action.php" method="POST" novalidate>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="prompt_title" class="form-label">Título do Prompt (Opcional):</label>
                                <input type="text" class="form-control" id="prompt_title" name="prompt_title" 
                                       value="<?php echo e($form_data['prompt_title'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="target_ia" class="form-label">IA Alvo:</label>
                                <input type="text" class="form-control" id="target_ia" name="target_ia" 
                                       value="<?php echo e($form_data['target_ia'] ?? 'Gemini'); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="objective" class="form-label">Objetivo Principal do Prompt: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($_SESSION['form_errors_generator']['objective']) ? 'is-invalid' : ''; ?>" 
                                   id="objective" name="objective" required
                                   placeholder="Ex: Criar um roteiro para vídeo, gerar um email de marketing..."
                                   value="<?php echo e($form_data['objective'] ?? ''); ?>">
                            <?php if (isset($_SESSION['form_errors_generator']['objective'])): ?>
                                <div class="error-feedback mt-1"><?php echo e($_SESSION['form_errors_generator']['objective']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="audience" class="form-label">Público-Alvo:</label>
                                <input type="text" class="form-control" id="audience" name="audience"
                                       placeholder="Ex: Iniciantes, especialistas..."
                                       value="<?php echo e($form_data['audience'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="tone_style" class="form-label">Tom/Estilo:</label>
                                <select class="form-select" id="tone_style" name="tone_style">
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
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="keywords" class="form-label">Palavras-chave/Conceitos Essenciais (separados por vírgula):</label>
                            <input type="text" class="form-control" id="keywords" name="keywords"
                                   placeholder="Ex: sustentabilidade, inovação, marketing digital"
                                   value="<?php echo e($form_data['keywords'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="output_format" class="form-label">Formato de Saída Desejado:</label>
                            <select class="form-select" id="output_format" name="output_format">
                                <option value="" <?php echo empty($form_data['output_format']) ? 'selected' : ''; ?>>-- Selecione --</option>
                                <option value="Parágrafos" <?php echo (($form_data['output_format'] ?? '') === 'Parágrafos') ? 'selected' : ''; ?>>Parágrafos</option>
                                <option value="Lista com marcadores" <?php echo (($form_data['output_format'] ?? '') === 'Lista com marcadores') ? 'selected' : ''; ?>>Lista com marcadores</option>
                                <option value="Lista numerada" <?php echo (($form_data['output_format'] ?? '') === 'Lista numerada') ? 'selected' : ''; ?>>Lista numerada</option>
                                <option value="Tabela" <?php echo (($form_data['output_format'] ?? '') === 'Tabela') ? 'selected' : ''; ?>>Tabela</option>
                                <option value="Bloco de código" <?php echo (($form_data['output_format'] ?? '') === 'Bloco de código') ? 'selected' : ''; ?>>Bloco de código</option>
                                <option value="Diálogo" <?php echo (($form_data['output_format'] ?? '') === 'Diálogo') ? 'selected' : ''; ?>>Diálogo</option>
                                <option value="Email" <?php echo (($form_data['output_format'] ?? '') === 'Email') ? 'selected' : ''; ?>>Email</option>
                                <option value="Roteiro" <?php echo (($form_data['output_format'] ?? '') === 'Roteiro') ? 'selected' : ''; ?>>Roteiro</option>
                            </select>
                        </div>

                        <div class="mb-4"> 
                            <label for="context_restrictions" class="form-label">Contexto Adicional, Exemplos ou Restrições:</label>
                            <textarea class="form-control" id="context_restrictions" name="context_restrictions" rows="5"
                                      placeholder="Ex: Evitar jargões técnicos. Incluir um call-to-action no final..."><?php echo e($form_data['context_restrictions'] ?? ''); ?></textarea>
                        </div>
                            
                        <div class="d-grid">
                            <button type="submit" name="action" value="generate" class="btn btn-primary btn-lg">
                                <i class="bi bi-pencil-square me-2"></i>Gerar Novo Prompt
                            </button>
                        </div>
                    </form>
                </div>
            </div>


            <section id="generated-prompt-interaction-section" class="mt-5 <?php echo empty($generated_prompt_text_display) ? 'd-none' : ''; ?>"> 
                <h3 class="mb-3 fw-semibold">Prompt Gerado:</h3>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <pre class="mb-0"><code id="generated-prompt-text-display" style="white-space: pre-wrap; word-wrap: break-word;"><?php echo e($generated_prompt_text_display); ?></code></pre>
                    </div>
                </div>
                
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <button onclick="copyToClipboard('generated-prompt-text-display')" class="btn btn-outline-secondary">
                        <i class="bi bi-clipboard me-2"></i>Copiar Prompt
                    </button>
                    <?php if ($has_gemini_api_key): ?>
                        <button id="send-to-gemini-btn" class="btn btn-success">
                            <i class="bi bi-send-fill me-2"></i>Enviar para Gemini
                        </button>
                    <?php else: ?>
                        <button class="btn btn-success" disabled title="Configure sua API Key do Gemini no Perfil para usar esta funcionalidade.">
                            <i class="bi bi-send-fill me-2"></i>Enviar para Gemini
                        </button>
                        <small class="ms-2 align-self-center"><a href="<?php echo e(BASE_URL); ?>profile.php">Configure sua API Key</a></small>
                    <?php endif; ?>
                </div>
                
                <div id="gemini-spinner" style="display: none; text-align: center; margin: 20px 0;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2 text-muted">Consultando Gemini, por favor aguarde...</p>
                </div>
                
                <?php if (!empty($generated_prompt_text_display) && $last_input_parameters_for_save !== null): ?>
                <div class="mt-4 pt-4 border-top">
                    <h4 class="mb-3 fw-semibold">Salvar este Prompt</h4>
                    <form id="savePromptForm" action="<?php echo e(BASE_URL); ?>../src/actions/save_prompt_action.php" method="POST">
                        <input type="hidden" name="prompt_title_to_save" value="<?php echo e($last_input_parameters_for_save['prompt_title'] ?? ''); ?>">
                        <input type="hidden" name="input_parameters_json" value="<?php echo e(json_encode($last_input_parameters_for_save)); ?>">
                        <input type="hidden" name="generated_prompt_text_to_save" value="<?php echo e($generated_prompt_text_display); ?>">
                        <input type="hidden" name="gemini_response_to_save" value="" id="gemini_response_for_save_action">

                        <div class="mb-3">
                            <label for="save_title_override" class="form-label">Título para o Histórico (opcional):</label>
                            <input type="text" class="form-control" name="save_title_override" id="save_title_override" value="<?php echo e($last_input_parameters_for_save['prompt_title'] ?? ''); ?>" placeholder="Ex: Prompt para email de boas-vindas">
                        </div>
                        
                        <button type="submit" name="action" value="save_generated" class="btn btn-info">
                            <i class="bi bi-archive-fill me-2"></i>Salvar no Histórico
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </section>

        </div>
    </div>
</div>


<!-- Modal da Resposta da API Gemini (HTML como antes) -->
<div class="modal fade" id="geminiResponseModal" tabindex="-1" aria-labelledby="geminiResponseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="geminiResponseModalLabel">Resposta da API Gemini</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="geminiResponseModalBody">
        <!-- Conteúdo será preenchido por JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" onclick="copyModalContent()">Copiar Resposta</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../src/templates/footer.php';
?>

<!-- JavaScript específico para esta página, colocado APÓS o footer (que inclui Bootstrap JS) -->
<script>
    function error_log_js(message) { console.log("[GENERATOR.JS LOG] " + message); }
    error_log_js("Script de generator.php (após footer) está sendo executado.");

    function copyModalContent() { /* ... (como na versão anterior completa) ... */ }
    function copyToClipboard(elementId) { /* ... (como na versão anterior completa) ... */ }

    const sendToGeminiBtn = document.getElementById('send-to-gemini-btn');
    const geminiModalElement = document.getElementById('geminiResponseModal');
    let geminiModal = null;
    if (geminiModalElement) {
        try {
            geminiModal = new bootstrap.Modal(geminiModalElement);
            error_log_js("Instância do Modal Bootstrap criada.");
        } catch (e) {
            error_log_js("ERRO ao instanciar Modal Bootstrap: " + e.message); console.error(e);
        }
    } else { error_log_js("Elemento do modal #geminiResponseModal NÃO encontrado."); }
    
    const modalBody = document.getElementById('geminiResponseModalBody');
    const spinner = document.getElementById('gemini-spinner');
    const geminiResponseForSaveActionField = document.getElementById('gemini_response_for_save_action');

    // Logs para verificar se os elementos foram encontrados
    error_log_js("sendToGeminiBtn: " + (sendToGeminiBtn ? "OK" : "FALHOU"));
    error_log_js("geminiModal: " + (geminiModal ? "OK" : "FALHOU"));
    error_log_js("modalBody: " + (modalBody ? "OK" : "FALHOU"));
    error_log_js("spinner: " + (spinner ? "OK" : "FALHOU"));
    error_log_js("geminiResponseForSaveActionField: " + (geminiResponseForSaveActionField ? "OK" : "FALHOU"));


    if (sendToGeminiBtn && geminiModal && modalBody && spinner) {
        error_log_js("Adicionando event listener ao sendToGeminiBtn.");
        sendToGeminiBtn.addEventListener('click', async function(event) {
            // ... (LÓGICA AJAX COMPLETA como na versão anterior, usando api_handler.php) ...
            // COPIE A LÓGICA DO addEventListener COMPLETA DA VERSÃO ANTERIOR AQUI
            // Certifique-se de que a URL do fetch é:
            // const response = await fetch('<?php echo e(BASE_URL); ?>api_handler.php', { ... });
        });
    } else {
        error_log_js("Não foi possível adicionar listener: um ou mais elementos DOM não encontrados ou modal não instanciado.");
    }
    error_log_js("Fim do script de generator.php.");
</script>

<?php
// Limpeza de sessão PHP
if (isset($_SESSION['form_data_generator'])) unset($_SESSION['form_data_generator']);
if (isset($_SESSION['form_errors_generator'])) unset($_SESSION['form_errors_generator']);
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['reuse_prompt_id'])) {
    if (isset($_SESSION['generated_prompt_text'])) unset($_SESSION['generated_prompt_text']);
    if (isset($_SESSION['last_input_parameters'])) unset($_SESSION['last_input_parameters']);
    if (isset($_SESSION['last_gemini_api_response_for_save'])) unset($_SESSION['last_gemini_api_response_for_save']);
}
error_log("Sessão no final de generator.php (após toda lógica): " . print_r($_SESSION, true));
// O footer.php já foi incluído ANTES do script.
?>