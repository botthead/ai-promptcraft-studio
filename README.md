 
# AI PromptCraft Studio

AI PromptCraft Studio é uma aplicação web desenvolvida para ajudar usuários a criar, gerenciar e otimizar prompts para diversas Inteligências Artificiais generativas, como Google Gemini, ChatGPT, DALL-E, entre outras.

## Funcionalidades do MVP (Produto Mínimo Viável)

*   **Gerenciamento de Usuários:**
    *   Registro de novos usuários.
    *   Login e Logout seguro.
    *   Páginas protegidas que exigem autenticação.
    *   Gerenciamento de perfil de usuário, incluindo alteração de senha.
    *   Armazenamento seguro (criptografado) para Chave API do Google Gemini.
*   **Gerador de Prompts:**
    *   Interface intuitiva para inserir parâmetros detalhados para a criação de prompts (objetivo, público-alvo, tom, palavras-chave, formato de saída, contexto).
    *   Geração de um texto de prompt otimizado com base nos inputs.
    *   Opção para copiar o prompt gerado.
*   **Histórico de Prompts:**
    *   Salvamento dos prompts gerados no banco de dados, associados ao usuário.
    *   Listagem dos prompts salvos com informações relevantes (título, preview, data).
    *   Opção para excluir prompts do histórico.
    *   Opção para reutilizar um prompt salvo, pré-preenchendo o formulário do gerador.

## Tecnologias Utilizadas

*   **Backend:** PHP
*   **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
*   **Banco de Dados:** MySQL / MariaDB
*   **Servidor:** Apache (geralmente via XAMPP)
*   **Estilização Base:** Pico.css (CDN)

## Configuração do Ambiente de Desenvolvimento (XAMPP no Windows)

1.  **Pré-requisitos:**
    *   XAMPP instalado (com Apache e MySQL). Baixe em [apachefriends.org](https://www.apachefriends.org/index.html).
    *   Um editor de código (ex: VS Code, Sublime Text, PhpStorm).
    *   Git (opcional, mas recomendado para controle de versão).

2.  **Instalação:**
    *   Clone ou baixe este repositório para o diretório `htdocs` do seu XAMPP (normalmente `C:\xampp\htdocs\`). Nomeie a pasta do projeto como `ai_promptcraft_studio`.
        ```
        C:\xampp\htdocs\ai_promptcraft_studio
        ```
    *   Inicie os módulos Apache e MySQL no Painel de Controle do XAMPP.

3.  **Configuração do Banco de Dados:**
    *   Abra o phpMyAdmin (geralmente `http://localhost/phpmyadmin`).
    *   Crie um novo banco de dados chamado `ai_promptcraft_studio` (use agrupamento `utf8mb4_unicode_ci`).
    *   Selecione o banco de dados recém-criado.
    *   Vá para a aba "SQL" e execute o conteúdo do arquivo `database/schema.sql` localizado na raiz do projeto. Isso criará as tabelas necessárias.

4.  **Configuração das Constantes:**
    *   Abra o arquivo `src/config/constants.php`.
    *   Verifique se `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` estão corretos para sua configuração do XAMPP (os padrões geralmente funcionam).
    *   **IMPORTANTE:** Defina valores únicos e seguros para `ENCRYPTION_KEY` (64 caracteres hexadecimais) e `ENCRYPTION_IV_KEY` (32 caracteres hexadecimais). Você pode gerar esses valores usando PHP no terminal:
        ```bash
        php -r "echo 'ENCRYPTION_KEY: ' . bin2hex(random_bytes(32)) . PHP_EOL;"
        php -r "echo 'ENCRYPTION_IV_KEY: ' . bin2hex(random_bytes(16)) . PHP_EOL;"
        ```
    *   Verifique se `BASE_URL` está correto. Para uma instalação padrão do XAMPP, deve ser `http://localhost/ai_promptcraft_studio/public/`.

5.  **Acessando a Aplicação:**
    *   Abra seu navegador e acesse `http://localhost/ai_promptcraft_studio/public/`.

## Próximas Fases (Pós-MVP)

*   Integração com a API do Google Gemini.
*   Sistema de templates de prompts.
*   Melhorias na interface do usuário (UX) e design (UI).
*   Funcionalidades AJAX para interações mais fluidas.
*   E muito mais!

---

Este `README.md` é um bom ponto de partida. Você pode adicionar mais detalhes conforme o projeto evolui.