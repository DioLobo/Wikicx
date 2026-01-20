<?php
require_once 'config/db.php';

// CORREÇÃO DE SESSÃO
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
if ($stmt->fetch()['role'] !== 'admin') { die("Acesso Negado"); }

$msg = "";
$edit_mode = false;

// Dados padrão (Vazios)
$edit_data = ['id'=>'', 'question_text'=>'', 'module'=>'', 'difficulty'=>'Médio', 'xp_reward'=>'20'];
$edit_opts = [['text'=>'', 'correct'=>0], ['text'=>'', 'correct'=>0], ['text'=>'', 'correct'=>0], ['text'=>'', 'correct'=>0]];
$correct_idx = 1; 

// 1. DELETAR
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM quiz_options WHERE question_id = :id")->execute(['id'=>$_GET['del']]);
    $pdo->prepare("DELETE FROM quiz_questions WHERE id = :id")->execute(['id'=>$_GET['del']]);
    header("Location: admin_quiz.php?msg=deleted"); exit;
}

// 2. MODO EDIÇÃO
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $fetched = $stmt->fetch();
    if($fetched) $edit_data = $fetched;

    $stmt_opt = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id = :id ORDER BY id ASC");
    $stmt_opt->execute(['id' => $_GET['edit']]);
    $opts_db = $stmt_opt->fetchAll();
    
    if($opts_db) {
        foreach($opts_db as $k => $o) {
            if(isset($edit_opts[$k])) {
                $edit_opts[$k]['text'] = $o['option_text'];
                if($o['is_correct']) $correct_idx = $k + 1;
            }
        }
    }
}

if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $msg = "<p class='success'>🗑️ Questão excluída com sucesso!</p>";
}

// 3. SALVAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module = $_POST['quiz_module'];
    $q_text = $_POST['question'];
    $diff = $_POST['difficulty'];
    $xp = $_POST['quiz_xp'];
    $correct = $_POST['correct'];
    
    $options_to_save = [
        $_POST['opt_1'], $_POST['opt_2'], $_POST['opt_3'], $_POST['opt_4']
    ];

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // UPDATE
        $qid = $_POST['id'];
        $sql = "UPDATE quiz_questions SET module=:m, question_text=:q, difficulty=:d, xp_reward=:x WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['m'=>$module, 'q'=>$q_text, 'd'=>$diff, 'x'=>$xp, 'id'=>$qid]);
        
        // Refazer Opções
        $pdo->prepare("DELETE FROM quiz_options WHERE question_id = :id")->execute(['id'=>$qid]);
        
        $stmt_opt = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (:qid, :txt, :corr)");
        foreach($options_to_save as $k => $text) {
            $is_c = ($k + 1 == $correct) ? 1 : 0;
            $stmt_opt->execute(['qid'=>$qid, 'txt'=>$text, 'corr'=>$is_c]);
        }
        
        $msg = "<p class='success'>✅ Questão atualizada com sucesso!</p>";
        
        $edit_mode = false;
        $edit_data = ['id'=>'', 'question_text'=>'', 'module'=>'', 'difficulty'=>'Médio', 'xp_reward'=>'20'];
        $edit_opts = [['text'=>'', 'correct'=>0], ['text'=>'', 'correct'=>0], ['text'=>'', 'correct'=>0], ['text'=>'', 'correct'=>0]];
        $correct_idx = 1;

    } else {
        // INSERT
        $stmt = $pdo->prepare("INSERT INTO quiz_questions (module, question_text, difficulty, xp_reward) VALUES (:m, :q, :d, :x)");
        $stmt->execute(['m'=>$module, 'q'=>$q_text, 'd'=>$diff, 'x'=>$xp]);
        $qid = $pdo->lastInsertId();

        $stmt_opt = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (:qid, :txt, :corr)");
        foreach($options_to_save as $k => $text) {
            $is_c = ($k + 1 == $correct) ? 1 : 0;
            $stmt_opt->execute(['qid'=>$qid, 'txt'=>$text, 'corr'=>$is_c]);
        }
        $msg = "<p class='success'>✅ Nova questão criada!</p>";
    }
}

