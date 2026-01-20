<?php
require_once 'config/db.php';

// CORREÇÃO DE SESSÃO
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SEGURANÇA
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Verifica Admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'admin') { die("Acesso Negado."); }

$msg = "";
$edit_mode = false;
$edit_data = ['title'=>'', 'module'=>'', 'description'=>'', 'video_url'=>'', 'xp_reward'=>'30', 'pdf_file'=>'', 'id'=>''];

// Verifica se o POST estourou o limite (acontece quando envia arquivo muito grande)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $msg = "<p class='error'>O arquivo enviado é muito grande! Aumente o limite no php.ini ou envie um vídeo menor.</p>";
}

// 1. DELETAR
if (isset($_GET['del'])) {
    $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = :id");
    if ($stmt->execute(['id' => $_GET['del']])) {
        header("Location: admin_aulas.php?msg=deleted"); exit;
    }
}

if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $msg = "<p class='success'>🗑️ Aula excluída com sucesso!</p>";
}

// 2. MODO EDIÇÃO
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $fetched = $stmt->fetch();
    if($fetched) $edit_data = $fetched;
}

// 3. SALVAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $title = $_POST['title'];
    $module = $_POST['module'];
    $desc = $_POST['description'];
    $xp = $_POST['xp_reward'];
    
    // --- LÓGICA DE VÍDEO (LINK OU UPLOAD) ---
    $video_final = $_POST['video_url']; // Começa com o link (se houver)

    // Se enviou um arquivo de vídeo, o arquivo tem prioridade
    if (isset($_FILES['local_video']) && $_FILES['local_video']['error'] === 0) {
        $allowed_video = ['mp4', 'webm', 'ogg'];
        $ext_vid = strtolower(pathinfo($_FILES['local_video']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext_vid, $allowed_video)) {
            $new_vid_name = 'video_' . time() . '.' . $ext_vid;
            $dest_vid = 'uploads/videos/' . $new_vid_name;
            
            if (!is_dir('uploads/videos')) { mkdir('uploads/videos', 0777, true); }
            
            if (move_uploaded_file($_FILES['local_video']['tmp_name'], $dest_vid)) {
                $video_final = $dest_vid; 
            }
        }
    }

    // --- LÓGICA DE UPLOAD DE PDF ---
    $pdf_name = $edit_data['pdf_file']; // Mantém o antigo por padrão

    // Se enviou um novo PDF
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $new_name = 'material_' . time() . '.pdf';
            $dest = 'uploads/materials/' . $new_name;
            if (!is_dir('uploads/materials')) { mkdir('uploads/materials', 0777, true); }
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $dest)) {
                $pdf_name = $new_name;
            }
        }
    }

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // UPDATE
        $sql = "UPDATE lessons SET title=:t, module=:m, description=:d, video_url=:v, xp_reward=:x, pdf_file=:pdf WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([
            't'=>$title, 'm'=>$module, 'd'=>$desc, 'v'=>$video_final, 'x'=>$xp, 
            'pdf'=>$pdf_name, 'id'=>$_POST['id']
        ]);
        $msg = $res ? "<p class='success'>✅ Aula atualizada!</p>" : "<p class='error'>Erro ao atualizar.</p>";
        $edit_mode = false; 
        $edit_data = ['title'=>'', 'module'=>'', 'description'=>'', 'video_url'=>'', 'xp_reward'=>'30', 'pdf_file'=>'', 'id'=>''];
    } else {
        // INSERT
        $sql = "INSERT INTO lessons (title, module, description, video_url, xp_reward, pdf_file) VALUES (:t, :m, :d, :v, :x, :pdf)";
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([
            't'=>$title, 'm'=>$module, 'd'=>$desc, 'v'=>$video_final, 'x'=>$xp, 
            'pdf'=>$pdf_name
        ]);
        $msg = $res ? "<p class='success'>✅ Aula criada!</p>" : "<p class='error'>Erro ao criar.</p>";
    }
}

