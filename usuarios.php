<?php
require 'header.php'; // Inclui o cabeçalho e a verificação de login

$erro = $_GET['erro'] ?? '';
$sucesso = $_GET['sucesso'] ?? '';

$cpf_busca = $_GET['cpf_busca'] ?? '';
$search_sql_condition = '';
$params = [];

if (!empty($cpf_busca)) {
    $search_sql_condition = "WHERE cpf LIKE ?";
    $params[] = '%' . $cpf_busca . '%';
}

$sql_usuarios = "SELECT id, cpf, email, criado_em, total_depositado_real, total_lucro_real
                 FROM usuarios
                 $search_sql_condition
                 ORDER BY id ASC";
$stmt_usuarios = $pdo->prepare($sql_usuarios);
$stmt_usuarios->execute($params);
$todos_usuarios = $stmt_usuarios->fetchAll();
?>

<?php if ($sucesso): ?><div class="mensagem sucesso"><?php echo htmlspecialchars($sucesso); ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem erro"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

<div class="search-container">
    <form method="GET" action="usuarios.php" class="action-form">
        <input type="text" name="cpf_busca" placeholder="Buscar por CPF..." value="<?php echo htmlspecialchars($cpf_busca); ?>">
        <button type="submit">Buscar 🔍</button>
    </form>
</div>

<div class="section-container">
    <div class="list-section">
        <h3>Investidores</h3>
        <table>
            <thead><tr><th>Usuário (CPF/Email)</th><th>Saldo (R$)</th><th>Lucro Ciclo (%)</th><th>Ações (Depósito / Saque)</th></tr></thead>
            <tbody>
                <?php foreach ($todos_usuarios as $usuario): ?>
                    <?php
                        // --- LÓGICA DE CÁLCULO DE P&L CORRIGIDA (IGUAL A DO USUÁRIO) ---
                        $stmt_ultimo_saque = $pdo->prepare("SELECT MAX(data_transacao) FROM transacoes WHERE user_id = ?");
                        $stmt_ultimo_saque->execute([$usuario['id']]);
                        $data_ultimo_saque = $stmt_ultimo_saque->fetchColumn();

                        $filtro_saque_sql = "";
                        $params_lucro = [':user_id' => $usuario['id'], ':conta_ativa' => 'real'];
                        if ($data_ultimo_saque) {
                            $filtro_saque_sql = " AND p.data_finalizacao > :data_ultimo_saque";
                            $params_lucro[':data_ultimo_saque'] = $data_ultimo_saque;
                        }

                        $sql_lucro_ciclo = "SELECT p.status, i.valor_investido, p.odd 
                                            FROM investimentos i JOIN partidas p ON i.partida_id = p.id
                                            WHERE i.user_id = :user_id AND i.tipo_conta = :conta_ativa
                                            AND (p.status = 'ganhou' OR p.status = 'perdeu')
                                            $filtro_saque_sql";
                        $stmt_lucro_ciclo = $pdo->prepare($sql_lucro_ciclo);
                        $stmt_lucro_ciclo->execute($params_lucro);
                        $investimentos_ciclo = $stmt_lucro_ciclo->fetchAll();

                        $lucro_desde_ultimo_saque = 0;
                        foreach($investimentos_ciclo as $inv) {
                            if ($inv['status'] == 'ganhou') {
                                $lucro_desde_ultimo_saque += ($inv['valor_investido'] * $inv['odd']) - $inv['valor_investido'];
                            } else {
                                $lucro_desde_ultimo_saque -= $inv['valor_investido'];
                            }
                        }

                        $saldo_real = $usuario['total_depositado_real'] + $usuario['total_lucro_real'];
                        $banca_base_ciclo = $saldo_real - $lucro_desde_ultimo_saque;

                        $lucro_percentual_ciclo = 0;
                        if ($banca_base_ciclo > 0 && $lucro_desde_ultimo_saque != 0) {
                            $lucro_percentual_ciclo = ($lucro_desde_ultimo_saque / $banca_base_ciclo) * 100;
                        } else if ($lucro_desde_ultimo_saque > 0 && $banca_base_ciclo <= 0) {
                            $lucro_percentual_ciclo = 100.0;
                        }
                        $lucro_class = $lucro_desde_ultimo_saque >= 0 ? 'lucro' : 'perda';
                    ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($usuario['cpf'] ?: 'N/A'); ?>
                            <small style="display: block; color: #888;"><?php echo htmlspecialchars($usuario['email']); ?></small>
                        </td>
                        <td><?php echo number_format($saldo_real, 2); ?></td>
                        <td class="<?php echo $lucro_class; ?>">
                            <?php echo number_format($lucro_percentual_ciclo, 2); ?>%
                        </td>
                        <td>
                            <form method="POST" action="processar_transacao.php" class="action-form inline-form">
                                <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                <input type="hidden" name="tipo" value="deposito">
                                <input type="number" step="0.01" name="valor" placeholder="Valor Depósito" required>
                                <button type="submit" class="btn-ganhou">Depositar</button>
                            </form>
                            <form method="POST" action="processar_transacao.php" class="action-form inline-form">
                                <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                <input type="hidden" name="tipo" value="saque">
                                <input type="number" step="0.01" name="valor" placeholder="Valor Saque" required>
                                <button type="submit" class="btn-perdeu">Sacar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>