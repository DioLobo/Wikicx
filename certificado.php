<?php
require_once 'config/db.php';

// 1. Configurar Fuso Horário (Brasil)
date_default_timezone_set('America/Sao_Paulo');

// 2. Segurança e Dados
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

// 3. Validação de Progresso
$stmt = $pdo->prepare("SELECT lesson_id FROM user_progress WHERE user_id = :uid");
$stmt->execute(['uid' => $user_id]);
$completed = $stmt->rowCount();
$stmt = $pdo->query("SELECT COUNT(*) FROM lessons");
$total = $stmt->fetchColumn();

if ($total > 0 && $completed < $total) {
    echo "<script>alert('Você precisa concluir 100% do curso.'); window.close();</script>";
    exit;
}

$data_emissao = date('d/m/Y');
$hash_validacao = strtoupper(substr(md5($user_id . 'cxpro_cert_' . $data_emissao), 0, 16));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Certificado - <?= htmlspecialchars($user['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Lora:ital,wght@0,400;0,700;1,400&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page { size: landscape; margin: 0; }
        
        body { 
            margin: 0; padding: 0; 
            background: #333; 
            font-family: 'Lora', serif; 
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
            display: flex; justify-content: center; align-items: center; min-height: 100vh;
        }
        
        .cert-container {
            width: 1123px; height: 794px; 
            position: relative;
            background-color: #fcfaf2; 
            background-image: url('https://www.transparenttextures.com/patterns/cream-paper.png');
            padding: 60px 100px; 
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between; 
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        /* LOGO MARCA D'ÁGUA NO CENTRO */
        .watermark-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px; /* Tamanho grande */
            opacity: 0.04; /* Quase invisível, bem sutil */
            z-index: 0; /* Fica atrás do texto */
            pointer-events: none;
            filter: grayscale(100%); /* Opcional: deixa preto e branco para ficar mais sóbrio */
        }

        .cert-border-outer {
            position: absolute; top: 25px; left: 25px; right: 25px; bottom: 25px;
            border: 4px double #1a237e; 
            pointer-events: none; z-index: 1;
        }
        .cert-border-inner {
            position: absolute; top: 35px; left: 35px; right: 35px; bottom: 35px;
            border: 2px solid #c5a000; 
            pointer-events: none; z-index: 1;
        }

        .header-titles { color: #1a237e; margin-top: 10px; z-index: 2; }
        h1 { font-family: 'Playfair Display', serif; font-size: 70px; font-weight: 900; text-transform: uppercase; letter-spacing: 5px; margin: 0; line-height: 1; }
        h2 { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 400; text-transform: uppercase; letter-spacing: 3px; margin-top: 10px; color: #c5a000; }

        .content { 
            font-size: 22px; color: #444; line-height: 1.6; 
            max-width: 85%; 
            margin: 20px auto; 
            z-index: 2;
        }
        
        .student-name {
            font-family: 'Great Vibes', cursive;
            font-size: 90px;
            color: #1a237e;
            display: block;
            margin: 20px 0 30px 0;
            line-height: 1;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .highlight { font-weight: bold; color: #1a237e; }

        .footer { 
            display: flex; justify-content: space-around; align-items: flex-end; 
            width: 90%; margin-bottom: 50px; position: relative; z-index: 2;
        }
        
        .sig-block { text-align: center; width: 280px; position: relative; }
        .sig-image-placeholder { height: 50px; display: flex; align-items: flex-end; justify-content: center; margin-bottom: 5px; }
        .sig-line { height: 1px; background: #1a237e; margin-bottom: 10px; }
        .sig-name { font-weight: bold; font-size: 18px; color: #1a237e; font-family: 'Playfair Display', serif; }
        .sig-role { font-size: 14px; color: #666; font-style: italic; }

        .seal {
            position: absolute; bottom: 90px; left: 50%; transform: translateX(-50%);
            color: #c5a000; font-size: 80px; z-index: 2; /* Aumentei o z-index para ficar acima da marca d'agua */
            text-shadow: 2px 2px 0 #fff; /* Contorno branco para destacar do fundo */
        }

        .validation-code { 
            position: absolute; bottom: 40px; right: 40px; 
            font-size: 11px; color: #888; font-family: monospace; 
            background: #fcfaf2; padding: 2px 5px; z-index: 3;
        }

        .print-btn-container { position: fixed; bottom: 30px; right: 30px; z-index: 999; }
        .btn-print { background: #1a237e; color: #fff; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: bold; box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3); border: none; font-size: 16px; transition: 0.3s; cursor: pointer; display: flex; align-items: center; gap: 10px; }
        .btn-print:hover { transform: translateY(-3px); background: #283593; box-shadow: 0 8px 20px rgba(26, 35, 126, 0.4); }

        @media print {
            body { margin: 0; background: none; }
            .cert-container { margin: 0; box-shadow: none; }
            .print-btn-container { display: none; }
            @page { margin: 0; }
        }
    </style>
</head>
<body>

    <div class="cert-container">
        <img src="assets/img/logo.svg" class="watermark-logo" alt="Marca D'água">

        <div class="cert-border-outer"></div>
        <div class="cert-border-inner"></div>
        
        <div class="seal"><i class="fas fa-certificate"></i></div>

        <div class="header-titles">
            <h1>Certificado</h1>
            <h2>de Conclusão de Curso</h2>
        </div>

        <div class="content">
            Certificamos que
            <span class="student-name"><?= htmlspecialchars($user['name']) ?></span>
            concluiu com êxito todos os requisitos da trilha de aprendizado na plataforma <span class="highlight">CXPRO</span>.
        </div>

        <div class="footer">
            <div class="sig-block">
                <div class="sig-image-placeholder"></div>
                <div class="sig-line"></div>
                <div class="sig-name">CXPRO TELECOMUNICAÇÕES</div>
                <div class="sig-role">CNPJ: 10733998/0001-97 </div>
            </div>
            
            <div class="sig-block">
                <div class="sig-image-placeholder"></div>
                <div class="sig-line"></div>
                <div class="sig-name"><?= $data_emissao ?></div>
                <div class="sig-role">Data de Emissão</div>
            </div>
        </div>

        <div class="validation-code">
            Cód. Validação: <?= $hash_validacao ?>
        </div>
    </div>

    <div class="print-btn-container">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-file-pdf"></i> Salvar como PDF / Imprimir
        </button>
    </div>

</body>
</html>