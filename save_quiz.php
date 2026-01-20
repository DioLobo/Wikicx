<?php
require_once 'config/db.php';

header('Content-Type: application/json');

// Só aceita se estiver logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não logado']);
    exit;
}

// Recebe o JSON do Javascript
$data = json_decode(file_get_contents('php://input'), true);
$score = $data['score'] ?? 0;
$total = 5; // Total de perguntas
$user_id = $_SESSION['user_id'];

// 1. Verifica se já fez o quiz antes (Segurança)
$stmt = $pdo->prepare("SELECT id FROM user_quiz_attempts WHERE user_id = :uid");
$stmt->execute(['uid' => $user_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Quiz já realizado']);
    exit;
}

// 2. Salva a tentativa no Banco
$stmt = $pdo->prepare("INSERT INTO user_quiz_attempts (user_id, score, total_questions) VALUES (:uid, :score, :total)");
$saved = $stmt->execute(['uid' => $user_id, 'score' => $score, 'total' => $total]);

// 3. Dá XP ao usuário (Ex: 10 XP por acerto)
if ($saved && $score > 0) {
    $xp_ganho = $score * 10;
    $stmt = $pdo->prepare("UPDATE users SET current_xp = current_xp + :xp WHERE id = :uid");
    $stmt->execute(['xp' => $xp_ganho, 'uid' => $user_id]);
    
    // Atualiza XP na sessão também para aparecer na hora
    $_SESSION['user_xp'] += $xp_ganho;
}

echo json_encode(['success' => true]);
?>