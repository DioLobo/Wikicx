<?php
require_once 'config/db.php';

// Proteção: Só logado entra
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$msg = "";
$user_id = $_SESSION['user_id'];

// 1. Processar Upload de Foto
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_name = uniqid() . '.' . $ext;
        $dir = 'uploads/';
        
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $new_name)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
            $stmt->execute(['avatar' => $new_name, 'id' => $user_id]);
            $msg = "<p class='success'>Foto de perfil atualizada!</p>";
        } else {
            $msg = "<p class='error'>Erro ao salvar arquivo.</p>";
        }
    } else {
        $msg = "<p class='error'>Formato inválido! Use JPG, PNG ou GIF.</p>";
    }
}

// 2. Processar Troca de Senha
if (isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    
    if (password_verify($current_pass, $user_data['password'])) {
        if ($new_pass === $confirm_pass) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
            $stmt->execute(['pass' => $hash, 'id' => $user_id]);
            $msg = "<p class='success'>Senha alterada com sucesso!</p>";
        } else {
            $msg = "<p class='error'>As novas senhas não coincidem.</p>";
        }
    } else {
        $msg = "<p class='error'>Senha atual incorreta.</p>";
    }
}

// Recarrega dados
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

// LÓGICA DO AVATAR (Corrige imagem quebrada)
$has_avatar = false;
$avatar_path = "";

if (!empty($user['avatar']) && file_exists('uploads/' . $user['avatar'])) {
    $has_avatar = true;
    $avatar_path = "uploads/" . $user['avatar'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil | CXPRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>

    <style>
        /* Estilos Globais (DARK MODE) */
        :root { 
            --bg-color: #050505; 
            --card-bg: #111; 
            --text-main: #ffffff; 
            --text-sec: #a0a0a0; 
            --accent: #00ff88; 
            --accent-glow: rgba(0, 255, 136, 0.4);
            --border: rgba(255, 255, 255, 0.1); 
            --input-bg: rgba(255,255,255,0.05);
            --item-hover: rgba(255, 255, 255, 0.05);
        }
        
        /* TEMA CLARO */
        html.light-mode body { 
            --bg-color: #f4f6f8; 
            --card-bg: #ffffff; 
            --text-main: #1a1a1a; 
            --text-sec: #555555; 
            --accent: #2563eb; 
            --accent-glow: rgba(37, 99, 235, 0.4);
            --border: rgba(0, 0, 0, 0.1); 
            --input-bg: #f0f0f0;
            --item-hover: #e0e0e0;
        }

        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s, border-color 0.3s; }
        
        body { background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 40px; display: flex; justify-content: center; }
        
        .profile-container { width: 100%; max-width: 800px; }
        
        /* --- ESTILO DO BOTÃO VOLTAR (Padrão Unificado) --- */
        .btn-back {
            display: inline-flex;       /* Alinha ícone e texto */
            align-items: center;        /* Centraliza verticalmente */
            gap: 10px;                  /* Espaço entre a seta e o texto */
            padding: 10px 25px;         /* Tamanho do botão */
            background: var(--card-bg); /* Fundo igual aos cards */
            border: 1px solid var(--border);
            border-radius: 50px;        /* Borda redonda (Pílula) */
            color: var(--text-sec);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        /* Efeito ao passar o mouse */
        .btn-back:hover {
            border-color: var(--accent);       /* Borda Verde ou Azul */
            color: var(--accent);              /* Texto Verde ou Azul */
            background: var(--item-hover);     /* Fundo levemente mais claro */
            box-shadow: 0 0 15px var(--accent-glow); /* O brilho Neon! */
            transform: translateX(-5px);       /* Movimento para a esquerda */
        }
        
        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 15px; padding: 30px; margin-bottom: 30px; }
        h2 { border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px; color: var(--accent); }
        
        /* Área da Foto */
        .avatar-section { display: flex; align-items: center; gap: 30px; margin-bottom: 30px; }
        
        .avatar-img { 
            width: 100px; height: 100px; 
            border-radius: 50%; object-fit: cover; 
            border: 3px solid var(--accent); 
        }

        /* Ícone Padrão se não houver foto */
        .avatar-default {
            width: 100px; height: 100px;
            border-radius: 50%;
            border: 3px solid var(--accent);
            background: var(--input-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--text-sec);
        }

        .avatar-upload label { background: #333; padding: 8px 15px; border-radius: 5px; cursor: pointer; border: 1px solid var(--border); transition: 0.3s; font-size: 0.9rem; color: #fff; }
        .avatar-upload label:hover { border-color: var(--accent); color: var(--accent); }
        input[type="file"] { display: none; }
        
        /* Formulários */
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: var(--text-sec); font-size: 0.9rem; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 12px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); outline: none; transition: 0.3s;
        }
        input:focus { border-color: var(--accent); }
        
        .btn-save { background: var(--accent); color: #000; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-save:hover { transform: scale(1.05); box-shadow: 0 0 15px var(--accent); }
        
        /* --- MENSAGENS DE FEEDBACK --- */
        .success { background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #00ff88; }
        .error { background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #ff4444; }

        html.light-mode .success { background: #d1fae5; color: #065f46; border-color: #065f46; }
        html.light-mode .error { background: #fee2e2; color: #991b1b; border-color: #991b1b; }

    </style>
</head>
<body>

<div class="profile-container">
    <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
    
    <?= $msg ?>

    <div class="card">
        <h2>Meu Perfil</h2>
        <div class="avatar-section">
            
            <?php if($has_avatar): ?>
                <img src="<?= $avatar_path ?>" alt="Foto de Perfil" class="avatar-img">
            <?php else: ?>
                <div class="avatar-default">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>

            <div class="avatar-info">
                <h3 style="margin: 0; font-size: 1.5rem;"><?= htmlspecialchars($user['name']) ?></h3>
                <p style="color: var(--text-sec); margin: 5px 0;">Nível <?= $user['level'] ?> • <?= $user['current_xp'] ?> XP</p>
                
                <form method="POST" enctype="multipart/form-data" class="avatar-upload" style="margin-top: 15px;">
                    <label for="file-upload"><i class="fas fa-camera"></i> Trocar Foto</label>
                    <input id="file-upload" type="file" name="avatar" onchange="this.form.submit()">
                </form>
            </div>
        </div>
        
        <div class="form-group">
            <label>E-mail (Não alterável)</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity: 0.5; cursor: not-allowed;">
        </div>
    </div>

    <div class="card">
        <h2>Alterar Senha</h2>
        <form method="POST">
            <div class="form-group">
                <label>Senha Atual</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>Nova Senha</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirmar Nova Senha</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" name="update_password" class="btn-save">SALVAR NOVA SENHA</button>
        </form>
    </div>
</div>

</body>
</html>