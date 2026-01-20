<?php
require_once 'config/db.php';

// Segurança da Sessão
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$stmt = $pdo->prepare("SELECT role, name FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') { die("Acesso Negado"); }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin | CXPRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') { document.documentElement.classList.add('light-mode'); }
    </script>
    <style>
        :root { --bg-color: #050505; --card-bg: #111; --text-main: #ffffff; --text-sec: #a0a0a0; --accent: #00ff88; --border: rgba(255, 255, 255, 0.1); }
        html.light-mode body { --bg-color: #f4f6f8; --card-bg: #ffffff; --text-main: #1a1a1a; --text-sec: #555555; --accent: #2563eb; --border: rgba(0, 0, 0, 0.1); }
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: 0.3s; }
        body { background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 40px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        
        .dashboard-container { max-width: 1000px; width: 100%; text-align: center; }
        
        /* AJUSTEI AQUI: Mudei para 'repeat(auto-fit...)' para aceitar 3 colunas automaticamente */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 40px; }
        
        .card { background: var(--card-bg); padding: 40px; border-radius: 20px; border: 1px solid var(--border); text-decoration: none; color: var(--text-main); transition: 0.3s; display: flex; flex-direction: column; align-items: center; }
        .card:hover { transform: translateY(-10px); border-color: var(--accent); box-shadow: 0 10px 30px rgba(0, 255, 136, 0.2); }
        .card i { font-size: 3rem; color: var(--accent); margin-bottom: 20px; }
        .card h2 { margin: 0; font-size: 1.5rem; }
        .card p { color: var(--text-sec); margin-top: 10px; }
        
        /* BOTÃO VOLTAR (Posição Fixa + Estilo Novo) */
        .back-link {
            /* 1. Posicionamento (Mantido como você queria) */
            position: absolute;
            top: 30px;
            left: 30px;
            z-index: 100; /* Garante que fique por cima de tudo */

            /* 2. Estilo Visual (Copiado do btn-back) */
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 25px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 50px;
            color: var(--text-sec);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Efeito Hover (Brilho Neon) */
        .back-link:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--item-hover);
            box-shadow: 0 0 15px var(--accent-glow);
            transform: translateX(-5px);
        }

        /* --- ESTILO DO BOTÃO VOLTAR (Padrão Unificado - Mantido caso use em outro lugar) --- */
        .btn-back {
            display: inline-flex;       /* Alinha ícone e texto */
            align-items: center;        /* Centraliza verticalmente */
            gap: 10px;                  /* Espaço entre a seta e o texto */
            padding: 10px 25px;         /* Tamanho do botão */
            background: var(--card-bg); /* Fundo igual aos cards (garante leitura) */
            border: 1px solid var(--border);
            border-radius: 50px;        /* Borda redonda (Pílula) */
            color: var(--text-sec);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* Sombra leve */
        }

        /* Efeito ao passar o mouse (Respeita o Tema) */
        .btn-back:hover {
            border-color: var(--accent);       /* Borda Verde ou Azul */
            color: var(--accent);              /* Texto Verde ou Azul */
            background: var(--item-hover);     /* Fundo levemente mais claro */
            box-shadow: 0 0 15px var(--accent-glow); /* O brilho Neon! */
            transform: translateX(-5px);       /* Pequeno movimento para a esquerda */
        }
    </style>
</head>
<body>

    <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao Site</a>

    <div class="dashboard-container">
        <h1>Painel Administrativo</h1>
        <p style="color:var(--text-sec);">Bem-vindo, <strong><?= htmlspecialchars($user['name']) ?></strong>. O que deseja gerenciar hoje?</p>

        <div class="grid">
            <a href="admin_aulas.php" class="card">
                <i class="fas fa-video"></i>
                <h2>Gerenciar Aulas</h2>
                <p>Adicionar, editar ou excluir videoaulas.</p>
            </a>

            <a href="admin_modulos.php" class="card">
                <i class="fas fa-layer-group"></i>
                <h2>Gerenciar Módulos</h2>
                <p>Criar novas matérias e categorias.</p>
            </a>

            <a href="admin_quiz.php" class="card">
                <i class="fas fa-question-circle"></i>
                <h2>Gerenciar Quiz</h2>
                <p>Criar perguntas e respostas do banco.</p>
            </a>

            <a href="admin_usuarios.php" class="card">
                <i class="fas fa-users-cog"></i>
                <h2>Gerenciar Usuários</h2>
                <p>Listar alunos, editar dados e acesso.</p>
            </a>
        </div>
    </div>
<script>
 // Verifica a validade da sessão a cada 5 segundos sem precisar de F5
    setInterval(function() {
        fetch('api/check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'kicked') {
                    // Se o token mudou, redireciona para o login na hora
                    window.location.href = 'login.php?msg=kicked';
                }
            });
    }, 5000);
</script>
</body>
</html>