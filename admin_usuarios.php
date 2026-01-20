<?php
require_once 'config/db.php';
require_once 'config/mail.php'; // Adicionado para permitir envio de e-mail

// 1. Segurança e Sessão
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'admin') { die("Acesso Negado."); }

$msg = "";
$edit_mode = false;
$edit_data = ['id'=>'', 'name'=>'', 'email'=>'', 'role'=>'user', 'current_xp'=>'0', 'level'=>'1', 'status' => 'ativo'];

// --- NOVAS AÇÕES: APROVAR (COM E-MAIL) / BLOQUEAR ---
if (isset($_GET['approve'])) {
    $id_usuario = $_GET['approve'];
    
    // Busca dados do usuário antes de aprovar para enviar o e-mail
    $stmt_email = $pdo->prepare("SELECT name, email FROM users WHERE id = :id");
    $stmt_email->execute(['id' => $id_usuario]);
    $dados_destinatario = $stmt_email->fetch();

    if ($dados_destinatario) {
        // Atualiza status no banco
        $pdo->prepare("UPDATE users SET status = 'ativo' WHERE id = :id")->execute(['id' => $id_usuario]);

        // PREPARAÇÃO DO E-MAIL DE BOAS-VINDAS (Estilo Neon/Dark)
        $link_portal = "https://www.cxpro.net.br/wikicx/login.php"; 
        $logo_url = "https://static.wixstatic.com/media/477649_87217bbe599943d6874d07c5065e07a3~mv2.png/v1/fill/w_463,h_175,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/477649_87217bbe599943d6874d07c5065e07a3~mv2.png";

        $corpo_email = '
        <!DOCTYPE html>
        <html>
        <body style="margin:0; padding:0; background-color:#050505; font-family: sans-serif;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#050505; padding: 40px 0;">
                <tr>
                    <td align="center">
                        <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color:#111111; border: 1px solid #333; border-radius: 20px; box-shadow: 0 0 20px rgba(0, 255, 136, 0.1);">
                            <tr>
                                <td align="center" style="padding: 40px 0 20px 0;">
                                    <img src="' . $logo_url . '" alt="CXPRO Logo" width="180" style="display: block; border: 0;">
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding: 0 40px 40px 40px;">
                                    <h2 style="color: #00ff88; margin-bottom: 20px; font-size: 24px;">Conta Aprovada! 🎉</h2>
                                    <p style="color: #cccccc; font-size: 16px; line-height: 1.6;">
                                        Olá, <strong>' . htmlspecialchars($dados_destinatario['name']) . '</strong>!<br><br>
                                        Seu acesso ao portal foi revisado e <strong>aprovado</strong> por um administrador. 
                                        Agora você pode começar seus estudos e acessar todo o conteúdo exclusivo.
                                    </p>
                                    <br>
                                    <a href="' . $link_portal . '" style="display: inline-block; background-color: #00ff88; color: #000000; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: bold; text-transform: uppercase;">
                                        ACESSAR PORTAL
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="background-color: #0a0a0a; border-radius: 0 0 20px 20px; padding: 20px;">
                                    <p style="color: #444444; font-size: 12px; margin: 0;">&copy; ' . date('Y') . ' CXPRO. Todos os direitos reservados.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';

        sendMail($dados_destinatario['email'], 'Acesso Liberado - CXPRO', $corpo_email);
    }
    
    header("Location: admin_usuarios.php?msg=approved"); exit;
}

if (isset($_GET['block'])) {
    if ($_GET['block'] == $_SESSION['user_id']) {
        $msg = "<p class='error'>Você não pode bloquear a si mesmo!</p>";
    } else {
        $pdo->prepare("UPDATE users SET status = 'bloqueado' WHERE id = :id")->execute(['id' => $_GET['block']]);
        header("Location: admin_usuarios.php?msg=blocked"); exit;
    }
}

// Mensagens de Feedback
if(isset($_GET['msg'])){
    if($_GET['msg'] == 'deleted') $msg = "<p class='success'>Usuário removido com sucesso!</p>";
    if($_GET['msg'] == 'approved') $msg = "<p class='success'>Usuário aprovado e e-mail enviado!</p>";
    if($_GET['msg'] == 'blocked') $msg = "<p class='error'>Usuário bloqueado!</p>";
}

// 2. AÇÕES (Excluir)
if (isset($_GET['del'])) {
    if ($_GET['del'] == $_SESSION['user_id']) {
        $msg = "<p class='error'>Você não pode excluir sua própria conta!</p>";
    } else {
        $pdo->prepare("DELETE FROM user_progress WHERE user_id = :id")->execute(['id' => $_GET['del']]);
        $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $_GET['del']]);
        header("Location: admin_usuarios.php?msg=deleted"); exit;
    }
}

// 3. AÇÕES (Editar - Carregar Dados)
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $fetched = $stmt->fetch();
    if($fetched) $edit_data = $fetched;
}

// 4. AÇÕES (Salvar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status']; 
    $xp = (int)$_POST['current_xp'];
    $new_pass = $_POST['password'];

    $new_level = floor($xp / 100) + 1;

    if (!empty($id)) {
        $sql = "UPDATE users SET name=:n, email=:e, role=:r, current_xp=:x, level=:l, status=:s WHERE id=:id";
        $params = [
            'n' => $name, 
            'e' => $email, 
            'r' => $role, 
            'x' => $xp, 
            'l' => $new_level,
            's' => $status,
            'id' => $id
        ];
        
        $pdo->prepare($sql)->execute($params);

        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = :p WHERE id = :id")->execute(['p'=>$hash, 'id'=>$id]);
        }

        $msg = "<p class='success'>Usuário atualizado! Nível recalculado para $new_level.</p>";
        $edit_mode = false;
        $edit_data = ['id'=>'', 'name'=>'', 'email'=>'', 'role'=>'user', 'current_xp'=>'0', 'level'=>'1', 'status' => 'ativo'];
    }
}

