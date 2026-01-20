<?php
require_once 'config/db.php';

// Se já estiver logado, manda pro Dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$msg = "";

if (isset($_GET['registered'])) {
    $msg = "<p class='success'>Conta criada! Faça login para continuar.</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $msg = "<p class='error'>Preencha e-mail e senha!</p>";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            
            // --- NOVA LÓGICA: VERIFICAÇÃO DE STATUS ---
            if ($user['status'] === 'pendente') {
                $msg = "<p class='error'>Sua conta está aguardando aprovação de um administrador.</p>";
            } elseif ($user['status'] === 'bloqueado') {
                $msg = "<p class='error'>Sua conta foi bloqueada. Entre em contato com o suporte.</p>";
            } else {
                // Se o status for 'ativo', procede com o login e a sessão única
                
                // --- LÓGICA EXISTENTE: SESSÃO ÚNICA (Derruba sessões anteriores) ---
                $session_token = bin2hex(random_bytes(32)); // Gera token único
                
                // Atualiza no banco
                $update = $pdo->prepare("UPDATE users SET session_token = :token WHERE id = :id");
                $update->execute(['token' => $session_token, 'id' => $user['id']]);
                // -------------------------------------------------------------

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_level'] = $user['level'];
                $_SESSION['user_xp'] = $user['current_xp'];
                $_SESSION['session_token'] = $session_token; // Salva o token na sessão atual
                
                header('Location: index.php');
                exit;
            }
        } else {
            $msg = "<p class='error'>E-mail ou senha incorretos!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CXPRO</title>
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
        
        /* AJUSTE DE CENTRALIZAÇÃO */
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; /* Ocupa toda a altura da tela */
            margin: 0; 
            padding: 20px; /* Evita que o card encoste nas bordas no mobile */
        }
        
        /* LARGURA RESPONSIVA */
        .login-card {
            background: var(--card-bg); 
            width: 100%; 
            max-width: 400px; /* Largura máxima para desktop */
            padding: 40px; 
            border-radius: 15px;
            border: 1px solid var(--border); 
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1); 
            text-align: center;
            position: relative;
        }
        .login-card:hover { border-color: var(--accent); }

        .logo-img {
            max-width: 180px; /* Logo um pouco menor para mobile */
            width: 100%;
            height: auto;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .theme-toggle {
            position: absolute; top: 20px; right: 20px;
            background: transparent; border: 1px solid var(--border);
            color: var(--text-sec); width: 35px; height: 35px;
            border-radius: 50%; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: 0.3s; padding: 0;
        }
        .theme-toggle:hover {
            border-color: var(--accent); color: var(--accent);
            transform: scale(1.1); box-shadow: 0 0 10px var(--accent);
        }

        p.subtitle { color: var(--text-sec); margin-bottom: 30px; font-size: 0.9rem; }
        
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--accent); }
        
        input {
            width: 100%; padding: 12px 15px 12px 45px; background: var(--input-bg);
            border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); outline: none; transition: 0.3s;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 0 10px var(--accent); }
        
        .btn-login {
            width: 100%; padding: 12px; background: var(--accent); color: #000; font-weight: bold;
            border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; transition: 0.3s;
        }
        .btn-login:hover { transform: scale(1.02); box-shadow: 0 0 15px var(--accent); }
        
        .links { margin-top: 25px; font-size: 0.9rem; display: flex; justify-content: space-between; }
        .links a { color: var(--text-sec); text-decoration: none; transition: 0.3s; }
        .links a:hover { color: var(--accent); text-decoration: underline; }
        
        .error { background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ff4444; }
        .success { background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #00ff88; }

        html.light-mode .success { background: #d1fae5; color: #065f46; border-color: #065f46; }
        html.light-mode .error { background: #fee2e2; color: #991b1b; border-color: #991b1b; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(5px);
            z-index: 9999; display: flex; align-items: center; justify-content: center;
        }
        .modal-content {
            background: var(--card-bg); padding: 40px; border-radius: 15px; 
            border: 1px solid var(--border); text-align: center; max-width: 90%; width: 400px;
            box-shadow: 0 0 30px rgba(255, 68, 68, 0.2); 
            animation: popIn 0.3s;
        }

        @media (max-width: 480px) {
            .login-card { padding: 30px 20px; }
            .links { flex-direction: column; gap: 10px; }
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popIn { from { transform: scale(0.8); } to { transform: scale(1); } }
    </style>
</head>
<body>
    <div class="login-card">
        <button id="theme-btn" class="theme-toggle" title="Alternar Tema">
            <i class="fas fa-sun"></i>
        </button>

        <img src="assets/img/logo.svg" alt="Logo CXPRO" class="logo-img">
        
        <p class="subtitle">Bem-vindo de volta!</p>
        
        <?= $msg ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="E-mail" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn-login">ENTRAR <i class="fas fa-sign-in-alt"></i></button>
        </form>
        
        <div class="links">
            <a href="register.php">Criar Conta</a>
            <a href="forgot_password.php">Esqueci a Senha</a>
        </div>
    </div>

   <?php if (isset($_GET['msg']) && $_GET['msg'] === 'kicked'): ?>
        <div id="kick-modal" class="modal-overlay">
            <div class="modal-content">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ff4444; margin-bottom: 20px;"></i>
                <h2 style="color: #ff4444; margin: 0 0 10px 0;">Sessão Encerrada</h2>
                <p style="color: var(--text-sec); margin-bottom: 25px; line-height: 1.5;">
                    Sua conta foi acessada em outro dispositivo ou navegador.<br>
                    Por segurança, você foi desconectado desta sessão.
                </p>
                <button onclick="closeModal()" style="width:100%; padding:12px; background:#ff4444; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">
                    Entendi, fazer login novamente
                </button>
            </div>
        </div>
        <script>
            function closeModal() {
                document.getElementById('kick-modal').style.display = 'none';
                window.history.replaceState({}, document.title, "login.php");
            }
        </script>
    <?php endif; ?>

    <script>
        const themeBtn = document.getElementById('theme-btn');
        const icon = themeBtn.querySelector('i');
        const html = document.documentElement;

        if (html.classList.contains('light-mode')) {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }

        themeBtn.addEventListener('click', () => {
            html.classList.toggle('light-mode');
            if (html.classList.contains('light-mode')) {
                localStorage.setItem('theme', 'light');
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            } else {
                localStorage.setItem('theme', 'dark');
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        });
    </script>
</body>
</html>