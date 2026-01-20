<?php
// 1. Impedir cache do navegador (Crucial para APIs de verificação frequente)
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 
header('Content-Type: application/json');

// 2. Iniciar sessão de forma segura
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once '../config/db.php';

// 3. Verificar se as variáveis básicas existem
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
    echo json_encode(['status' => 'kicked']);
    exit;
}

try {
    // 4. Buscar o token atual no banco
    $stmt = $pdo->prepare("SELECT session_token FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $db_token = $stmt->fetchColumn();

    // 5. Lógica de Expulsão
    if ($db_token !== $_SESSION['session_token']) {
        // IMPORTANTE: Destruir a sessão local para que o próximo F5 não re-logue
        session_unset();
        session_destroy();
        
        echo json_encode(['status' => 'kicked']);
    } else {
        echo json_encode(['status' => 'ok']);
    }

} catch (PDOException $e) {
    // Caso o banco falhe, não expulsamos o usuário por erro técnico
    echo json_encode(['status' => 'error']);
}