// 5. LISTAR TODOS
$usuarios = $pdo->query("SELECT * FROM users ORDER BY status DESC, id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários | CXPRO</title>
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
        .container { max-width: 1100px; margin: 0 auto; }
        .card { background: var(--card-bg); padding: 30px; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 30px; }
        h2 { color: var(--accent); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        
        input, select { width: 100%; padding: 10px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 5px; color: var(--text-main); margin-bottom: 15px; outline: none; }
        input:focus, select:focus { border-color: var(--accent); }
        
        button { width: 100%; padding: 12px; background: var(--accent); color: #000; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 136, 0.3); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { color: var(--accent); }
        
        .actions a { margin-right: 8px; text-decoration: none; font-size: 1.1rem; }
        
        /* Badges de Status */
        .status-badge { padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .status-pendente { background: #ebbc25; color: #000; }
        .status-ativo { background: #00ff88; color: #000; }
        .status-bloqueado { background: #ff4444; color: #fff; }

        .role-badge { padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .role-admin { background: rgba(255, 68, 68, 0.2); color: #ff4444; border: 1px solid #ff4444; }
        .role-user { background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid #00ff88; }
        
        .btn-approve { color: #00ff88; }
        .btn-block { color: #ff9900; }
        .btn-edit { color: #ebbc25; }
        .btn-del { color: #ff4444; }

        .success { background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #00ff88; }
        .error { background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #ff4444; }

        .btn-back { display: inline-flex; align-items: center; gap: 10px; padding: 10px 25px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 50px; color: var(--text-sec); text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; }
        .btn-back:hover { border-color: var(--accent); color: var(--accent); background: var(--item-hover); transform: translateX(-5px); }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="admin.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 style="color:var(--text-main);">Gerenciar Usuários</h1>
    </div>

    <?= $msg ?>

    <?php if($edit_mode): ?>
    <div class="card">
        <h2><i class="fas fa-user-edit"></i> Editar Usuário</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            
            <label>Nome Completo</label>
            <input type="text" name="name" value="<?= htmlspecialchars($edit_data['name']) ?>" required>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_data['email']) ?>" required>
                </div>
                <div>
                    <label>Status da Conta</label>
                    <select name="status">
                        <option value="pendente" <?= $edit_data['status']=='pendente'?'selected':'' ?>>Pendente (Aguardando)</option>
                        <option value="ativo" <?= $edit_data['status']=='ativo'?'selected':'' ?>>Ativo (Liberado)</option>
                        <option value="bloqueado" <?= $edit_data['status']=='bloqueado'?'selected':'' ?>>Bloqueado (Sem Acesso)</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Função (Cargo)</label>
                    <select name="role">
                        <option value="student" <?= $edit_data['role']=='student'?'selected':'' ?>>Aluno</option>
                        <option value="admin" <?= $edit_data['role']=='admin'?'selected':'' ?>>Administrador</option>
                    </select>
                </div>
                <div>
                    <label>XP Atual</label>
                    <input type="number" name="current_xp" value="<?= $edit_data['current_xp'] ?>">
                </div>
            </div>

            <label>Nova Senha (Deixe em branco para não alterar)</label>
            <input type="text" name="password" placeholder="Digite apenas se quiser mudar a senha...">

            <button type="submit">SALVAR ALTERAÇÕES</button>
            <a href="admin_usuarios.php" style="display:block; text-align:center; margin-top:10px; color:var(--text-sec); text-decoration:none;">Cancelar</a>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2>Lista de Alunos</h2>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Nome / E-mail</th>
                    <th>Nível</th>
                    <th>Função</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr>
                    <td>
                        <span class="status-badge status-<?= $u['status'] ?>"><?= $u['status'] ?></span>
                    </td>
                    <td>
                        <b><?= htmlspecialchars($u['name']) ?></b><br>
                        <small style="color:var(--text-sec)"><?= htmlspecialchars($u['email']) ?></small>
                    </td>
                    <td>
                        <b style="color:var(--accent)">Nível <?= $u['level'] ?></b> 
                        <small style="color:var(--text-sec); font-size: 0.7rem;"> (<?= $u['current_xp'] ?> XP)</small>
                    </td>
                    <td>
                        <span class="role-badge role-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span>
                    </td>
                    <td class="actions">
                        <?php if($u['status'] === 'pendente'): ?>
                            <a href="admin_usuarios.php?approve=<?= $u['id'] ?>" class="btn-approve" title="Aprovar e Enviar E-mail"><i class="fas fa-check-circle"></i></a>
                        <?php endif; ?>

                        <?php if($u['status'] !== 'bloqueado' && $u['id'] != $_SESSION['user_id']): ?>
                            <a href="admin_usuarios.php?block=<?= $u['id'] ?>" class="btn-block" title="Bloquear"><i class="fas fa-ban"></i></a>
                        <?php endif; ?>

                        <a href="admin_usuarios.php?edit=<?= $u['id'] ?>" class="btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                        
                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                            <a href="admin_usuarios.php?del=<?= $u['id'] ?>" class="btn-del" title="Excluir" onclick="return confirm('Excluir permanentemente?')"><i class="fas fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>