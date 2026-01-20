<?php
// Arquivo: config/session_check.php

// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Garante conexão com o banco (ajuste o caminho se necessário, 
// o __DIR__ ajuda a achar o arquivo db.php na mesma pasta config)
require_once __DIR__ . '/db.php'; 

// 1. Se não estiver logado, manda pro login imediatamente
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. O VIGILANTE: Verifica se o token do navegador é igual ao do banco
// Busca o token oficial que está gravado no banco para este usuário
$stmt = $pdo->prepare("SELECT session_token FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$db_token = $stmt->fetchColumn();

// 3. Compara: O token do banco é diferente do token que tenho aqui no navegador?
if ($db_token !== $_SESSION['session_token']) {
    // Se for diferente, significa que alguém logou em outro lugar!
    session_destroy(); // Destrói esta sessão
    // Redireciona com aviso de 'kicked' (chutado)
    header('Location: login.php?msg=kicked'); 
    exit;
}
?>