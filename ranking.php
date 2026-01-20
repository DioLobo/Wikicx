<?php
require_once 'config/db.php';

// Segurança
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// BUSCAR RANKING (TOP 20)
$stmt = $pdo->query("SELECT id, name, avatar, level, current_xp FROM users ORDER BY current_xp DESC LIMIT 20");
$ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Posição do usuário logado (para destacar na lista)
$my_rank = 0;
foreach($ranking as $key => $r) {
    if($r['id'] == $user_id) {
        $my_rank = $key + 1;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ranking Global | CXPRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') { document.documentElement.classList.add('light-mode'); }
    </script>

    <style>
        /* --- DESIGN SYSTEM (Igual ao Index/Profile) --- */
        :root { --bg-color: #050505; --card-bg: #111; --text-main: #ffffff; --text-sec: #a0a0a0; --accent: #00ff88; --accent-glow: rgba(0, 255, 136, 0.4); --border: rgba(255, 255, 255, 0.1); --item-hover: rgba(255, 255, 255, 0.05); }
        html.light-mode body { --bg-color: #f4f6f8; --card-bg: #ffffff; --text-main: #1a1a1a; --text-sec: #555555; --accent: #2563eb; --accent-glow: rgba(37, 99, 235, 0.4); --border: rgba(0, 0, 0, 0.1); --item-hover: #e0e0e0; }
        
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: 0.3s; }
        body { background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 40px; display: flex; justify-content: center; }
        
        .container { max-width: 800px; width: 100%; }

        /* --- BOTÃO VOLTAR (Padrão Neon) --- */
        .btn-back {
            display: inline-flex; align-items: center; gap: 10px; padding: 10px 25px;
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 50px;
            color: var(--text-sec); text-decoration: none; font-weight: 600; font-size: 0.9rem;
            transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px;
        }
        .btn-back:hover { border-color: var(--accent); color: var(--accent); background: var(--item-hover); box-shadow: 0 0 15px var(--accent-glow); transform: translateX(-5px); }

        /* --- HEADER DO RANKING --- */
        .rank-header { text-align: center; margin-bottom: 40px; }
        .rank-header h1 { font-size: 2.5rem; margin: 0 0 10px 0; color: var(--text-main); }
        .rank-header i { color: #FFD700; text-shadow: 0 0 20px rgba(255, 215, 0, 0.6); margin-right: 10px; }
        .rank-header p { color: var(--text-sec); margin: 0; }

        /* --- LISTA DE RANKING --- */
        .rank-list { list-style: none; padding: 0; margin: 0; }
        
        .rank-item {
            display: flex; align-items: center; justify-content: space-between;
            background: var(--card-bg); border: 1px solid var(--border);
            padding: 15px 25px; margin-bottom: 15px; border-radius: 15px;
            position: relative; overflow: hidden;
        }
        
        .rank-item:hover { transform: scale(1.02); border-color: var(--accent); }

        /* DESTAQUE PARA O USUÁRIO LOGADO */
        .rank-item.is-me { border: 2px solid var(--accent); box-shadow: 0 0 20px var(--accent-glow); background: rgba(0, 255, 136, 0.05); }
        html.light-mode .rank-item.is-me { background: rgba(37, 99, 235, 0.05); }

        /* --- Posições e Medalhas --- */
        .rank-pos { font-size: 1.5rem; font-weight: 800; width: 40px; color: var(--text-sec); text-align: center; }
        
        /* Ouro */
        .rank-item:nth-child(1) .rank-pos { color: #FFD700; text-shadow: 0 0 10px #FFD700; }
        .rank-item:nth-child(1) { border-color: #FFD700; }
        
        /* Prata */
        .rank-item:nth-child(2) .rank-pos { color: #C0C0C0; text-shadow: 0 0 10px #C0C0C0; }
        .rank-item:nth-child(2) { border-color: #C0C0C0; }
        
        /* Bronze */
        .rank-item:nth-child(3) .rank-pos { color: #CD7F32; text-shadow: 0 0 10px #CD7F32; }
        .rank-item:nth-child(3) { border-color: #CD7F32; }

        /* --- Info do Usuário --- */
        .user-info { flex: 1; display: flex; align-items: center; gap: 15px; margin-left: 20px; }
        
        .user-avatar { 
            width: 50px; height: 50px; border-radius: 50%; object-fit: cover; 
            border: 2px solid var(--border); background: #222;
            display: flex; align-items: center; justify-content: center; color: var(--text-sec);
        }
        
        .user-details h3 { margin: 0; font-size: 1rem; color: var(--text-main); }
        .user-details span { font-size: 0.8rem; color: var(--text-sec); background: var(--item-hover); padding: 2px 8px; border-radius: 4px; }

        /* --- XP --- */
        .xp-display { text-align: right; }
        .xp-val { font-size: 1.2rem; font-weight: bold; color: var(--accent); display: block; }
        .xp-label { font-size: 0.7rem; color: var(--text-sec); text-transform: uppercase; letter-spacing: 1px; }

        @media(max-width: 600px) {
            body { padding: 20px; }
            .rank-item { padding: 10px 15px; }
            .user-avatar { width: 40px; height: 40px; }
            .rank-pos { font-size: 1.2rem; width: 30px; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>

    <div class="rank-header">
        <h1><i class="fas fa-trophy"></i> Ranking Global</h1>
        <p>Os melhores estudantes da plataforma</p>
    </div>

    <ul class="rank-list">
        <?php foreach($ranking as $index => $r): ?>
            <?php 
                $rank = $index + 1;
                $isMe = ($r['id'] == $user_id) ? 'is-me' : '';
                
                // Lógica do Avatar
                $avatarDisplay = "";
                if (!empty($r['avatar']) && file_exists('uploads/'.$r['avatar'])) {
                    $avatarDisplay = "<img src='uploads/{$r['avatar']}' class='user-avatar'>";
                } else {
                    $avatarDisplay = "<div class='user-avatar'><i class='fas fa-user'></i></div>";
                }
            ?>
            <li class="rank-item <?= $isMe ?>">
                <div class="rank-pos">
                    <?php if($rank == 1): ?><i class="fas fa-crown"></i>
                    <?php elseif($rank == 2): ?>2º
                    <?php elseif($rank == 3): ?>3º
                    <?php else: ?><?= $rank ?>º
                    <?php endif; ?>
                </div>
                
                <div class="user-info">
                    <?= $avatarDisplay ?>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($r['name']) ?></h3>
                        <span>Nível <?= $r['level'] ?></span>
                    </div>
                </div>

                <div class="xp-display">
                    <span class="xp-val"><?= $r['current_xp'] ?></span>
                    <span class="xp-label">XP Total</span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

</body>
</html>