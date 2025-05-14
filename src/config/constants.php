<?php
// Definições básicas do site
define('SITE_NAME', 'AI PromptCraft Studio');

// Determina o BASE_URL dinamicamente ou define manualmente
// Para XAMPP, se a pasta do projeto é 'ai_promptcraft_studio' dentro de 'htdocs'
// e você acessa via http://localhost/ai_promptcraft_studio/public/
// então BASE_URL deve ser http://localhost/ai_promptcraft_studio/public/
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Assume que o DocumentRoot é 'public' dentro da pasta do projeto
$script_name_parts = explode('/', dirname($_SERVER['SCRIPT_NAME'])); // dirname pega o diretório do script em execução
// Se o script está em /ai_promptcraft_studio/public/index.php, dirname é /ai_promptcraft_studio/public
// Se o script está em /ai_promptcraft_studio/src/config/constants.php, este método não funciona diretamente para o BASE_URL do frontend
// Vamos simplificar para XAMPP, assumindo que a pasta do projeto é acessada diretamente e 'public' é o DocumentRoot simulado

// Caminho base da URL para os assets e links do frontend
// Se você acessa http://localhost/ai_promptcraft_studio/public/
define('BASE_URL', $protocol . $host . '/ai_promptcraft_studio/public/');

// Caminho base no sistema de arquivos para includes PHP
define('BASE_PATH', dirname(__DIR__, 2) . '/'); // Sobe dois níveis de src/config para a raiz do projeto

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'ai_promptcraft_studio');
define('DB_USER', 'root');
define('DB_PASS', '');

// Chave para criptografia da API Key (MUDE ISSO!)
define('ENCRYPTION_KEY', '55555890B01E1823E05D750E0E172D5DCD73728D11FF901E667CF36BB6BA7E08');
define('ENCRYPTION_IV_KEY', 'C0DBA5DB814B807751B719BA3FE90FA6');

// Habilitar exibição de erros para desenvolvimento (desabilitar em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>