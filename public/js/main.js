// public/js/main.js
// Certifique-se de que este script é incluído DEPOIS do bloco <script> que define BASE_URL_JS em generator.php

// Sua função de log JS existente...
function error_log_js(message) { console.log("[GENERATOR.JS LOG] " + message); }

$(document).ready(function () { // Se estiver usando jQuery
    // ... seu código existente ...

     // Encontre o formulário e botão enviar para Gemini
    const promptForm = document.getElementById('prompt-form'); // Use ID se tiver
    const sendToGeminiBtn = document.getElementById('send-to-gemini-btn'); // Use ID do botão

    // ... outros elementos como modalBody, spinner, geminiModalElement ...

    if (sendToGeminiBtn) { // Certifique-se que o botão existe
         error_log_js("Adicionando event listener para 'Enviar para Gemini'.");
         sendToGeminiBtn.addEventListener('click', function(e) { // Usando addEventListener, melhor que onclick inline
            e.preventDefault();

            error_log_js("'Enviar para Gemini' CLICADO!");

            // Desabilita botão e mostra spinner
            sendToGeminiBtn.disabled = true;
            spinner.classList.remove('d-none');
            modalBody.innerHTML = '<p>Aguarde...</p>'; // Limpa conteúdo anterior

            // Coleta dados do formulário
            // Adapte isso para os nomes reais dos campos do seu formulário generator.php
            const promptText = promptForm.querySelector('textarea[name="prompt_text"]').value;
            const theme = promptForm.querySelector('input[name="theme"]').value;
            const style = promptForm.querySelector('input[name="style"]').value;
            const tone = promptForm.querySelector('select[name="tone"]').value;
            // ... colete outros campos conforme necessário ...

            // Monta o conteúdo completo do prompt para a IA
            // Adapte isso para a estrutura final que você envia para a API
            const fullPromptContent = `Para a IA: Gemini\n\nObjetivo Principal: ${promptText}\n\nTema: ${theme}\nEstilo: ${style}\nTono: ${tone}`; // Exemplo


            error_log_js("Prompt Text a ser enviado para backend: " + fullPromptContent.substring(0, 200) + "..."); // Log parcial

            // --- CORRIGINDO A URL DO AJAX ---
            // Use a variável JavaScript definida em generator.php
            // Aponte para o novo endpoint API
            const apiEndpointUrl = BASE_URL_JS + 'public/api.php'; // URL CORRETA

            // Crie FormData para enviar os dados
            const formData = new FormData();
            formData.append('action', 'call_gemini'); // Indica a ação para api.php
            formData.append('prompt_text', fullPromptContent); // Envia o prompt completo
            // Adicione outros dados do formulário se precisar no backend
            formData.append('theme', theme);
            formData.append('style', style);
            formData.append('tone', tone);
            // ... adicione mais campos aqui ...

            error_log_js(`Enviando requisição AJAX para ${apiEndpointUrl} com action=call_gemini`);

            // Usa a Fetch API (moderno e recomendado)
            fetch(apiEndpointUrl, {
                method: 'POST',
                body: formData // Envia os dados do formulário
                // Fetch API define o Content-Type para FormData automaticamente
            })
            .then(response => {
                error_log_js(`Fetch response status: ${response.status}`);
                // Verifica se a resposta foi bem sucedida no nível HTTP (status 2xx)
                if (!response.ok) {
                    // Se não foi OK, tenta ler a resposta como texto para logar
                     return response.text().then(text => {
                        // Cria um erro com o status e o texto da resposta
                        throw new Error(`Erro HTTP: ${response.status} ${response.statusText}. Resposta: ${text}`);
                     });
                }
                // Se foi OK (2xx), assume que a resposta é JSON e a parseia
                return response.json();
            })
            .then(data => {
                // Processa a resposta JSON recebida do PHP
                error_log_js("Resposta JSON do Backend:");
                console.log(data); // Loga a estrutura completa da resposta

                if (data.success) {
                    // Se o backend retornou sucesso:
                    modalBody.innerHTML = `
                        <p><strong>Prompt Enviado:</strong></p>
                        <pre>${e(data.prompt_text_sent)}</pre>
                        <p><strong>Resposta da ${e(data.model_used || 'IA')}:</strong></p>
                        <div id="gemini-response-content"><p>${nl2br(e(data.generated_text))}</p></div>
                        <button class="btn btn-secondary btn-sm mt-2" onclick="copyModalContent()">Copiar Resposta</button>
                        <button id="saveGeminiResponseBtn" class="btn btn-primary btn-sm mt-2">Salvar Resposta</button>
                    `;

                    // TODO: Lógica para o botão "Salvar Resposta"
                    // Você precisará adicionar um event listener para '#saveGeminiResponseBtn'
                    // e enviar os dados relevantes (prompt_text_sent, generated_text, model_used, input_parameters)
                    // para outro endpoint API (ex: apiEndpointUrl com action='save_prompt_api')
                    const saveGeminiResponseBtn = document.getElementById('saveGeminiResponseBtn');
                    if (saveGeminiResponseBtn) {
                        // Armazena os dados a serem salvos como data attributes no botão
                        saveGeminiResponseBtn.dataset.promptTextSent = data.prompt_text_sent;
                        saveGeminiResponseBtn.dataset.generatedText = data.generated_text;
                        saveGeminiResponseBtn.dataset.modelUsed = data.model_used;
                        saveGeminiResponseBtn.dataset.inputParameters = JSON.stringify(data.input_parameters || {}); // Salva input_parameters como JSON string

                        saveGeminiResponseBtn.addEventListener('click', function() {
                            error_log_js("Botão 'Salvar Resposta' clicado.");
                            // TODO: Implementar chamada AJAX para salvar (action='save_prompt_api')
                            alert("Funcionalidade de salvar ainda não implementada. (TODO)"); // Placeholder
                            // Exemplo de como enviar os dados para salvar (você precisa criar a action 'save_prompt_api' em api.php e o script correspondente em src/Actions/)
                            /*
                            const saveData = new FormData();
                            saveData.append('action', 'save_prompt_api');
                            saveData.append('title', promptForm.querySelector('input[name="title"]').value || 'Prompt Salvo'); // Use um título do formulário ou padrão
                            saveData.append('prompt_text', this.dataset.promptTextSent);
                            saveData.append('generated_text', this.dataset.generatedText);
                            saveData.append('model_used', this.dataset.modelUsed);
                            saveData.append('input_parameters', this.dataset.inputParameters); // Envia como JSON string

                             fetch(apiEndpointUrl, {
                                 method: 'POST',
                                 body: saveData
                             })
                             .then(response => response.json())
                             .then(saveResponse => {
                                 if(saveResponse.success) {
                                     alert('Prompt e Resposta salvos com sucesso!');
                                      // Opcional: Desabilitar o botão de salvar ou mudar o texto
                                     saveGeminiResponseBtn.disabled = true;
                                     saveGeminiResponseBtn.textContent = 'Salvo!';
                                 } else {
                                     alert('Erro ao salvar: ' + saveResponse.message);
                                 }
                             })
                             .catch(error => {
                                 error_log_js("Erro ao salvar via AJAX: " + error.message);
                                 alert('Erro ao salvar: ' + error.message);
                             });
                             */
                        });
                    }


                } else {
                    // Se o backend retornou erro (data.success === false):
                    let errorMessage = data.message || 'Ocorreu um erro desconhecido no backend.';
                    modalBody.innerHTML = `<p class="text-danger">Erro: ${e(errorMessage)}</p>`;

                     // Se for um erro de chave API, sugere ir para o perfil
                    if (data.error_type === 'api_key_missing' || data.error_type === 'api_key_invalid') {
                        modalBody.innerHTML += `<p>Por favor, verifique sua chave API Gemini no seu <a href="${BASE_URL_JS}public/profile.php">perfil</a>.</p>`;
                    }
                     // Se for erro de autenticação (embora api.php já retorne 401):
                     if (data.error_type === 'auth_required') {
                        modalBody.innerHTML += `<p>Você precisa fazer login para usar esta funcionalidade.</p>`;
                     }
                }
            })
            .catch(error => {
                // Captura erros na comunicação de rede ou erros lançados nos `.then`
                error_log_js("ERRO na chamada AJAX ou processamento: " + error.message);
                console.error(error); // Loga o erro completo no console do navegador
                modalBody.innerHTML = `<p class="text-danger">Erro na comunicação ou processamento: ${e(error.message)}</p>`;
                 // Opcional: Se for um erro HTTP 401 ou 403, pode dar uma mensagem mais específica
                 if (error.message.includes("Erro HTTP: 401")) {
                     modalBody.innerHTML = `<p class="text-danger">Erro: Sua sessão expirou ou você não está logado. Por favor, <a href="${BASE_URL_JS}public/login.php">faça login</a>.</p>`;
                 } else if (error.message.includes("Erro HTTP: 403")) {
                     modalBody.innerHTML = `<p class="text-danger">Erro: Acesso negado ao endpoint API. Verifique as permissões ou se você está logado corretamente.</p>`;
                 }
            })
            .finally(() => {
                // Esta parte sempre executa, após sucesso ou falha
                spinner.classList.add('d-none'); // Esconde o spinner
                sendToGeminiBtn.disabled = false; // Reabilita o botão

                // Exibe o modal (sempre, para mostrar resultado ou erro)
                if (geminiModal) { // Verifica se o modal foi instanciado com sucesso antes
                    error_log_js("geminiModal.show() chamado no FINALLY.");
                    geminiModal.show();
                } else {
                     error_log_js("Não foi possível mostrar o modal no FINALLY: geminiModal não foi instanciado.");
                }
            });
        }); // Fim do event listener do botão
    } else {
         error_log_js("Botão 'Enviar para Gemini' não encontrado. Verifique o ID.");
    }


    // TODO: Adicionar listener para o botão de Salvar Resposta (ver comentários acima)
    // TODO: Adicionar função copyModalContent() (já fizemos antes, certifique-se que está em main.js ou acessível)
     window.copyModalContent = function() {
        const responseDiv = document.getElementById('gemini-response-content');
        if (responseDiv) {
            const textToCopy = responseDiv.innerText; // Use innerText para pegar o texto formatado
            navigator.clipboard.writeText(textToCopy).then(function() {
                alert('Resposta copiada para a área de transferência!');
            }, function(err) {
                console.error('Erro ao copiar texto: ', err);
                alert('Erro ao copiar texto. Por favor, copie manualmente.');
            });
        } else {
             error_log_js("Elemento 'gemini-response-content' não encontrado para copiar.");
        }
    };

    // TODO: Certificar-se de que as funções utilitárias (e, nl2br) estão disponíveis no escopo global ou incluídas.
    // Como você está usando e() e nl2br() diretamente no innerHTML (string), você precisa que
    // essas funções JS existam globalmente (definidas em um <script> antes de main.js)
    // ou que você as defina aqui. É melhor defini-las em um script incluído antes de main.js
    // ou reescrever o HTML para usar document.createElement e set textContent/innerText.
    // Para simplicidade AGORA, vamos definir versões JS simples aqui (não ideais, apenas para teste):
     window.e = function(text) { // Simula htmlspecialchars
        if (text === null || text === undefined) return '';
        return String(text).replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"').replace(/'/g, ''');
     };
     window.nl2br = function(text) { // Simula nl2br
         if (text === null || text === undefined) return '';
         return String(text).replace(/\n/g, '<br>');
     };
     error_log_js("Funções utilitárias JS (e, nl2br) definidas globalmente (temporário).");


}); // Fim do $(document).ready()