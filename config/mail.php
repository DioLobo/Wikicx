<?php
// config/mail.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carrega as classes do PHPMailer
require __DIR__ . '/../mailer/Exception.php';
require __DIR__ . '/../mailer/PHPMailer.php';
require __DIR__ . '/../mailer/SMTP.php';

// --- LÓGICA PARA CARREGAR O .ENV ---
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignora comentários e linhas vazias
            if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
            
            // Separa CHAVE=VALOR
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove aspas se houver (ex: "senha" vira senha)
            $value = trim($value, "\"'");

            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }
        }
    }
}

// Carrega o .env se necessário
if (empty($_ENV['SMTP_USER'])) {
    loadEnv(__DIR__ . '/../.env');
}

function sendMail($to, $subject, $body) {
    // Verifica se as variáveis de ambiente foram carregadas
    if (!isset($_ENV['SMTP_USER']) || !isset($_ENV['SMTP_PASS'])) {
        echo "<b>ERRO DE CONFIG:</b> Arquivo .env não carregado ou variáveis faltando.";
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // --- MODO DEBUG (Mude para 0 quando terminar de testar) ---
        $mail->SMTPDebug = 0; 
        $mail->Debugoutput = 'html'; 

        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Gmail exige isso na porta 587
        $mail->Port       = $_ENV['SMTP_PORT'];
        $mail->CharSet    = 'UTF-8';

        // --- CORREÇÃO OBRIGATÓRIA PARA XAMPP (IGNORAR SSL) ---
        // Sem isso, o XAMPP bloqueia o certificado do Gmail
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Remetente e Destinatário
        $mail->setFrom($_ENV['SMTP_USER'], 'CXPRO Suporte');
        $mail->addAddress($to);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        // Versão texto puro para clientes de email antigos
        $mail->AltBody = strip_tags($body); 

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Mostra o erro detalhado na tela para você corrigir
        echo "<br><div style='background: #ffddd0; color: #d8000c; padding: 10px; border: 1px solid #d8000c;'>";
        echo "<b>ERRO PHPMailer:</b> " . $mail->ErrorInfo;
        echo "</div>";
        return false;
    }
}
?>