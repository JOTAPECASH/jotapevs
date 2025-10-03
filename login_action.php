<?php
session_start();

// --- DEFINA SUA SENHA DE ADMIN AQUI ---
$SENHA_ADMIN = "maju2016";
// ------------------------------------

// Verifica se a senha foi enviada do formulário
if (isset($_POST['senha_admin'])) {
    if ($_POST['senha_admin'] == $SENHA_ADMIN) {
        // Senha correta, cria a sessão
        $_SESSION['admin_logado_vision'] = true;
        // Redireciona para o painel principal
        header('Location: dashboard.php');
        exit;
    } else {
        // Senha errada, volta para a página de login com uma mensagem de erro
        header('Location: index.php?erro=senha');
        exit;
    }
}

// Se alguém tentar acessar este arquivo diretamente, manda para o login
header('Location: index.php');
exit;
?>