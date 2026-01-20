<?php
require_once 'config/db.php';

// Segurança
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_GET['id'])) { header('Location: index.php'); exit; }

$lesson_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 1. Busca aula
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = :id");
$stmt->execute(['id' => $lesson_id]);
$aula = $stmt->fetch();

if (!$aula) die("Aula não encontrada.");

// 2. Verifica se já fez
$stmt = $pdo->prepare("SELECT id FROM user_progress WHERE user_id = :uid AND lesson_id = :lid");
$stmt->execute(['uid' => $user_id, 'lid' => $lesson_id]);
$ja_fez = $stmt->rowCount() > 0;

// 3. Busca dados do user (para o header/sidebar se precisar)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$userData = $stmt->fetch();

// --- LÓGICA DE VÍDEO (LINK OU ARQUIVO LOCAL) ---
$videoRawUrl = $aula['video_url'];

// Verifica se é arquivo local (tem 'uploads/' no nome ou termina em .mp4)
$isLocalVideo = (strpos($videoRawUrl, 'uploads/') !== false) || (substr($videoRawUrl, -4) === '.mp4');

// Função para corrigir link do YouTube (só usada se NÃO for local)
function getYoutubeEmbedUrl($url) {
    $pattern = '%^# Match any youtube URL
        (?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=))([\w-]{10,12})$%x';
    $result = preg_match($pattern, $url, $matches);
    if ($result) {
        return "https://www.youtube.com/embed/" . $matches[1] . "?enablejsapi=1&rel=0&showinfo=0";
    }
    return $url;
}

