<?php
require 'header.php';
// --- CORREÇÃO DE FUSO HORÁRIO ---
date_default_timezone_set('America/Campo_Grande');

$erro = ''; $sucesso = '';

if (isset($_POST['add_partida'])) {
    $titulo = $_POST['titulo'];
    $odd = (float)$_POST['odd'];
    $investidores_selecionados = $_POST['investidores'] ?? [];

    if (empty($investidores_selecionados)) {
        $erro = "Erro: Você precisa selecionar pelo menos um investidor.";
    } else {
        try {
            $pdo->beginTransaction();
            // --- ALTERAÇÃO NO INSERT: Adicionamos a data_criacao ---
            $stmt_partida = $pdo->prepare("INSERT INTO partidas (titulo, odd, data_criacao) VALUES (?, ?, ?)");
            $data_atual = date('Y-m-d H:i:s'); // Gera a data/hora no fuso horário correto
            $stmt_partida->execute([$titulo, $odd, $data_atual]); // Salva a data/hora correta
            $partida_id = $pdo->lastInsertId();

            $placeholders = implode(',', array_fill(0, count($investidores_selecionados), '?'));
            $usuarios_stmt = $pdo->prepare("SELECT id, total_depositado_real, total_lucro_real, total_depositado_demo, total_lucro_demo, stake_real, stake_demo FROM usuarios WHERE id IN ($placeholders)");
            $usuarios_stmt->execute($investidores_selecionados);
            $investidores = $usuarios_stmt->fetchAll();

            foreach ($investidores as $investidor) {
                $saldo_real = $investidor['total_depositado_real'] + $investidor['total_lucro_real'];
                $valor_a_investir_real = ($saldo_real * $investidor['stake_real']) / 100;
                if ($valor_a_investir_real > 0) {
                    $invest_stmt = $pdo->prepare("INSERT INTO investimentos (user_id, partida_id, valor_investido, percentual_stake, tipo_conta) VALUES (?, ?, ?, ?, 'real')");
                    $invest_stmt->execute([$investidor['id'], $partida_id, $valor_a_investir_real, $investidor['stake_real']]);
                }
                $saldo_demo = $investidor['total_depositado_demo'] + $investidor['total_lucro_demo'];
                $valor_a_investir_demo = ($saldo_demo * $investidor['stake_demo']) / 100;
                if ($valor_a_investir_demo > 0) {
                     $invest_stmt_demo = $pdo->prepare("INSERT INTO investimentos (user_id, partida_id, valor_investido, percentual_stake, tipo_conta) VALUES (?, ?, ?, ?, 'demo')");
                    $invest_stmt_demo->execute([$investidor['id'], $partida_id, $valor_a_investir_demo, $investidor['stake_demo']]);
                }
            }
            $pdo->commit();
            $sucesso = "Aposta '".htmlspecialchars($titulo)."' criada para ".count($investidores)." investidor(es)! O saldo será atualizado após a finalização.";
        } catch (Exception $e) { $pdo->rollBack(); $erro = "Erro ao criar aposta: " . $e->getMessage(); }
    }
}
$sql_usuarios = "SELECT id, email, total_depositado_real, total_lucro_real, stake_real FROM usuarios WHERE status_real = 'ativo'";
$usuarios_ativos = $pdo->query($sql_usuarios)->fetchAll();
$total_potencial_investimento = 0;
foreach ($usuarios_ativos as $usuario) {
    $saldo_real = $usuario['total_depositado_real'] + $usuario['total_lucro_real'];
    $total_potencial_investimento += ($saldo_real * $usuario['stake_real']) / 100;
}
?>
<?php if ($sucesso): ?><div class="mensagem sucesso"><?php echo $sucesso; ?></div><?php endif; ?>
<?php if ($erro): ?><div class="mensagem erro"><?php echo $erro; ?></div><?php endif; ?>
<div class="section-container">
    <div class="form-section">
        <h3>Criar Nova Partida (Seleção Manual)</h3>
        <form method="POST" action="partidas_criar.php">
            <input type="hidden" name="add_partida" value="1">
            <label>Título da Aposta</label><input type="text" name="titulo" required>
            <label>Odd da Aposta</label><input type="number" step="0.01" name="odd" required>
            <div class="summary-box">
                <div>Potencial Total (Ativos): <span>R$ <?php echo number_format($total_potencial_investimento, 2); ?></span></div>
                <div>Valor Selecionado: <span id="valor-selecionado">R$ 0.00</span></div>
            </div>
            <label>Selecionar Investidores:</label>
            <div class="investor-list">
                <table>
                    <thead><tr><th><input type="checkbox" id="select-all-investors"></th><th>Email</th><th>Saldo</th><th>Stake</th><th>Valor do Stake</th></tr></thead>
                    <tbody>
                        <?php foreach ($usuarios_ativos as $usuario): ?>
                            <?php 
                                $saldo_real = $usuario['total_depositado_real'] + $usuario['total_lucro_real'];
                                $valor_stake = ($saldo_real * $usuario['stake_real']) / 100;
                            ?>
                            <tr>
                                <td><input type="checkbox" name="investidores[]" value="<?php echo $usuario['id']; ?>" data-valor-stake="<?php echo $valor_stake; ?>" class="investor-checkbox"></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>R$ <?php echo number_format($saldo_real, 2); ?></td>
                                <td><?php echo $usuario['stake_real']; ?>%</td>
                                <td>R$ <?php echo number_format($valor_stake, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit">Criar Aposta com Selecionados</button>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.investor-checkbox');
        const valorSelecionadoEl = document.getElementById('valor-selecionado');
        const selectAllCheckbox = document.getElementById('select-all-investors');
        function updateTotal() {
            let totalSelecionado = 0;
            checkboxes.forEach(function(cb) { if (cb.checked) { totalSelecionado += parseFloat(cb.getAttribute('data-valor-stake')); } });
            valorSelecionadoEl.innerText = `R$ ${totalSelecionado.toFixed(2)}`;
        }
        checkboxes.forEach(function(checkbox) { checkbox.addEventListener('change', updateTotal); });
        selectAllCheckbox.addEventListener('change', function() { checkboxes.forEach(function(cb) { cb.checked = selectAllCheckbox.checked; }); updateTotal(); });
    });
</script>
<?php require 'footer.php'; ?>