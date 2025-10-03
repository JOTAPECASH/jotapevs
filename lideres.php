<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db.php';

// --- LÓGICA DO HEADER ---
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT total_depositado_real, total_lucro_real, total_depositado_demo, total_lucro_demo, conta_ativa FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$dados_usuario_logado = $stmt->fetch();
if ($dados_usuario_logado['conta_ativa'] == 'real') {
    $saldo_para_usar = $dados_usuario_logado['total_depositado_real'] + $dados_usuario_logado['total_lucro_real'];
    $nome_conta_ativa = "Conta Real";
} else {
    $saldo_para_usar = $dados_usuario_logado['total_depositado_demo'] + $dados_usuario_logado['total_lucro_demo'];
    $nome_conta_ativa = "Conta Demo";
}

// --- LÓGICA DO RANKING ---
$ranking_sql = "SELECT email, total_depositado_real, total_lucro_real, (total_depositado_real + total_lucro_real) as saldo_real_total FROM usuarios ORDER BY saldo_real_total DESC LIMIT 50";
$ranking_stmt = $pdo->query($ranking_sql);
$ranking_list = $ranking_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - VISION</title>
    <link rel="stylesheet" href="app_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="header-top">
                <div class="account-switcher" id="account-switcher">
                    <div class="account-info-line"><span id="nome-conta"><?php echo $nome_conta_ativa; ?></span> <i class="fas fa-chevron-down"></i></div>
                    <div class="account-info-line"><span id="saldo-display" class="account-balance">$<?php echo number_format($saldo_para_usar, 2); ?></span></div>
                </div>
                </div>
            <div class="asset-selector"><div class="asset-main"><i class="fas fa-trophy"></i> Tabela de Líderes</div></div>
        </header>
        
        <main class="main-content">
            <div class="history-panel">
                <table id="history-table">
                    <thead><tr><th>#</th><th>Investidor</th><th>Saldo Real</th><th>Lucro (%)</th></tr></thead>
                    <tbody>
                        <?php if (empty($ranking_list)): ?>
                            <tr><td colspan="4" style="text-align:center; color:#8a91a0;">Nenhum usuário no ranking.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ranking_list as $index => $posicao): ?>
                                <?php
                                    $lucro_percentual = 0;
                                    if ($posicao['total_depositado_real'] > 0) {
                                        $lucro_percentual = ($posicao['total_lucro_real'] / $posicao['total_depositado_real']) * 100;
                                    } else if ($posicao['total_lucro_real'] > 0) {
                                        $lucro_percentual = 100.0;
                                    }
                                    $lucro_class = $posicao['total_lucro_real'] >= 0 ? 'lucro' : 'perda';
                                ?>
                                <tr class="<?php if($index < 3) echo 'rank-top-' . ($index + 1); ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo substr(htmlspecialchars($posicao['email']), 0, 4) . '****'; ?></td>
                                    <td class="lucro">$<?php echo number_format($posicao['saldo_real_total'], 2); ?></td>
                                    <td class="<?php echo $lucro_class; ?>"><?php echo number_format($lucro_percentual, 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
        
        <footer class="control-footer">
            <nav class="app-nav-bar">
                <a href="index.php" class="nav-icon"><i class="fas fa-chart-line fa-2x"></i></a>
                <a href="historico.php" class="nav-icon"><i class="fas fa-history fa-2x"></i></a>
                <a href="lideres.php" class="nav-icon active"><i class="fas fa-trophy fa-2x"></i></a>
                <a href="perfil.php" class="nav-icon"><i class="fas fa-user-circle fa-2x"></i></a>
                <a href="logout.php" class="nav-icon" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt fa-2x"></i></a>
            </nav>
        </footer>
    </div>
    <script>
        const accountSwitcherEl = document.getElementById('account-switcher');
        if(accountSwitcherEl) {
            accountSwitcherEl.addEventListener('click', () => {
                const contaAtual = '<?php echo $dados_usuario_logado['conta_ativa']; ?>';
                const novaConta = (contaAtual === 'demo') ? 'real' : 'demo';
                document.body.style.opacity = '0.5'; 
                fetch('mudar_conta.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nova_conta: novaConta }) })
                .then(r=>r.json()).then(d=>{if(d.status==='success'){window.location.reload();}});
            });
        }
    </script>
</body>
</html>