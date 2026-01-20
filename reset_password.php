<?php
require_once 'config/db.php';

$msg = "";
$valid_token = false;

// 1. VERIFICA O TOKEN NA URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Define o fuso horário para garantir sincronia com a gravação
    date_default_timezone_set('America/Sao_Paulo');
    $agora = date("Y-m-d H:i:s");
    
    // Busca usuário com este token E que o token ainda não tenha expirado (> agora)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_expires > :agora");
    $stmt->execute([
        'token' => $token, 
        'agora' => $agora
    ]);
    
    $user = $stmt->fetch();

    if ($user) {
        $valid_token = true;
    } else {
        $msg = "<p class='error'>Link inválido ou expirado! Solicite uma nova recuperação.</p>";
    }
} else {
    // Se tentar entrar sem token na URL, manda pro login
    header('Location: login.php');
    exit;
}

// 2. PROCESSA A TROCA DE SENHA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($pass) < 6) {
        $msg = "<p class='error'>A senha deve ter pelo menos 6 caracteres.</p>";
    } elseif ($pass !== $confirm) {
        $msg = "<p class='error'>As senhas não coincidem!</p>";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = :pass, reset_token = NULL, reset_expires = NULL WHERE id = :id");
        
        if ($stmt->execute(['pass' => $hash, 'id' => $user['id']])) {
            $msg = "<p class='success'>Senha alterada com sucesso! <a href='login.php'>Faça Login</a></p>";
            $valid_token = false; 
        } else {
            $msg = "<p class='error'>Erro ao atualizar senha no banco.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha | CXPRO</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/wikicx/assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/wikicx/assets/img/favicon-16x16.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>

    <style>
        /* VARIÁVEIS (PADRÃO DARK) */
        :root { 
            --bg-color: #050505; 
            --card-bg: #111; 
            --text-main: #ffffff; 
            --text-sec: #a0a0a0; 
            --accent: #00ff88; 
            --border: rgba(255, 255, 255, 0.1); 
            --input-bg: rgba(255,255,255,0.05);
        }

        /* TEMA CLARO */
        html.light-mode body { 
            --bg-color: #f4f6f8; 
            --card-bg: #ffffff; 
            --text-main: #1a1a1a; 
            --text-sec: #555555; 
            --accent: #2563eb; 
            --border: rgba(0, 0, 0, 0.1); 
            --input-bg: #f0f0f0;
        }

        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s, border-color 0.3s; }
        
        /* AJUSTE DE CENTRALIZAÇÃO ABSOLUTA */
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px; 
        }
        
        /* LARGURA RESPONSIVA INTELIGENTE */
        .login-card { 
            background: var(--card-bg); 
            width: 100%; 
            max-width: 400px; 
            padding: 40px; 
            border-radius: 15px; 
            border: 1px solid var(--border); 
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1); 
            text-align: center; 
            position: relative;
        }
        .login-card:hover { border-color: var(--accent); }

        h2 { color: var(--accent); margin-bottom: 20px; font-size: 1.5rem; }
        
        input { 
            width: 100%; padding: 12px; background: var(--input-bg); 
            border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); margin-bottom: 20px; outline: none; transition: 0.3s;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 0 10px var(--accent); }
        
        button { 
            width: 100%; padding: 12px; background: var(--accent); color: #000; font-weight: bold; 
            border: none; border-radius: 8px; cursor: pointer; transition: 0.3s; 
        }
        button:hover { transform: scale(1.02); box-shadow: 0 0 15px var(--accent); }
        
        /* Mensagens de Feedback */
        .success { background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #00ff88; }
        .error { background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ff4444; }

        html.light-mode .success { background: #d1fae5; color: #065f46; border-color: #065f46; }
        html.light-mode .error { background: #fee2e2; color: #991b1b; border-color: #991b1b; }

        a { color: var(--accent); text-decoration: none; font-weight: bold; }

        .logo-img {
            max-width: 180px; 
            width: 100%;
            height: auto;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        @media (max-width: 480px) {
            .login-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="assets/img/logo.svg" alt="Logo CXPRO" class="logo-img">
        <h2><i class="fas fa-lock"></i> Definir Nova Senha</h2>
        <?= $msg ?>
        
        <?php if ($valid_token): ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Nova Senha" required>
            <input type="password" name="confirm_password" placeholder="Confirme a Nova Senha" required>
            <button type="submit">SALVAR SENHA</button>
        </form>
        <?php endif; ?>
        
        <?php if (!$valid_token && strpos($msg, 'sucesso') === false): ?>
            <div style="margin-top:20px;">
                <a href="forgot_password.php">Solicitar novo link</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>