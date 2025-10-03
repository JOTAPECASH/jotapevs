<?php
// A sessão JÁ FOI INICIADA pelo arquivo que incluiu este (ex: header.php).
// Aqui nós apenas verificamos se o admin está logado.

if (!isset($_SESSION['admin_logado_vision']) || $_SESSION['admin_logado_vision'] !== true) {
    // Se não estiver logado, expulsa o usuário para a página de login.
    header('Location: index.php');
    exit;
}
?>