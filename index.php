<?php
require_once 'config/db.php';

// 1. Segurança e Sessão
require_once 'config/session_check.php';


$user_id = $_SESSION['user_id'];

// 2. Dados do Usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$userData = $stmt->fetch();
$roleLabel = ($userData['role'] === 'admin') ? 'Administrador' : 'Aluno Premium';
$isAdmin = ($userData['role'] === 'admin');
$xp_percent = ($userData['current_xp'] % 100);

// 3. Progresso
$stmt = $pdo->prepare("SELECT lesson_id FROM user_progress WHERE user_id = :uid");
$stmt->execute(['uid' => $user_id]);
$completed_lessons = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 4. Todas as Aulas
$stmt = $pdo->query("SELECT * FROM lessons ORDER BY module, title");
$all_lessons = $stmt->fetchAll();

// 5. Busca Módulos do Banco
$stmt_mod = $pdo->query("SELECT name FROM modules ORDER BY name ASC");
$db_modules_list = $stmt_mod->fetchAll(PDO::FETCH_COLUMN);

// 6. Organiza Aulas nos Módulos
$modulos = [];
foreach ($db_modules_list as $mName) { 
    $modulos[$mName] = []; 
}
foreach ($all_lessons as $aula) {
    if (array_key_exists($aula['module'], $modulos)) {
        $modulos[$aula['module']][] = $aula;
    } else {
        $modulos[$aula['module']][] = $aula;
    }
}

// 7. Verifica quais módulos têm QUIZ cadastrado
$stmt_quiz_check = $pdo->query("SELECT DISTINCT module FROM quiz_questions");
$modules_with_quiz = $stmt_quiz_check->fetchAll(PDO::FETCH_COLUMN);

// 8. Próxima Aula
$proxima_aula = null;
foreach ($all_lessons as $aula) {
    if (!in_array($aula['id'], $completed_lessons)) {
        $proxima_aula = $aula;
        break;
    }
}

// 9. Cálculos Finais
$total_lessons = count($all_lessons);
$completed_count = count($completed_lessons);
$global_percentage = $total_lessons > 0 ? round(($completed_count / $total_lessons) * 100) : 0;