// Define a URL final para exibição
$videoUrl = $isLocalVideo ? $videoRawUrl : getYoutubeEmbedUrl($videoRawUrl);
$duracao_texto = isset($aula['duration']) ? htmlspecialchars($aula['duration']) : '45 min';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($aula['title']) ?> | CXPRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') { document.documentElement.classList.add('light-mode'); }
    </script>

    <style>
        /* CSS GERAL MANTIDO */
        :root { --bg-color: #050505; --card-bg: #111; --text-main: #ffffff; --text-sec: #a0a0a0; --accent: #00ff88; --border: rgba(255, 255, 255, 0.1); --item-hover: rgba(255, 255, 255, 0.05); }
        html.light-mode body { --bg-color: #f4f6f8; --card-bg: #ffffff; --text-main: #1a1a1a; --text-sec: #555555; --accent: #2563eb; --border: rgba(0, 0, 0, 0.1); --item-hover: #f0f0f0; }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; transition: 0.3s; }
        body { background-color: var(--bg-color); color: var(--text-main); padding: 40px; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        /* HEADER DA AULA */
        .lesson-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 20px; flex-wrap: wrap; gap: 20px; }
        .lesson-info h1 { color: var(--accent); margin: 0 0 10px 0; font-size: 2rem; }
        .meta { color: var(--text-sec); font-size: 0.9rem; }
        
        /* ÁREA DE AÇÕES (BOTÕES) */
        .lesson-actions { display: flex; gap: 15px; align-items: center; }

        /* Botão Concluir */
        .btn-concluir { background: var(--accent); color: #000; padding: 12px 25px; border: none; border-radius: 50px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.3s; font-size: 0.9rem; }
        .btn-concluir:hover { transform: scale(1.05); box-shadow: 0 0 20px var(--accent); }
        .btn-concluir.disabled { background: var(--item-hover); color: var(--text-sec); cursor: default; box-shadow: none; transform: none; border: 1px solid var(--border); }

        /* Botão PDF (Agora no topo) */
        .btn-pdf-top { 
            padding: 10px 20px; 
            background: transparent; 
            border: 1px solid var(--border); 
            color: var(--text-sec); 
            border-radius: 50px; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            font-size: 0.9rem; 
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-pdf-top:hover { border-color: var(--accent); color: var(--accent); background: var(--item-hover); }
        .btn-pdf-top i { font-size: 1rem; }

        /* Vídeo Box (Adapta para iframe ou video tag) */
        .video-box { position: relative; width: 100%; padding-bottom: 56.25%; height: 0; background: #000; border: 1px solid var(--border); border-radius: 15px; overflow: hidden; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .video-box iframe, .video-box video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        
        /* Botão Voltar */
        .back-link { display: inline-flex; align-items: center; gap: 10px; padding: 10px 25px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 50px; color: var(--text-sec); text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .back-link:hover { border-color: var(--accent); color: var(--accent); background: var(--item-hover); box-shadow: 0 0 15px var(--accent); transform: translateX(-5px); }

        /* Descrição */
        .content { background: var(--card-bg); padding: 30px; border-radius: 15px; border: 1px solid var(--border); }
        .content h3 { margin-top: 0; color: var(--text-main); }
        .content p { color: var(--text-sec); line-height: 1.6; }

        /* --- NOTIFICAÇÃO TOAST (CANTO INFERIOR ESQUERDO) --- */
        #toast {
            visibility: hidden;
            min-width: 280px;
            background-color: var(--card-bg);
            color: var(--text-main);
            text-align: left;
            border-radius: 10px;
            padding: 20px;
            position: fixed;
            z-index: 9999;
            left: 30px;
            bottom: 30px;
            border-left: 5px solid var(--accent);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 15px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
        }
        
        #toast i { font-size: 1.5rem; color: var(--accent); }
        #toast strong { display: block; color: var(--accent); font-size: 1rem; margin-bottom: 2px; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>

    <div class="lesson-header">
        <div class="lesson-info">
            <h1><?= htmlspecialchars($aula['title']) ?></h1>
            <div class="meta">
                <span><i class="fas fa-folder"></i> <?= htmlspecialchars($aula['module']) ?></span> • 
                <span><i class="fas fa-clock"></i> <?= $duracao_texto ?></span>
            </div>
        </div>
        
        <div class="lesson-actions">
            <?php if(isset($aula['pdf_file']) && !empty($aula['pdf_file'])): ?>
                <a href="uploads/materials/<?= $aula['pdf_file'] ?>" target="_blank" class="btn-pdf-top" title="Baixar Material">
                    <i class="fas fa-file-pdf"></i> Material
                </a>
            <?php endif; ?>

            <button type="button" id="btn-concluir" onclick="concluirAulaAjax()" class="btn-concluir <?= $ja_fez ? 'disabled' : '' ?>" <?= $ja_fez ? 'disabled' : '' ?>>
                <?php if ($ja_fez): ?>
                    <i class="fas fa-check-circle"></i> Aula Concluída
                <?php else: ?>
                    <i class="fas fa-check"></i> Concluir Aula (+<?= $aula['xp_reward'] ?? 30 ?> XP)
                <?php endif; ?>
            </button>
        </div>
    </div>

    <div class="video-box">
        <?php if($isLocalVideo): ?>
            <video id="localVideo" controls controlsList="nodownload" style="width:100%; height:100%;">
                <source src="<?= $videoUrl ?>" type="video/mp4">
                Seu navegador não suporta este vídeo.
            </video>
        <?php elseif($videoUrl): ?>
            <iframe id="video-frame" src="<?= $videoUrl ?>" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        <?php else: ?>
            <div style="display:flex; align-items:center; justify-content:center; width:100%; height:100%; color:var(--text-sec);">
                <i class="fas fa-video-slash" style="margin-right:10px;"></i> Vídeo indisponível
            </div>
        <?php endif; ?>
    </div>

    <div class="content">
        <h3>Sobre esta aula</h3>
        <p><?= nl2br(htmlspecialchars($aula['description'])) ?></p>
    </div>
</div>

<div id="toast">
    <i class="fas fa-medal"></i>
    <div>
        <strong>Parabéns!</strong>
        <span id="toast-msg">Aula concluída. XP recebido!</span>
    </div>
</div>

<script src="https://www.youtube.com/iframe_api"></script>
<script>
    let player;
    let jaFez = <?= json_encode((bool)$ja_fez) ?>;
    const lessonId = <?= $lesson_id ?>;

    // --- NOVO: DETECÇÃO PARA VÍDEO LOCAL (MP4) ---
    document.addEventListener("DOMContentLoaded", function() {
        const localVid = document.getElementById('localVideo');
        if (localVid) {
            // O evento 'ended' é disparado nativamente pelo navegador quando o vídeo MP4 termina
            localVid.addEventListener('ended', function() {
                if (!jaFez) {
                    concluirAulaAjax();
                }
            });
        }
    });

    // --- DETECÇÃO PARA YOUTUBE (JÁ EXISTENTE) ---
    function onYouTubeIframeAPIReady() {
        if(document.getElementById('video-frame')) {
            player = new YT.Player('video-frame', {
                events: { 'onStateChange': onPlayerStateChange }
            });
        }
    }

    function onPlayerStateChange(event) {
        // Estado 0 = Vídeo Terminou
        if (event.data === 0 && !jaFez) {
            concluirAulaAjax();
        }
    }

    // --- FUNÇÃO PARA SALVAR AULA E DAR XP ---
    function concluirAulaAjax() {
        if(jaFez) return;

        const btn = document.getElementById('btn-concluir');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        btn.classList.add('disabled'); 
        
        fetch('ajax_complete_lesson.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'lesson_id=' + lessonId
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) { // Mantive 'data.success' conforme seu código original
                jaFez = true;
                btn.setAttribute('disabled', 'disabled');
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Aula Concluída';
                
                if(data.new_completion) {
                    showToast("Você ganhou +30 XP!");
                }
            } else {
                console.error("Erro backend:", data);
                btn.innerHTML = 'Erro ao salvar';
                btn.classList.remove('disabled');
            }
        })
        .catch(err => {
            console.error("Erro fetch:", err);
            btn.innerHTML = 'Erro de conexão';
            btn.classList.remove('disabled');
        });
    }

    // FUNÇÃO PARA MOSTRAR O TOAST
    function showToast(message) {
        const x = document.getElementById("toast");
        document.getElementById("toast-msg").innerText = message;
        x.className = "show";
        setTimeout(function(){ x.className = x.className.replace("show", ""); }, 4000);
    }
</script>
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
</body>
</html>