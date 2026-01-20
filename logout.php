<?php
// logout.php
session_start(); // Inicia a sessão para poder acessá-la
session_destroy(); // Destroi todas as variáveis de sessão (desloga)
header("Location: login.php"); // Redireciona para o login
exit;
?>