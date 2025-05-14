<?php
// public/ebook_planner.php
$page_title = "Planejador de eBook Inteligente"; // Título para o <title> da página

require_once __DIR__ . '/../src/core/auth.php';
require_login(); // Garante que o usuário esteja logado para acessar esta página

// Nosso header.php principal:
// 1. Inicia a sessão (session_start())
// 2. Inclui constants.php (define BASE_URL, SITE_NAME, etc.)
// 3. Define a função e() (htmlspecialchars)
// 4. Abre <!DOCTYPE html>, <html>, <head>
// 5. Inclui no <head>:
//    - Bootstrap CSS
//    - Bootstrap Icons
//    - Google Fonts (Inter)
//    - Seu style.css global (public/css/style.css)
//    - Alguns estilos inline básicos para o layout flex do body
// 6. Abre <body> com classes para sticky footer
// 7. Inclui o <header> de navegação do AI PromptCraft Studio
// 8. Abre <main class="flex-shrink-0">
// 9. Exibe mensagens globais de sessão (success/error)
require_once __DIR__ . '/../src/templates/header.php'; 
?>

<!-- CSS específico para o eBook Planner -->
<!-- Este link é adicionado APÓS o header.php ter sido processado,
     então ele será incluído DENTRO do <head> principal gerado pelo header.php -->
<link rel="stylesheet" href="<?php echo e(BASE_URL); ?>ebook_planner_assets/style.css">