// 4. LISTAR
$aulas = $pdo->query("SELECT * FROM lessons ORDER BY id DESC")->fetchAll();
$db_modules = $pdo->query("SELECT name FROM modules ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Aulas | CXPRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') { document.documentElement.classList.add('light-mode'); }
    </script>
    <style>
        :root { --bg-color: #050505; --card-bg: #111; --text-main: #ffffff; --text-sec: #a0a0a0; --accent: #00ff88; --border: rgba(255, 255, 255, 0.1); --input-bg: rgba(255,255,255,0.05); }
        html.light-mode body { --bg-color: #f4f6f8; --card-bg: #ffffff; --text-main: #1a1a1a; --text-sec: #555555; --accent: #2563eb; --border: rgba(0, 0, 0, 0.1); --input-bg: #f0f0f0; }
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: 0.3s; }
        body { background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 40px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: var(--card-bg); padding: 30px; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 30px; }
        h2 { color: var(--accent); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        
        label { display: block; margin-bottom: 5px; color: var(--text-sec); font-size: 0.9rem; }
        input, select, textarea { width: 100%; padding: 10px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 5px; color: var(--text-main); margin-bottom: 15px; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--accent); }
        
        .file-input-wrapper { border: 1px dashed var(--border); padding: 15px; border-radius: 5px; margin-bottom: 15px; text-align: center; cursor: pointer; transition: 0.3s; }
        .file-input-wrapper:hover { border-color: var(--accent); background: rgba(0, 255, 136, 0.05); }
        .file-input-wrapper input[type="file"] { display: none; }
        .file-input-label { cursor: pointer; font-size: 0.9rem; color: var(--text-main); }
        .file-input-label i { font-size: 1.2rem; margin-bottom: 5px; display: block; color: var(--accent); }

        select option { background-color: #111; color: #fff; }
        html.light-mode select option { background-color: #fff; color: #000; }
        
        button { width: 100%; padding: 12px; background: var(--accent); color: #000; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 136, 0.3); }
        
        .success { background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #00ff88; }
        html.light-mode .success { background: #d1fae5; color: #065f46; border-color: #065f46; }
        .error { background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #ff4444; }

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
        
        .pdf-badge { font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; border: 1px solid var(--accent); color: var(--accent); font-weight: bold; }
        .vid-badge { font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; border: 1px solid #ebbc25; color: #ebbc25; font-weight: bold; }
        .no-pdf { font-size: 0.75rem; color: var(--text-sec); opacity: 0.5; }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="admin.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 style="color:var(--text-main);">Gerenciar Aulas</h1>
    </div>

    <?= $msg ?>

    <div class="card">
        <h2><i class="fas fa-video"></i> <?= $edit_mode ? 'Editar Aula' : 'Nova Aula' ?></h2>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            
            <label>Título</label>
            <input type="text" name="title" value="<?= htmlspecialchars($edit_data['title']) ?>" required>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                <div>
                    <label>Módulo</label>
                    <select name="module" required>
                        <option value="" disabled selected>Selecione um módulo</option>
                        <?php foreach($db_modules as $mod): ?>
                            <?php $selected = ($edit_data['module'] == $mod['name']) ? 'selected' : ''; ?>
                            <option value="<?= htmlspecialchars($mod['name']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($mod['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>XP</label>
                    <input type="number" name="xp_reward" value="<?= $edit_data['xp_reward'] ?>" required>
                </div>
            </div>

            <label style="margin-top:20px; border-top:1px solid var(--border); padding-top:20px;">Vídeo da Aula</label>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <label>Opção A: Link (YouTube/Vimeo)</label>
                    <input type="text" name="video_url" value="<?= htmlspecialchars($edit_data['video_url']) ?>" placeholder="https://youtube.com/...">
                </div>
                <div>
                    <label>Opção B: Upload Arquivo (MP4)</label>
                    <div class="file-input-wrapper" onclick="document.getElementById('vid-upload').click()">
                        <div class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i> Clique para escolher vídeo
                        </div>
                        <input type="file" id="vid-upload" name="local_video" accept="video/mp4,video/webm">
                        <div id="vid-name" style="margin-top:5px; font-size:0.8rem; color:var(--text-sec);"></div>
                    </div>
                </div>
            </div>

            <label>Material de Apoio (PDF)</label>
            <div class="file-input-wrapper" onclick="document.getElementById('pdf-upload').click()">
                <div class="file-input-label">
                    <i class="fas fa-file-pdf"></i> Escolher PDF (Opcional)
                </div>
                <input type="file" id="pdf-upload" name="pdf_file" accept=".pdf">
                <?php if($edit_mode && !empty($edit_data['pdf_file'])): ?>
                    <p style="margin:5px 0 0; font-size:0.8rem; color:var(--accent);">
                        <i class="fas fa-check"></i> Arquivo atual: <?= htmlspecialchars($edit_data['pdf_file']) ?>
                    </p>
                <?php endif; ?>
                <div id="pdf-name" style="margin-top:5px; font-size:0.8rem; color:var(--text-sec);"></div>
            </div>

            <label>Descrição</label>
            <textarea name="description" rows="3" required><?= htmlspecialchars($edit_data['description']) ?></textarea>

            <button type="submit"><?= $edit_mode ? 'SALVAR ALTERAÇÕES' : 'CADASTRAR AULA' ?></button>
            <?php if($edit_mode): ?>
                <a href="admin_aulas.php" class="btn-cancel">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>Lista de Aulas</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Módulo</th>
                    <th>Tipo Vídeo</th>
                    <th>PDF</th> <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($aulas as $aula): ?>
                <tr>
                    <td><?= $aula['id'] ?></td>
                    <td><?= htmlspecialchars($aula['title']) ?></td>
                    <td><span style="padding:2px 8px; border-radius:4px; background:rgba(255,255,255,0.1); font-size:0.8rem;"><?= $aula['module'] ?></span></td>
                    
                    <td>
                        <?php if(strpos($aula['video_url'], 'uploads/') !== false): ?>
                            <span class="vid-badge" style="border-color:#ebbc25; color:#ebbc25;">ARQUIVO</span>
                        <?php else: ?>
                            <span class="pdf-badge">LINK</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if(!empty($aula['pdf_file'])): ?>
                            <span class="pdf-badge">SIM</span>
                        <?php else: ?>
                            <span class="no-pdf">-</span>
                        <?php endif; ?>
                    </td>

                    <td class="actions">
                        <a href="admin_aulas.php?edit=<?= $aula['id'] ?>" class="btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="admin_aulas.php?del=<?= $aula['id'] ?>" class="btn-del" title="Excluir" onclick="return confirm('Tem certeza?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Script simples para mostrar o nome do arquivo selecionado
    document.getElementById('vid-upload').addEventListener('change', function(){
        document.getElementById('vid-name').textContent = this.files[0] ? this.files[0].name : '';
    });
    document.getElementById('pdf-upload').addEventListener('change', function(){
        document.getElementById('pdf-name').textContent = this.files[0] ? this.files[0].name : '';
    });
</script>

</body>
</html>