// Ícones
$icons = [
    'Linguagens' => 'fa-language', 
    'Humanas' => 'fa-landmark', 
    'Exatas' => 'fa-flask', 
    'Redação' => 'fa-pen-nib',
    'Biologia' => 'fa-dna',
    'Inglês' => 'fa-comments'
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard | CXPRO</title>
    <!-- Favicons -->
<link rel="icon" type="image/png" sizes="32x32" href="/wikicx/assets/img/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/wikicx/assets/img/favicon-16x16.png">
<link rel="shortcut icon" href="/gestaopro/assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="assets/css/style.css">

    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') { document.documentElement.classList.add('light-mode'); }
    </script>

</head>
<body>

    <div id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <aside id="sidebar">
        <button class="close-sidebar-btn" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>

        <div class="logo" onclick="switchView('dashboard', null)">
            <img src="assets/img/logo.svg" alt="CXPRO" class="sidebar-logo">
        </div>

        <div class="global-progress-container">
            <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--text-sec); margin-bottom:8px;">
                <span>Progresso Total</span>
                <span><?= $global_percentage ?>%</span>
            </div>
            <div style="width:100%; height:8px; background:var(--item-hover); border-radius:4px; overflow:hidden;">
                <div class="progress-fill-global" style="height:100%; width:<?= $global_percentage ?>%; background:var(--gradient); border-radius:4px;"></div>
            </div>
        </div>

        <div style="font-size:0.75rem; color:var(--text-sec); margin-bottom:10px; font-weight:bold;">Módulos de Estudo</div>

        <?php foreach ($modulos as $nome_modulo => $aulas): ?>
            <?php 
                $icone = $icons[$nome_modulo] ?? 'fa-folder';
                $total_mod = count($aulas);
                $feitas_mod = 0;
                foreach($aulas as $a) if(in_array($a['id'], $completed_lessons)) $feitas_mod++;
                $perc_mod = $total_mod > 0 ? round(($feitas_mod / $total_mod) * 100) : 0;
            ?>
            <div class="folder-item">
                <button class="folder-btn">
                    <i class="fas <?= $icone ?> folder-icon" style="margin-right:10px; width:20px; text-align:center;"></i> 
                    <span style="flex:1;"><?= htmlspecialchars($nome_modulo) ?></span>
                    <div style="margin-left:auto; display:flex; gap:10px; font-size:0.8rem; color:var(--text-sec);">
                        <span><?= $perc_mod ?>%</span>
                        <i class="fas fa-chevron-down chevron"></i>
                    </div>
                    <div class="module-progress-line" style="width: <?= $perc_mod ?>%"></div>
                </button>
                <div class="submenu">
                    <?php if (empty($aulas)): ?>
                        <div style="padding: 10px 15px; color: var(--text-sec); font-size: 0.8rem; font-style: italic;">
                            Nenhuma aula cadastrada.
                        </div>
                    <?php else: ?>
                        <?php foreach ($aulas as $aula): ?>
                            <?php $check = in_array($aula['id'], $completed_lessons) ? '<i class="fas fa-check-circle completed-icon"></i>' : ''; ?>
                            <a href="aula.php?id=<?= $aula['id'] ?>">
                                <?= htmlspecialchars($aula['title']) ?> <?= $check ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if(in_array($nome_modulo, $modules_with_quiz)): ?>
                        <a onclick="switchView('quiz', this, '<?= $nome_modulo ?>')" class="quiz-module-link">
                            <span><i class="fas fa-bolt"></i> Quiz: <?= htmlspecialchars($nome_modulo) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="download-zone">
            <div style="font-size:0.75rem; color:var(--text-sec); margin-bottom:10px; font-weight:bold; margin-top:30px;">Treinamento</div>
            <div class="folder-item">
                <button class="folder-btn active-folder" style="color: var(--accent);">
                    <i class="fas fa-brain folder-icon" style="margin-right:10px; width:20px; text-align:center;"></i> <span style="flex:1;">Simulados</span> <i class="fas fa-chevron-down chevron" style="transform: rotate(180deg);"></i>
                </button>
                <div class="submenu" style="max-height: 200px;">
                    <a onclick="switchView('quiz', this, null)" id="link-quiz">
                        <span><i class="fas fa-bolt"></i> Quiz Rápido (Geral)</span>
                    </a>
                    <a onclick="switchView('downloads', this)">
                        <span><i class="fas fa-file-pdf"></i> Banco de Provas</span>
                    </a>
                </div>
            </div>
        </div>

        <div style="font-size:0.75rem; color:var(--text-sec); margin-bottom:10px; font-weight:bold; margin-top:30px;">Comunidade</div>
        <div class="folder-item">
            <a href="ranking.php" class="folder-btn" style="text-decoration:none; color:var(--text-main); font-size: 13.33px;">
                <i class="fas fa-trophy folder-icon" style="margin-right:10px; width:20px; text-align:center; color:var(--accent);"></i> 
                <span style="flex:1; color:var(--accent); font-weight:bold;">Ranking Global</span>
            </a>
        </div>
    </aside>

    <main>
        <header>
            <div class="header-left">
                <div class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
                <div><h2 id="page-title" style="margin:0; font-size:1.5rem;">Dashboard</h2><p id="page-subtitle" style="color:var(--text-sec); font-size:0.9rem; margin:0;">Visão geral do seu progresso</p></div>
            </div>
            <div class="header-right">
                <div class="xp-badge">
                    <span class="xp-text">Nível <?= $userData['level'] ?></span>
                    <div class="xp-bar-container"><div class="xp-bar-fill" style="width: <?= $xp_percent ?>%"></div></div>
                </div>
                
                <?php if($isAdmin): ?>
                   <a href="admin.php" class="header-btn" data-tooltip="Painel Admin"><i class="fas fa-cogs"></i></a>
                <?php endif; ?>

                <?php if($global_percentage >= 100): ?>
                    <a href="certificado.php" target="_blank" class="btn-certificado-header">
                        <i class="fas fa-scroll"></i> <span>Baixar Certificado</span>
                    </a>
                    <style>
                        /* Estilo específico para o botão neon */
                        .btn-certificado-header {
                            display: flex; align-items: center; gap: 8px;
                            background: var(--accent); color: #000;
                            padding: 8px 15px; border-radius: 50px;
                            text-decoration: none; font-weight: bold;
                            font-size: 0.9rem; box-shadow: 0 0 15px var(--accent-glow);
                            transition: 0.3s; margin-right: 10px; white-space: nowrap;
                        }
                        .btn-certificado-header:hover { transform: translateY(-2px); filter: brightness(1.1); }
                    </style>
                <?php endif; ?>

                <button class="header-btn theme-switch" onclick="toggleTheme()" data-tooltip="Alternar Tema"><i class="fas fa-sun"></i></button>
                
                <a href="profile.php" class="user-profile-btn" data-tooltip="Editar Perfil">
                    <?php if(!empty($userData['avatar']) && file_exists('uploads/'.$userData['avatar'])): ?>
                        <img src="uploads/<?= $userData['avatar'] ?>" class="user-img">
                    <?php else: ?>
                        <div class="user-img"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($userData['name']) ?></span>
                        <span class="user-role"><?= htmlspecialchars($roleLabel) ?></span>
                    </div>
                </a>
                
                <button class="header-btn logout-btn" onclick="window.location.href='logout.php'" data-tooltip="Sair"><i class="fas fa-sign-out-alt"></i></button>
            </div>
        </header>

        <div id="view-dashboard" class="view-section active">
            <div class="dashboard-grid">
                <div class="stat-card"><h3><?= $total_lessons ?></h3><p>Módulos</p><i class="fas fa-layer-group stat-icon"></i></div>
                <div class="stat-card"><h3><?= $completed_count ?></h3><p>Aulas Assistidas</p><i class="fas fa-play-circle stat-icon"></i></div>
                <div class="stat-card"><h3 style="color: var(--accent);">850</h3><p>Pontuação Meta</p><i class="fas fa-chart-line stat-icon"></i></div>
            </div>
            
            <div style="padding: 40px; border: 1px dashed var(--border); text-align: center; border-radius: 10px;">
                <?php if ($proxima_aula): ?>
                    <h3 style="color:var(--accent)">Continue estudando: <?= htmlspecialchars($proxima_aula['module']) ?></h3>
                    <p style="color: var(--text-sec); margin-bottom: 20px;">
                        Próxima aula: <strong style="color:var(--text-main)"><?= htmlspecialchars($proxima_aula['title']) ?></strong>
                    </p>
                    <button class="action-btn" style="float:none;" onclick="window.location.href='aula.php?id=<?= $proxima_aula['id'] ?>'">
                        Continuar <i class="fas fa-arrow-right"></i>
                    </button>
                <?php else: ?>
                    <h3 style="color:var(--accent)">Parabéns! 🎉</h3>
                    <p style="color: var(--text-sec); margin-bottom: 20px;">Você concluiu todas as aulas disponíveis.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="view-quiz" class="view-section">
            <div id="quiz-playable" class="quiz-container">
                <div style="display:flex; justify-content:space-between; margin-bottom: 10px; font-weight:bold;">
                    <span style="color: var(--text-main);">Questão <span id="q-number">1</span> <span style="color:var(--text-sec); font-weight:normal;">/ 5</span></span>
                    <span id="quiz-module-tag" style="color: var(--accent); background: rgba(0,255,136,0.1); padding: 2px 10px; border-radius: 10px; font-size: 0.8rem;">Geral</span>
                </div>
                <div class="progress-track"><div class="progress-fill" id="quiz-progress" style="width: 0%;"></div></div>
                <p id="q-text" style="font-size: 1.2rem; margin-bottom: 30px;">Carregando...</p>
                <div id="q-options"></div>
                <div style="overflow:hidden; margin-top:20px;">
                    <button class="action-btn" id="btn-next-q" onclick="nextQuestion()" disabled>Próxima <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            <div id="quiz-gabarito" class="quiz-container" style="display: none;"></div>
        </div>

        <div id="view-downloads" class="view-section">
            <div class="files-grid">
                <div class="file-card">
                    <i class="fas fa-file-pdf file-icon"></i>
                    <b>PROVA 1</b>
                    <small>Contrato</small>
                    <a href="downloads/prova1.pdf" download="prova1.pdf" class="download-btn">Baixar PDF</a>
                </div>
                <div class="file-card">
                    <i class="fas fa-file-pdf file-icon"></i>
                    <b>PROVA 2</b>
                    <small>Financeiro</small>
                    <a href="downloads/prova1.pdf" download="prova2.pdf" class="download-btn">Baixar PDF</a>
                </div>
                <div class="file-card">
                    <i class="fas fa-file-pdf file-icon"></i>
                    <b>PROVA 3</b>
                    <small>Atendimento</small>
                    <a href="downloads/prova3.pdf" download="prova3.pdf" class="download-btn">Baixar PDF</a>
                </div>
                <div class="file-card">
                    <i class="fas fa-file-pdf file-icon"></i>
                    <b>PROVA 4</b>
                    <small>Ordem de serviço</small>
                    <a href="downloads/prova4.pdf" download="prova4.pdf" class="download-btn">Baixar PDF</a>
                </div>
            </div>
        </div>
    </main>

    <div id="quiz-result-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h2 style="color: #fff;">Quiz Concluído!</h2>
            <p>Você acertou:</p>
            <div class="result-score" id="final-score">0/5</div>
            <p class="result-text" id="final-message">Salvando resultado...</p>
            <button class="action-btn" style="float:none; margin: 0 auto;" onclick="finishAndSaveQuiz()">Ver Gabarito</button>
        </div>
    </div>
<  <script src="assets/js/main.js"></script>
    <script>
 // Verifica a validade da sessão a cada 5 segundos sem precisar de F5
setInterval(function() {
    // Adicionamos um timestamp (?t=...) para garantir que o navegador peça dados novos
    fetch('api/check_session.php?t=' + Date.now())
        .then(response => response.json())
        .then(data => {
            if (data.status === 'kicked') {
                window.location.href = 'login.php?msg=kicked';
            }
        });
}, 5000);

    </script>
</body>
</html>