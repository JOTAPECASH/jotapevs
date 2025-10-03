<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT total_depositado_real, total_lucro_real, conta_ativa FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$dados_usuario = $stmt->fetch();

$saldo_para_usar = 0;
$nome_conta_ativa = "Conta Demo";

if ($dados_usuario && $dados_usuario['conta_ativa'] == 'real') {
    $saldo_para_usar = $dados_usuario['total_depositado_real'] + $dados_usuario['total_lucro_real'];
    $nome_conta_ativa = "Conta Real";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamentos - VISION</title>
    <link rel="stylesheet" href="app_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="app-container">

        <main class="main-content">
            <div class="pagamentos-container">
                <div class="saldo-grande">$<?php echo number_format($saldo_para_usar, 2); ?></div>
                <p class="nome-conta-pagamentos"><?php echo $nome_conta_ativa; ?></p>
                <div class="botoes-pagamento">
                    <a href="https://wa.me/+5567992657769?text=Ol%C3%A1%20%F0%9F%98%8A%2C%20gostaria%20de%20fazer%20uma%20retirada%20da%20minha%20conta." class="btn-pagamento btn-retirar" target="_blank">
                        <i class="fas fa-arrow-down"></i> Retirar
                    </a>
                    <a href="https://wa.me/+5567992657769?text=Ol%C3%A1%20%F0%9F%98%8A%2C%20gostaria%20de%20fazer%20um%20dep%C3%B3sito%20na%20minha%20conta." class="btn-pagamento btn-depositar" target="_blank">
                        <i class="fas fa-plus"></i> Depositar
                    </a>
                </div>
            </div>
        </main>
        
        <footer class="control-footer">
            <nav class="app-nav-bar">
                <a href="index.php" class="nav-icon"><i class="fas fa-chart-line fa-2x"></i></a>
                <a href="historico.php" class="nav-icon"><i class="fas fa-history fa-2x"></i></a>
                <a href="lideres.php" class="nav-icon"><i class="fas fa-trophy fa-2x"></i></a>
                <a href="perfil.php" class="nav-icon"><i class="fas fa-user-circle fa-2x"></i></a>
                <a href="logout.php" class="nav-icon" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt fa-2x"></i></a>
            </nav>
        </footer>
    </div>
</body>
</html>