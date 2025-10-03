<?php
require 'header.php';
date_default_timezone_set('America/Campo_Grande');

$erro = ''; $sucesso = '';

if (isset($_POST['finalize_partida'])) {
    $partida_id = $_POST['partida_id']; $resultado_final = $_POST['resultado_final'];
    try {
        $pdo->beginTransaction();
        $partida_stmt = $pdo->prepare("SELECT * FROM partidas WHERE id = ? AND status = 'aberta'");
        $partida_stmt->execute([$partida_id]);
        $partida = $partida_stmt->fetch();
        if ($partida) {
            $invest_stmt = $pdo->prepare("SELECT * FROM investimentos WHERE partida_id = ?");
            $invest_stmt->execute([$partida_id]);
            $investimentos_feitos = $invest_stmt->fetchAll();
            foreach ($investimentos_feitos as $invest) {
                $coluna_lucro = ($invest['tipo_conta'] == 'real') ? 'total_lucro_real' : 'total_lucro_demo';
                $lucro_ou_perda = 0;
                if ($resultado_final == 'ganhou') { $lucro_ou_perda = ($invest['valor_investido'] * $partida['odd']) - $invest['valor_investido']; } 
                else { $lucro_ou_perda = -$invest['valor_investido']; }
                $update_lucro_stmt = $pdo->prepare("UPDATE usuarios SET $coluna_lucro = $coluna_lucro + ? WHERE id = ?");
                $update_lucro_stmt->execute([$lucro_ou_perda, $invest['user_id']]);
            }
            $update_partida_stmt = $pdo->prepare("UPDATE partidas SET status = ?, data_finalizacao = ? WHERE id = ?");
            $data_final = date('Y-m-d H:i:s');
            $update_partida_stmt->execute([$resultado_final, $data_final, $partida_id]);
            $pdo->commit();
            $sucesso = "Partida finalizada! Resultados aplicados ao lucro dos usuários.";
        } else { $erro = "Partida já foi finalizada ou não existe."; }
    } catch (Exception $e) { $pdo->rollBack(); $erro = "Erro ao finalizar: " . $e->getMessage(); }
}

// --- LÓGICA DE PAGINAÇÃO PARA ADMIN ---
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$total_itens = $pdo->query("SELECT COUNT(*) FROM partidas")->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

$partidas_sql = "SELECT * FROM partidas ORDER BY data_criacao DESC LIMIT :limit OFFSET :offset";
$partidas_stmt = $pdo->prepare($partidas_sql);
$partidas_stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
$partidas_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$partidas_stmt->execute();
$partidas = $partidas_stmt->fetchAll();
?>
<?php if ($sucesso): ?><div class="mensagem sucesso"><?php echo $sucesso; ?></div><?php endif; ?> <?php if ($erro): ?><div class="mensagem erro"><?php echo $erro; ?></div><?php endif; ?>
<div class="section-container">
    <div class="list-section">
        <h3>Partidas Cadastradas</h3>
        <table>
            <thead><tr><th>ID</th><th>Título</th><th>Odd</th><th>Status</th><th>Ação</th></tr></thead>
            <tbody>
                <?php foreach ($partidas as $partida): ?>
                <tr>
                    <td><?php echo $partida['id']; ?></td>
                    <td><?php echo htmlspecialchars($partida['titulo']); ?></td>
                    <td><?php echo $partida['odd']; ?></td>
                    <td><span class="status <?php echo $partida['status']; ?>"><?php echo $partida['status']; ?></span></td>
                    <td><?php if ($partida['status'] == 'aberta'): ?>
                        <form method="POST" action="partidas_listar.php" class="action-form">
                            <input type="hidden" name="finalize_partida" value="1">
                            <input type="hidden" name="partida_id" value="<?php echo $partida['id']; ?>">
                            <button type="submit" name="resultado_final" value="ganhou" class="btn-ganhou">Ganhou</button>
                            <button type="submit" name="resultado_final" value="perdeu" class="btn-perdeu">Perdeu</button>
                        </form>
                    <?php else: ?>
                        Finalizada
                    <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
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
</div>
<?php require 'footer.php'; ?>