// 4. LISTAR
$questoes = $pdo->query("SELECT * FROM quiz_questions ORDER BY id DESC")->fetchAll();
// 5. BUSCAR MÓDULOS (Para o Select)
$db_modules = $pdo->query("SELECT name FROM modules ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Quiz | CXPRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') { document.documentElement.classList.add('light-mode'); }
    </script>
    <style>
        /* CSS MANTIDO */
        :root { --bg-color: #050505; --card-bg: #111; --text-main: #ffffff; --text-sec: #a0a0a0; --accent: #00ff88; --border: rgba(255, 255, 255, 0.1); --input-bg: rgba(255,255,255,0.05); }
        html.light-mode body { --bg-color: #f4f6f8; --card-bg: #ffffff; --text-main: #1a1a1a; --text-sec: #555555; --accent: #2563eb; --border: rgba(0, 0, 0, 0.1); --input-bg: #f0f0f0; }
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: 0.3s; }
        body { background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 40px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: var(--card-bg); padding: 30px; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 30px; }
        h2 { color: var(--accent); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        input, select, textarea { width: 100%; padding: 10px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 5px; color: var(--text-main); margin-bottom: 15px; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--accent); }
        select option { background-color: #111; color: #fff; }
        html.light-mode select option { background-color: #fff; color: #000; }
        button { width: 100%; padding: 12px; background: var(--accent); color: #000; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 136, 0.3); }
        .success { background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #00ff88; }
        html.light-mode .success { background: #d1fae5; color: #065f46; border-color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { color: var(--accent); }
        .actions a { margin-right: 10px; text-decoration: none; font-size: 1.1rem; }
        .btn-edit { color: #ebbc25; }
        .btn-del { color: #ff4444; }

        .btn-back { display: inline-flex; align-items: center; gap: 10px; padding: 10px 25px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 50px; color: var(--text-sec); text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-back:hover { border-color: var(--accent); color: var(--accent); background: var(--item-hover); box-shadow: 0 0 15px var(--accent-glow); transform: translateX(-5px); }

        .btn-cancel { display: block; width: 100%; padding: 12px; background: transparent; border: 1px solid var(--text-sec); color: var(--text-sec); border-radius: 8px; font-weight: bold; text-align: center; text-decoration: none; margin-top: 10px; transition: 0.3s; box-sizing: border-box; }
        .btn-cancel:hover { border-color: #ff4444; color: #ff4444; background: rgba(255, 68, 68, 0.1); }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="admin.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 style="color:var(--text-main);">Gerenciar Quiz</h1>
    </div>

    <?= $msg ?>

    <div class="card">
        <h2><i class="fas fa-question-circle"></i> <?= $edit_mode ? 'Editar Questão' : 'Nova Questão' ?></h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                <div>
                    <label>Módulo</label>
                    <select name="quiz_module" required>
                        <option value="" disabled selected>Selecione...</option>
                        <?php foreach($db_modules as $mod): ?>
                            <?php $selected = ($edit_data['module'] == $mod['name']) ? 'selected' : ''; ?>
                            <option value="<?= htmlspecialchars($mod['name']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($mod['name']) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="Geral" <?= $edit_data['module']=='Geral'?'selected':'' ?>>Geral (Simulado)</option>
                    </select>
                </div>
                <div>
                    <label>Dificuldade</label>
                    <select name="difficulty">
                        <option value="Fácil" <?= $edit_data['difficulty']=='Fácil'?'selected':'' ?>>Fácil</option>
                        <option value="Médio" <?= $edit_data['difficulty']=='Médio'?'selected':'' ?>>Médio</option>
                        <option value="Difícil" <?= $edit_data['difficulty']=='Difícil'?'selected':'' ?>>Difícil</option>
                    </select>
                </div>
                <div>
                    <label>XP</label>
                    <input type="number" name="quiz_xp" value="<?= $edit_data['xp_reward'] ?>">
                </div>
            </div>

            <label>Enunciado</label>
            <textarea name="question" rows="3" required><?= htmlspecialchars($edit_data['question_text']) ?></textarea>

            <label style="margin-top:20px; display:block; border-bottom:1px solid var(--border);">Alternativas</label>
            <div style="margin-top:10px;">
                <input type="text" name="opt_1" placeholder="Opção 1" value="<?= htmlspecialchars($edit_opts[0]['text']) ?>" required>
                <input type="text" name="opt_2" placeholder="Opção 2" value="<?= htmlspecialchars($edit_opts[1]['text']) ?>" required>
                <input type="text" name="opt_3" placeholder="Opção 3" value="<?= htmlspecialchars($edit_opts[2]['text']) ?>" required>
                <input type="text" name="opt_4" placeholder="Opção 4" value="<?= htmlspecialchars($edit_opts[3]['text']) ?>" required>
            </div>

            <label>Correta</label>
            <select name="correct" required style="border-color:var(--accent);">
                <option value="1" <?= $correct_idx==1?'selected':'' ?>>Opção 1</option>
                <option value="2" <?= $correct_idx==2?'selected':'' ?>>Opção 2</option>
                <option value="3" <?= $correct_idx==3?'selected':'' ?>>Opção 3</option>
                <option value="4" <?= $correct_idx==4?'selected':'' ?>>Opção 4</option>
            </select>

            <button type="submit"><?= $edit_mode ? 'SALVAR ALTERAÇÕES' : 'CADASTRAR QUESTÃO' ?></button>
            <?php if($edit_mode): ?>
                <a href="admin_quiz.php" class="btn-cancel">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>Lista de Questões</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Enunciado</th>
                    <th>Módulo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($questoes as $q): ?>
                <tr>
                    <td><?= $q['id'] ?></td>
                    <td><?= mb_strimwidth(htmlspecialchars($q['question_text']), 0, 50, "...") ?></td>
                    <td><span style="padding:2px 8px; border-radius:4px; background:rgba(255,255,255,0.1); font-size:0.8rem;"><?= $q['module'] ?></span></td>
                    <td class="actions">
                        <a href="admin_quiz.php?edit=<?= $q['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="admin_quiz.php?del=<?= $q['id'] ?>" class="btn-del" onclick="return confirm('Apagar questão?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>