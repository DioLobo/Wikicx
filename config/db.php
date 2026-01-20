<?php
// config/db.php

// 1. Função para carregar o .env manualmente (sem precisar instalar bibliotecas externas)
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignora comentários
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Carrega as variáveis do arquivo .env que está na raiz
loadEnv(__DIR__ . '/../.env');

// 2. Configurações pegando do .env
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'wikicx';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    // Conexão PDO segura
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- SINCRONIZAÇÃO DE HORÁRIO ---
    // Define o fuso horário no PHP
    date_default_timezone_set('America/Sao_Paulo');
    
    // Define o fuso horário no MySQL (Horário de Brasília)
    $pdo->exec("SET time_zone='-03:00'");
    // --------------------------------


} catch (PDOException $e) {
    // Em produção, nunca mostre o erro real para o usuário!
    die("Erro de conexão (Code: 001). Contate o suporte.");
}

// Inicia sessão segura
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>