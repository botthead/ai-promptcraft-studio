<?php
// src/actions/generate_prompt_action.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // Para $pdo, se necessário no futuro
require_once __DIR__ . '/../core/functions.php';   // Para redirect(), e()
require_once __DIR__ . '/../core/auth.php';       // Para require_login()

require_login(); // Apenas usuários logados podem gerar prompts

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message_generator'] = "Acesso inválido.";
    redirect(BASE_URL . 'generator.php');
}

// Limpar dados e erros anteriores específicos do gerador
unset($_SESSION['form_errors_generator']);
unset($_SESSION['generated_prompt_text']);
unset($_SESSION['last_input_parameters']);

// Coleta e sanitização básica dos inputs
$input_parameters = [
    'prompt_title'         => trim($_POST['prompt_title'] ?? ''),
    'target_ia'            => trim($_POST['target_ia'] ?? 'IA Genérica'),
    'objective'            => trim($_POST['objective'] ?? ''),
    'audience'             => trim($_POST['audience'] ?? ''),
    'tone_style'           => trim($_POST['tone_style'] ?? ''),
    'keywords'             => trim($_POST['keywords'] ?? ''),
    'output_format'        => trim($_POST['output_format'] ?? ''),
    'context_restrictions' => trim($_POST['context_restrictions'] ?? '')
];

// Armazenar os dados submetidos para preencher o formulário em caso de erro
$_SESSION['form_data_generator'] = $input_parameters;
$_SESSION['last_input_parameters'] = $input_parameters; // Guardar para o formulário de salvar

$errors = [];

// Validação simples (pode ser expandida)
if (empty($input_parameters['objective'])) {
    $errors['objective'] = "O objetivo principal do prompt é obrigatório.";
}

if (!empty($errors)) {
    $_SESSION['form_errors_generator'] = $errors;
    $_SESSION['error_message_generator'] = "Por favor, corrija os erros abaixo.";
    redirect(BASE_URL . 'generator.php');
}

// Lógica de Geração do Prompt (Concatenação Simples por enquanto)
$generated_prompt = "Para a IA: " . e($input_parameters['target_ia']) . "\n\n";
$generated_prompt .= "Objetivo Principal: " . e($input_parameters['objective']) . "\n";

if (!empty($input_parameters['audience'])) {
    $generated_prompt .= "Público-Alvo: " . e($input_parameters['audience']) . "\n";
}
if (!empty($input_parameters['tone_style'])) {
    $generated_prompt .= "Tom/Estilo: " . e($input_parameters['tone_style']) . "\n";
}
if (!empty($input_parameters['keywords'])) {
    $generated_prompt .= "Palavras-chave Essenciais: " . e($input_parameters['keywords']) . "\n";
}
if (!empty($input_parameters['output_format'])) {
    $generated_prompt .= "Formato de Saída Esperado: " . e($input_parameters['output_format']) . "\n";
}
if (!empty($input_parameters['context_restrictions'])) {
    $generated_prompt .= "Contexto Adicional/Restrições:\n" . e($input_parameters['context_restrictions']) . "\n";
}

$generated_prompt .= "\nPor favor, gere uma resposta com base nas informações acima.";

// Armazenar o prompt gerado na sessão para exibir na página generator.php
$_SESSION['generated_prompt_text'] = $generated_prompt;

// Limpar dados do formulário da sessão se a geração foi "bem-sucedida" (sem erros de validação)
// mas manter o last_input_parameters para o botão de salvar
unset($_SESSION['form_data_generator']); 

redirect(BASE_URL . 'generator.php');
?>