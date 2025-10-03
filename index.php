<?php
session_start();

// Se o admin já está logado, manda ele direto para o dashboard
if (isset($_SESSION['admin_logado_vision']) && $_SESSION['admin_logado_vision'] === true) {
    header('Location: dashboard.php');
    exit;
}

$erro_msg = '';
if (isset($_GET['erro']) && $_GET['erro'] == 'senha') {
    $erro_msg = 'Senha incorreta!';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - VISION</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="admin-container">
        <h2>Login do Painel de Admin</h2>
        
        <?php if ($erro_msg): ?>
            <div class="mensagem erro"><?php echo $erro_msg; ?></div>
        <?php endif; ?>
        
        <form action="login_action.php" method="POST">
            <label for="senha_admin">Senha:</label>
            <input type="password" name="senha_admin" id="senha_admin" required autofocus>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>