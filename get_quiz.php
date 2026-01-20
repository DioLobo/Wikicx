<?php
require_once 'config/db.php';
header('Content-Type: application/json');

// Verifica se pediu um módulo específico
$moduleFilter = isset($_GET['module']) && !empty($_GET['module']) ? $_GET['module'] : null;

try {
    if ($moduleFilter) {
        // Busca 5 perguntas APENAS do módulo solicitado
        $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE module = :mod ORDER BY RAND() LIMIT 5");
        $stmt->execute(['mod' => $moduleFilter]);
    } else {
        // Busca 5 perguntas GERAIS (Aleatórias de qualquer módulo)
        $stmt = $pdo->query("SELECT * FROM quiz_questions ORDER BY RAND() LIMIT 5");
    }
    
    $questions_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $final_questions = [];

    foreach ($questions_db as $q) {
        // Busca opções para cada pergunta
        $stmt_opt = $pdo->prepare("SELECT option_text, is_correct FROM quiz_options WHERE question_id = :qid ORDER BY id ASC");
        $stmt_opt->execute(['qid' => $q['id']]);
        $options_db = $stmt_opt->fetchAll(PDO::FETCH_ASSOC);

        $opts_text = [];
        $correct_index = 0;

        foreach ($options_db as $index => $opt) {
            $opts_text[] = $opt['option_text'];
            if ($opt['is_correct'] == 1) {
                $correct_index = $index;
            }
        }

        if (count($opts_text) > 0) {
            $final_questions[] = [
                'q' => $q['question_text'],
                'module' => $q['module'], // Adicionei o módulo aqui para mostrar na tela
                'difficulty' => $q['difficulty'],
                'opts' => $opts_text,
                'ans' => $correct_index
            ];
        }
    }

    echo json_encode($final_questions);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar questões']);
}