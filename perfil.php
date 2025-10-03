<?php
session_start();
require 'db.php';
date_default_timezone_set('America/Campo_Grande');

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_user->execute([$user_id]);
$usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

$lucro_usuario_atual = $usuario['total_lucro_real'] ?? 0;
$rank_sql = "SELECT COUNT(*) FROM usuarios WHERE total_lucro_real > ?";
$stmt_rank = $pdo->prepare($rank_sql);
$stmt_rank->execute([$lucro_usuario_atual]);
$usuarios_com_maior_lucro = $stmt_rank->fetchColumn();
$rank_usuario = $usuarios_com_maior_lucro + 1;

$stmt_trans = $pdo->prepare("SELECT * FROM transacoes WHERE user_id = ? ORDER BY data_transacao DESC");
$stmt_trans->execute([$user_id]);
$transacoes = $stmt_trans->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - VISION</title>
    <link rel="stylesheet" href="app_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="profile-page-header">
                <a href="index.php" class="back-button">⬅️</a>
                <span class="header-title">Meu Perfil</span>
                <div style="width: 24px;"></div>
            </div>
        </header>

        <main class="main-content">
            <div class="profile-section">
                <div class="page-background-logo">
                    <img src="003.png" alt="VISION Logo">
                </div>

                <div class="profile-header-main">
                    <div class="profile-pic-container">
                        <img src="uploads/<?php echo htmlspecialchars($usuario['foto_perfil'] ?: 'default.png'); ?>" alt="Foto de Perfil" class="profile-pic">
                    </div>
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($usuario['nome_completo'] ?: $usuario['email']); ?></h4>
                        <p>Rank Geral: <span class="rank-highlight">#<?php echo $rank_usuario; ?></span></p>
                        <form action="upload_foto.php" method="post" enctype="multipart/form-data" id="form-upload-foto">
                            <label for="foto" class="file-label">Alterar Foto</label>
                            <input type="file" name="foto" id="foto" onchange="this.form.submit()" style="display: none;">
                        </form>
                    </div>
                </div>

                <div class="profile-content-wrapper">
                    <div class="profile-details">
                        <h4>Dados Pessoais</h4>
                        <form action="atualizar_dados.php" method="post">
                            <div class="form-grid">
                                <div><label>Nome Completo</label><input type="text" name="nome_completo" value="<?php echo htmlspecialchars($usuario['nome_completo']); ?>" placeholder="Seu nome completo"></div>
                                <div><label>CPF</label><input type="text" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf']); ?>" placeholder="Seu CPF"></div>
                                <div><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly></div>
                                <div><label>Telefone com DDD</label><input type="text" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>" placeholder="(XX) XXXXX-XXXX"></div>
                                <div class="full-width"><label>Endereço</label><input type="text" name="endereco" value="<?php echo htmlspecialchars($usuario['endereco']); ?>" placeholder="Sua rua e número"></div>
                                <div><label>CEP</label><input type="text" name="cep" value="<?php echo htmlspecialchars($usuario['cep']); ?>" placeholder="XXXXX-XXX"></div>
                                
                                <div><label>Cidade</label><input type="text" name="cidade" value="<?php echo htmlspecialchars($usuario['cidade']); ?>" placeholder="Sua cidade"></div>
                                <div><label>Estado</label><input type="text" name="estado" value="<?php echo htmlspecialchars($usuario['estado']); ?>" placeholder="Seu estado"></div>

                            </div>
                            <button type="submit">Salvar Alterações</button>
                        </form>
                    </div>

                    <div class="profile-details">
                        <h4>Histórico de Saldo</h4>
                        <div class="transaction-history">
                            <table>
                                <thead><tr><th>Data</th><th>Tipo</th><th>Valor</th></tr></thead>
                                <tbody>
                                    <?php if (empty($transacoes)): ?>
                                        <tr><td colspan="3" style="text-align: center; color: #8a91a0;">Nenhuma transação encontrada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($transacoes as $trans): ?>
                                        <tr class="trans-<?php echo $trans['tipo']; ?>">
                                            <td><?php echo date('d/m/Y H:i', strtotime($trans['data_transacao'])); ?></td>
                                            <td><?php echo ucfirst($trans['tipo']); ?></td>
                                            <td>R$ <?php echo number_format($trans['valor'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="control-footer">
            <nav class="app-nav-bar">
                <a href="index.php" class="nav-icon"><i class="fas fa-chart-line fa-2x"></i></a>
                <a href="historico.php" class="nav-icon"><i class="fas fa-history fa-2x"></i></a>
                <a href="lideres.php" class="nav-icon"><i class="fas fa-trophy fa-2x"></i></a>
                <a href="perfil.php" class="nav-icon active"><i class="fas fa-user-circle fa-2x"></i></a>
                <a href="logout.php" class="nav-icon" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt fa-2x"></i></a>
            </nav>
        </footer>
    </div>
</body>
</html>