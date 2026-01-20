<?php
require_once 'config/db.php';

// Correção de Sessão
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Segurança
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') { die("Acesso Negado."); }

$msg = "";
$edit_mode = false;
$edit_data = ['name'=>'', 'id'=>''];

// 1. DELETAR
if (isset($_GET['del'])) {
    $stmt = $pdo->prepare("DELETE FROM modules WHERE id = :id");
    if ($stmt->execute(['id' => $_GET['del']])) {
        header("Location: admin_modulos.php?msg=deleted"); exit;
    }
}

// 2. MODO EDIÇÃO
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $fetched = $stmt->fetch();
    if($fetched) $edit_data = $fetched;
}

// 3. SALVAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // UPDATE
            $stmt = $pdo->prepare("UPDATE modules SET name = :name WHERE id = :id");
            $res = $stmt->execute(['name' => $name, 'id' => $_POST['id']]);
            $msg = $res ? "<p class='success'>Módulo atualizado!</p>" : "<p class='error'>Erro ao atualizar.</p>";
            $edit_mode = false;
            $edit_data = ['name'=>'', 'id'=>''];
        } else {
            // INSERT
            $stmt = $pdo->prepare("INSERT INTO modules (name) VALUES (:name)");
            $res = $stmt->execute(['name' => $name]);
            $msg = $res ? "<p class='success'>Módulo criado!</p>" : "<p class='error'>Erro ao criar.</p>";
        }
    } else {
        $msg = "<p class='error'>O nome não pode ser vazio.</p>";
    }
}

// 4. LISTAR
$modulos = $pdo->query("SELECT * FROM modules ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Módulos | CXPRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') { document.documentElement.classList.add('light-mode'); }
    </script>
    <style>
        /* MESMO CSS DO ADMIN_AULAS */
        :root { --bg-color: #050505; --card-bg: #111; --text-main: #ffffff; --text-sec: #a0a0a0; --accent: #00ff88; --border: rgba(255, 255, 255, 0.1); --input-bg: rgba(255,255,255,0.05); }
        html.light-mode body { --bg-color: #f4f6f8; --card-bg: #ffffff; --text-main: #1a1a1a; --text-sec: #555555; --accent: #2563eb; --border: rgba(0, 0, 0, 0.1); --input-bg: #f0f0f0; }
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: 0.3s; }
        body { background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; } /* Largura menor pois é mais simples */
        .card { background: var(--card-bg); padding: 30px; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 30px; }
        h2 { color: var(--accent); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        
        label { display: block; margin-bottom: 5px; color: var(--text-sec); font-size: 0.9rem; }
        input { width: 100%; padding: 10px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 5px; color: var(--text-main); margin-bottom: 15px; outline: none; }
        input:focus { border-color: var(--accent); }
        
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
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="admin.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 style="color:var(--text-main);">Gerenciar Módulos</h1>
    </div>

    <?= $msg ?>
    <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted') echo "<p class='success'>Módulo excluído!</p>"; ?>

    <div class="card">
        <h2><i class="fas fa-layer-group"></i> <?= $edit_mode ? 'Editar Módulo' : 'Novo Módulo' ?></h2>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            
            <label>Nome do Módulo (Ex: Biologia, Inglês)</label>
            <input type="text" name="name" value="<?= htmlspecialchars($edit_data['name']) ?>" required autofocus>

            <button type="submit"><?= $edit_mode ? 'SALVAR ALTERAÇÃO' : 'CADASTRAR MÓDULO' ?></button>
            <?php if($edit_mode): ?>
                <a href="admin_modulos.php" class="btn-cancel">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>Módulos Ativos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($modulos as $m): ?>
                <tr>
                    <td><?= $m['id'] ?></td>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td class="actions">
                        <a href="admin_modulos.php?edit=<?= $m['id'] ?>" class="btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="admin_modulos.php?del=<?= $m['id'] ?>" class="btn-del" title="Excluir" onclick="return confirm('Tem certeza? Isso pode deixar aulas sem categoria.')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>