<?php
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // Validações Básicas
    if (empty($name) || empty($email) || empty($password)) {
        $msg = "<p class='error'>Preencha todos os campos!</p>";
    } elseif ($password !== $confirm_pass) {
        $msg = "<p class='error'>As senhas não coincidem!</p>";
    } else {
        // Verifica se e-mail já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            $msg = "<p class='error'>Este e-mail já está cadastrado!</p>";
        } else {
            // Criptografa a senha e Salva
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, password, level, current_xp, status) VALUES (:name, :email, :pass, 1, 0, 'pendente')";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute(['name' => $name, 'email' => $email, 'pass' => $hash])) {
                // Sucesso: Redireciona para Login
                header('Location: login.php?registered=1');
                exit;
            } else {
                $msg = "<p class='error'>Erro ao cadastrar. Tente novamente.</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta | CXPRO</title>
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
            min-height: 100vh; 
            margin: 0; 
            padding: 20px; 
        }
        
        /* LARGURA RESPONSIVA */
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

        h2 { color: var(--accent); margin-bottom: 30px; font-size: 1.8rem; }
        
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--accent); }
        
        input {
            width: 100%; padding: 12px 15px 12px 45px; background: var(--input-bg);
            border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); outline: none; transition: 0.3s;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 0 10px var(--accent); }
        
        button {
            width: 100%; padding: 12px; background: var(--accent); color: #000; font-weight: bold;
            border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; transition: 0.3s;
        }
        button:hover { transform: scale(1.02); box-shadow: 0 0 15px var(--accent); }
        
        .links { margin-top: 20px; font-size: 0.9rem; }
        .links a { color: var(--text-sec); text-decoration: none; transition: 0.3s; }
        .links a:hover { color: var(--accent); text-decoration: underline; }
        
        .error { background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ff4444; }
        html.light-mode .error { background: #fee2e2; color: #991b1b; border-color: #991b1b; }

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
            h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="assets/img/logo.svg" alt="Logo CXPRO" class="logo-img">

       <p class="subtitle">Crie aqui seu cadastro!</p>
        
        <?php if(!empty($msg)) echo $msg; ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Nome Completo" required>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Seu Melhor E-mail" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Senha Secreta" required>
            </div>
            <div class="input-group">
                <i class="fas fa-check-circle"></i>
                <input type="password" name="confirm_password" placeholder="Confirme a Senha" required>
            </div>
            <button type="submit">CRIAR CONTA</button>
        </form>
        
        <div class="links">
            <p>Já tem conta? <a href="login.php">Fazer Login</a></p>
        </div>
    </div>
</body>
</html>