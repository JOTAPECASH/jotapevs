<?php
session_start();
date_default_timezone_set('America/Campo_Grande');

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db.php';

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

$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$count_sql = "SELECT COUNT(*) FROM investimentos i JOIN partidas p ON i.partida_id = p.id WHERE i.user_id = ? AND i.tipo_conta = ?";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute([$user_id, $dados_usuario['conta_ativa']]);
$total_itens = $count_stmt->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

$historico_sql = "SELECT p.titulo, p.status, i.valor_investido, p.odd, p.data_criacao 
                  FROM investimentos i JOIN partidas p ON i.partida_id = p.id
                  WHERE i.user_id = :user_id AND i.tipo_conta = :conta_ativa
                  ORDER BY p.data_criacao DESC
                  LIMIT :limit OFFSET :offset";
$hist_stmt = $pdo->prepare($historico_sql);
$hist_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$hist_stmt->bindValue(':conta_ativa', $dados_usuario['conta_ativa'], PDO::PARAM_STR);
$hist_stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
$hist_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$hist_stmt->execute();
$historico_list = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - VISION</title>
    <link rel="stylesheet" href="app_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="header-top">
                <div class="account-switcher">
                    <div class="account-info-line"><span id="nome-conta"><?php echo $nome_conta_ativa; ?></span></div>
                    <div class="account-info-line"><span id="saldo-display" class="account-balance">$<?php echo number_format($saldo_para_usar, 2); ?></span></div>
                </div>
                <a href="pagamentos.php" class="btn-pagamentos">Pagamentos</a>
            </div>
            <div class="asset-selector"><div class="asset-main"><i class="fas fa-history"></i> Histórico de Apostas</div></div>
        </header>
        
        <main class="main-content">
            <div class="history-panel">
                <table id="history-table">
                    <thead>
                        <tr>
                            <th>Partida</th>
                            <th>Investido ($)</th>
                            <th>Resultado ($)</th>
                            <th>Odds</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historico_list)): ?>
                            <tr><td colspan="5" style="text-align:center; color:#8a91a0;">Nenhum histórico encontrado para esta conta.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historico_list as $item): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($item['titulo']); ?>
                                        <br>
                                        <small style="color: #8a91a0; font-size: 0.8em;">
                                            <?php echo date('d/m/Y H:i', strtotime($item['data_criacao'])); ?>
                                        </small>
                                    </td>
                                    <td><?php echo number_format($item['valor_investido'], 2); ?></td>
                                    <td>
                                        <?php
                                        if ($item['status'] == 'ganhou') {
                                            $lucro = ($item['valor_investido'] * $item['odd']) - $item['valor_investido'];
                                            echo '<span class="lucro">+' . number_format($lucro, 2) . '</span>';
                                        } elseif ($item['status'] == 'perdeu') {
                                            echo '<span class="perda">-' . number_format($item['valor_investido'], 2) . '</span>';
                                        } else { echo '<span>-</span>'; }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($item['odd'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status_class = $item['status'] == 'aberta' ? 'aguardando' : $item['status'];
                                        $status_text = $item['status'] == 'aberta' ? 'Aguardando' : ucfirst($item['status']);
                                        echo '<span class="status ' . $status_class . '">' . $status_text . '</span>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="paginacao">
                    <?php if ($pagina_atual > 1): ?>
                        <a href="?pagina=<?php echo $pagina_atual - 1; ?>">&laquo; Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?pagina=<?php echo $i; ?>" class="<?php if ($i == $pagina_atual) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($pagina_atual < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina_atual + 1; ?>">Próximo &raquo;</a>
                    <?php endif; ?>
                </div>

            </div>
        </main>
        
        <footer class="control-footer">
            <nav class="app-nav-bar">
                <a href="index.php" class="nav-icon"><i class="fas fa-chart-line fa-2x"></i></a>
                <a href="historico.php" class="nav-icon active"><i class="fas fa-history fa-2x"></i></a>
                <a href="lideres.php" class="nav-icon"><i class="fas fa-trophy fa-2x"></i></a>
                <a href="perfil.php" class="nav-icon"><i class="fas fa-user-circle fa-2x"></i></a>
                <a href="logout.php" class="nav-icon" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt fa-2x"></i></a>
            </nav>
        </footer>
    </div>
</body>
</html>