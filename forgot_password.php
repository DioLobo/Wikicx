<?php
require_once 'config/db.php';
require_once 'config/mail.php';

$msg = "";

// DEFINA AQUI A URL BASE DO SEU SITE (Importante para a imagem do e-mail)
// Quando subir para produção, mude para: https://seusite.com/
define('BASE_URL', 'https://www.cxpro.net.br/wikicx/'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // AJUSTE DE HORA: Define o fuso horário para garantir que a expiração bata com o banco de dados
    date_default_timezone_set('America/Sao_Paulo');
    
    $email = trim($_POST['email']);

    // Verifica se usuário existe
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Gera Token Único
        $token = bin2hex(random_bytes(32)); 
        
        // AJUSTE DE HORA: Gera a expiração baseada no fuso horário definido acima
        $expire = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Salva no Banco
        $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_expires = :expire WHERE email = :email");
        $stmt->execute(['token' => $token, 'expire' => $expire, 'email' => $email]);

        // Prepara o Link de redefinição
        $link = BASE_URL . "reset_password.php?token=" . $token;
        // Prepara o Link da Imagem
        $logo_url = "https://static.wixstatic.com/media/477649_87217bbe599943d6874d07c5065e07a3~mv2.png/v1/fill/w_463,h_175,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/477649_87217bbe599943d6874d07c5065e07a3~mv2.png";
        
        // --- CORPO DO E-MAIL COM LOGO ---
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Recuperar Senha</title>
        </head>
        <body style="margin:0; padding:0; background-color:#050505; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#050505; padding: 40px 0;">
                <tr>
                    <td align="center">
                        
                        <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color:#111111; border: 1px solid #333; border-radius: 20px; box-shadow: 0 0 20px rgba(0, 255, 136, 0.1);">
                            
                            <tr>
                                <td align="center" style="padding: 40px 0 20px 0;">
                                    <img src="' . $logo_url . '" alt="CXPRO Logo" width="180" style="display: block; border: 0; max-width: 100%; height: auto;">
                                </td>
                            </tr>

                            <tr>
                                <td align="center" style="padding: 0 40px 40px 40px;">
                                    <h2 style="color: #00ff88; margin-bottom: 20px; font-size: 24px;">Recuperação de Senha</h2>
                                    
                                    <p style="color: #cccccc; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                                        Olá, <strong>' . htmlspecialchars($user['name']) . '</strong>!<br><br>
                                        Recebemos um pedido para redefinir sua senha. Se foi você, clique no botão neon abaixo:
                                    </p>

                                    <a href="' . $link . '" style="display: inline-block; background-color: #00ff88; color: #000000; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 16px; text-transform: uppercase; box-shadow: 0 0 15px rgba(0, 255, 136, 0.4);">
                                        Redefinir Minha Senha
                                    </a>

                                    <p style="color: #666666; font-size: 14px; margin-top: 30px;">
                                        Ou cole este link no navegador:<br>
                                        <a href="' . $link . '" style="color: #00ff88; text-decoration: none; word-break: break-all; font-size: 12px;">' . $link . '</a>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <td align="center" style="background-color: #0a0a0a; border-radius: 0 0 20px 20px; padding: 20px;">
                                    <p style="color: #444444; font-size: 12px; margin: 0;">
                                        &copy; ' . date('Y') . ' CXPRO. Todos os direitos reservados.<br>
                                        Se você não solicitou, ignore este e-mail.
                                    </p>
                                </td>
                            </tr>

                        </table>

                    </td>
                </tr>
            </table>

        </body>
        </html>
        ';
        // --- FIM DO CORPO DO E-MAIL ---

        if (sendMail($email, 'Recuperação de Senha - CXPRO', $body)) {
            $msg = "<p class='success'>Link enviado! Verifique seu e-mail (e a caixa de spam).</p>";
        } else {
            $msg = "<p class='error'>Erro ao enviar e-mail. Verifique as configurações.</p>";
        }
    } else {
        // Mensagem padrão de segurança
        $msg = "<p class='success'>Se o e-mail existir, enviamos um link!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha | CXPRO</title>
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
        }
        .login-card:hover { border-color: var(--accent); }

        h2 { color: var(--accent); margin-bottom: 20px; font-size: 1.5rem; }
        p { color: var(--text-sec); margin-bottom: 30px; font-size: 0.9rem; }
        
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
        
        .links { margin-top: 20px; }
        .links a { color: var(--text-sec); text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .links a:hover { color: var(--accent); text-decoration: underline; }
        
        /* Mensagens de Feedback */
        .success { background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #00ff88; }
        .error { background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ff4444; }

        html.light-mode .success { background: #d1fae5; color: #065f46; border-color: #065f46; }
        html.light-mode .error { background: #fee2e2; color: #991b1b; border-color: #991b1b; }

        @media (max-width: 480px) {
            .login-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Recuperar Senha</h2>
        <p>Digite seu e-mail para receber o link de redefinição.</p>
        
        <?= $msg ?>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Seu e-mail cadastrado" required>
            <button type="submit">ENVIAR LINK <i class="fas fa-paper-plane"></i></button>
        </form>
        
        <div class="links">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Voltar para Login</a>
        </div>
    </div>
</body>
</html>