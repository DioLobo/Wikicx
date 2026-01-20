<?php
// Inicia o buffer para segurar qualquer texto indesejado
ob_start();

require_once 'config/db.php';
session_start();

// Define que a resposta será JSON limpo
header('Content-Type: application/json');

// Limpa qualquer aviso/erro/espaço que tenha sido gerado antes daqui
ob_clean(); 

$response = ['success' => false, 'message' => 'Erro desconhecido'];

try {
    if (!isset($_SESSION['user_id']) || !isset($_POST['lesson_id'])) {
        throw new Exception('Dados inválidos ou sessão expirada.');
    }

    $user_id = $_SESSION['user_id'];
    $lesson_id = $_POST['lesson_id'];
    $xp_reward = 30; 

    // Verifica se já completou
    $check = $pdo->prepare("SELECT id FROM user_progress WHERE user_id = :uid AND lesson_id = :lid");
    $check->execute(['uid' => $user_id, 'lid' => $lesson_id]);

    if ($check->rowCount() == 0) {
        // Marca como concluída
        $stmt = $pdo->prepare("INSERT INTO user_progress (user_id, lesson_id, completed_at) VALUES (:uid, :lid, NOW())");
        $stmt->execute(['uid' => $user_id, 'lid' => $lesson_id]);

        // Dá o XP
        $xpStmt = $pdo->prepare("UPDATE users SET current_xp = current_xp + :xp WHERE id = :uid");
        $xpStmt->execute(['xp' => $xp_reward, 'uid' => $user_id]);

        // Atualiza nível
        $pdo->prepare("UPDATE users SET level = FLOOR(current_xp / 100) + 1 WHERE id = :uid")->execute(['uid' => $user_id]);

        $response = ['success' => true, 'new_completion' => true];
    } else {
        $response = ['success' => true, 'new_completion' => false];
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// Imprime APENAS o JSON
echo json_encode($response);
exit;
?>