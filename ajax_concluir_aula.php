<?php
// ajax_concluir_aula.php
require_once 'config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['lesson_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
    exit;
}

$user_id = $_SESSION['user_id'];
$lesson_id = $_POST['lesson_id'];

// 1. Verifica se já completou essa aula antes
$stmt = $pdo->prepare("SELECT id FROM user_progress WHERE user_id = ? AND lesson_id = ?");
$stmt->execute([$user_id, $lesson_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Aula já completada anteriormente']);
    exit;
}

// 2. Marca como concluída
$stmt = $pdo->prepare("INSERT INTO user_progress (user_id, lesson_id, completed_at) VALUES (?, ?, NOW())");
$stmt->execute([$user_id, $lesson_id]);

// 3. Busca o XP da aula
$stmt = $pdo->prepare("SELECT xp_reward FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$aula = $stmt->fetch();
$xp = $aula['xp_reward'] ?? 10; // Padrão 10 se der erro

// 4. Adiciona XP ao usuário
$stmt = $pdo->prepare("UPDATE users SET current_xp = current_xp + ? WHERE id = ?");
$stmt->execute([$xp, $user_id]);

// 5. Verifica Level Up (Opcional: Exemplo a cada 100xp)
$stmt = $pdo->prepare("UPDATE users SET level = floor(current_xp / 100) + 1 WHERE id = ?");
$stmt->execute([$user_id]);

echo json_encode(['status' => 'success', 'xp_gained' => $xp]);
?>