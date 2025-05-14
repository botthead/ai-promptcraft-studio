<?php
// src/core/functions.php
require_once dirname(__DIR__) . '/config/constants.php'; // Garante que as constantes de criptografia sejam carregadas

/**
 * Sanitiza uma string para exibição em HTML.
 * @param string|null $string A string a ser sanitizada.
 * @return string A string sanitizada.
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redireciona o usuário para uma URL.
 * @param string $url A URL para redirecionar.
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Criptografa um texto usando AES-256-CBC.
 * @param string $plaintext O texto a ser criptografado.
 * @return string|false O texto criptografado em base64 ou false em caso de erro.
 */
function encrypt_data($plaintext) {
    if (empty(ENCRYPTION_KEY) || empty(ENCRYPTION_IV_KEY)) {
        error_log("Chave de criptografia ou IV não definidos para encrypt_data.");
        return false;
    }
    $key = hex2bin(ENCRYPTION_KEY);
    $iv = hex2bin(ENCRYPTION_IV_KEY);

    if (strlen($key) !== 32) {
        error_log("A chave de criptografia (após hex2bin) deve ter 32 bytes para AES-256-CBC.");
        return false;
    }
    if (strlen($iv) !== 16) {
        error_log("O vetor de inicialização (IV) (após hex2bin) deve ter 16 bytes para AES-256-CBC.");
        return false;
    }

    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        error_log("OpenSSL encryption failed: " . openssl_error_string());
        return false;
    }
    return base64_encode($ciphertext);
}

/**
 * Descriptografa um texto usando AES-256-CBC.
 * @param string $ciphertext_base64 O texto criptografado em base64.
 * @return string|false O texto original ou false em caso de erro.
 */
function decrypt_data($ciphertext_base64) {
    if (empty(ENCRYPTION_KEY) || empty(ENCRYPTION_IV_KEY)) {
        error_log("Chave de criptografia ou IV não definidos para decrypt_data.");
        return false;
    }
    $key = hex2bin(ENCRYPTION_KEY);
    $iv = hex2bin(ENCRYPTION_IV_KEY);

    if (strlen($key) !== 32) {
        error_log("A chave de criptografia (após hex2bin) deve ter 32 bytes para descriptografia AES-256-CBC.");
        return false;
    }
    if (strlen($iv) !== 16) {
        error_log("O vetor de inicialização (IV) (após hex2bin) deve ter 16 bytes para descriptografia AES-256-CBC.");
        return false;
    }

    $ciphertext_raw = base64_decode($ciphertext_base64);
    if ($ciphertext_raw === false) {
        error_log("Base64 decode failed during decryption.");
        return false;
    }
    $plaintext = openssl_decrypt($ciphertext_raw, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($plaintext === false) {
        error_log("OpenSSL decryption failed: " . openssl_error_string());
        return false;
    }
    return $plaintext;
}
?>