<!-- O conteúdo específico desta página (eBook Planner) começa aqui, -->
<!-- dentro do <main class="flex-shrink-0"> aberto pelo header.php -->
<div class="container-fluid px-0 px-md-4 py-4" id="ebookPlannerAppContainer"> 
    
    <!-- Sub-Header do Planner (Dentro do container-fluid da página) -->
    <div class="container"> 
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-3 bg-body-tertiary border rounded shadow-sm planner-sub-header">
            <div>
                <h1 class="h4 mb-1 fw-bold"><i class="bi bi-journal-bookmark-fill me-2 text-primary"></i><?php echo e($page_title); /* Usa o $page_title definido no topo */ ?></h1>
                <small class="text-muted d-block">Seu assistente passo a passo para criar eBooks incríveis.</small>
            </div>
            <div class="header-actions d-flex align-items-center mt-2 mt-md-0">
                <div class="dropdown me-2">
                    <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" id="templateDropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false" title="Carregar um modelo de plano">
                        <i class="bi bi-layout-text-sidebar-reverse me-1"></i> Modelos
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="templateDropdownMenu" aria-labelledby="templateDropdownMenuButton">
                        {/* <!-- Conteúdo preenchido por JavaScript do planner --> */}
                    </ul>
                </div>
                <button type="button" class="btn btn-sm btn-outline-info me-2" data-bs-toggle="modal" data-bs-target="#aiAssistanceModal" title="Obter ajuda da IA para preencher o plano">
                     <i class="bi bi-stars me-1"></i> Assistência IA
                </button>
                <button type="button" id="saveProgressBtn" class="btn btn-sm btn-outline-secondary me-2" title="Salvar seu progresso atual no navegador">
                    <i class="bi bi-save me-1"></i> Salvar
                </button>
                <button type="button" id="resetPlanBtn" class="btn btn-sm btn-danger" title="Limpar todos os campos do plano">
                    <i class="bi bi-trash me-1"></i> Limpar
                </button>
            </div>
        </div>

        <div id="apiKeyStatusContainer" class="alert alert-warning py-2 small text-center" role="alert">
            Carregando status da API Key...
        </div>
    </div> 


    <!-- Wizard Principal (Dentro de um container para centralização e padding) -->
    <div class="container" id="wizardOuterContainer">
        <div id="wizardContainer"> 
          <div id="progressIndicator" class="mt-3 mb-4 text-center fw-semibold text-muted">
              Passo X de Y {/* Preenchido pelo JS do planner */}
          </div>
          
          <form id="wizardForm" class="wizard-card bg-white p-4 p-md-5 rounded shadow-sm" novalidate>
                <div id="stepsContainer" aria-live="polite">
                    
                    <div class="wizard-step active" data-step-index="0"> 
                        <h3 class="step-title">1. Ideia Central & Propósito (Exemplo)</h3>
                        <div class="mb-4">
                            <label for="step0_q0_placeholder" class="form-label">Tema Principal do eBook: <span class="required-field-marker">*</span></label>
                            <input type="text" id="step0_q0_placeholder" name="step0_q0" class="form-control" placeholder="Ex: Marketing de Conteúdo" required>
                            <div class="form-text">Defina o assunto central de forma clara e concisa.</div>
                        </div>
                        
                        <p class="text-center text-muted p-3">Carregando conteúdo completo do passo...</p>
                    </div>
                </div>
            
                <div id="validationErrorMessage" class="alert alert-danger mt-3" style="display: none;" role="alert">
                    
                </div>
            
                <div class="d-flex justify-content-between navigation-buttons mt-4">
                    <button type="button" id="prevBtn" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-left-circle me-2"></i>Voltar</button>
                    <button type="button" id="nextBtn" class="btn btn-primary btn-lg">Próximo<i class="bi bi-arrow-right-circle ms-2"></i></button>
                </div>
          </form>
        </div>

        
        <div id="completionSection" class="wizard-card bg-white p-4 p-md-5 rounded shadow-sm" style="display: none;">
            {/* 
            COPIE AQUI O CONTEÚDO HTML INTERNO COMPLETO 
            DO <div id="completionSection"...> DO SEU ARQUIVO ebookplannerv11.html 
            */
            }
            <h2 class="text-center mb-3"><i class="bi bi-check2-circle me-2 text-success"></i>Planejamento Concluído!</h2>
            <p class="text-center text-muted mb-4">Seu plano detalhado para o eBook está pronto...</p>
            {/* ... Mais HTML da seção de conclusão ... */}
        </div>
    </div> 


    <!-- Modal de Assistência IA -->
    <div class="modal fade" id="aiAssistanceModal" tabindex="-1" aria-labelledby="aiAssistanceModalLabel" aria-hidden="true">
        {/* 
        COPIE AQUI O CONTEÚDO HTML INTERNO COMPLETO 
        DO <div class="modal fade" id="aiAssistanceModal"...> DO SEU ARQUIVO ebookplannerv11.html
        (Começando com <div class="modal-dialog modal-xl ...">)
        */
        }
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="aiAssistanceModalLabel"><i class="bi bi-stars me-2"></i>Assistência Inteligente IA</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                 {/* ... Conteúdo do modal body do original ... */}
                 <p>Conteúdo do modal de assistência IA...</p>
            </div>
            <div class="modal-footer">
                {/* ... Conteúdo do modal footer do original ... */}
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
          </div>
        </div>
    </div>

    <!-- General Loading Overlay -->
    <div id="loadingOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.85); z-index: 1060; display: none; justify-content: center; align-items: center;">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Carregando...</span>
      </div>
    </div>

    <!-- O Botão Flutuante de IA Inline geralmente é adicionado ao <body> pelo JavaScript do planner. -->
    <!-- Se ele espera um contêiner específico, certifique-se de que esse contêiner exista ou ajuste o JS. -->

</div> {/* Fim do #ebookPlannerAppContainer (container-fluid principal da página) */}


<?php
// Footer do AI PromptCraft Studio. 
// Ele já inclui Bootstrap JS Bundle.
require_once __DIR__ . '/../src/templates/footer.php';
?>

<!-- Dependências JS Adicionais do eBook Planner -->
<!-- Carregadas APÓS o Bootstrap JS (do footer) e ANTES do script.js do planner -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://unpkg.com/turndown/dist/turndown.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<!-- O script principal do eBook Planner -->
<script type="module" src="<?php echo e(BASE_URL); ?>ebook_planner_assets/script.js"></script>
<?php
// NENHUM HTML ou TEXTO após esta linha final do PHP.
// O footer.php já fechou as tags </body> e </html>.
?>