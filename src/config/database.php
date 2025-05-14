<?php
// Inclui as constantes do projeto
// O caminho é relativo ao arquivo atual (database.php)
require_once __DIR__ . '/constants.php';

$pdo = null;
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
    // Em produção, não exiba $e->getMessage() diretamente
    die("Erro crítico: Não foi possível conectar ao banco de dados. Verifique os logs do servidor.");
}
?>