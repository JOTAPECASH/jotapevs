<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['erro' => 'Usuário não logado']);
    exit;
}
require 'db.php';

date_default_timezone_set('America/Campo_Grande');
$pdo->exec("SET time_zone = '-04:00';");

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$dados_usuario = $stmt->fetch();
$conta_ativa = $dados_usuario['conta_ativa'];

$evolucao_percentual = 0;
$filtro_transacao_sql = "";
$data_ultima_transacao = null;
$params = [':user_id' => $user_id, ':conta_ativa' => $conta_ativa];

if ($conta_ativa == 'real') {
    $stmt_ultima_transacao = $pdo->prepare("SELECT MAX(data_transacao) FROM transacoes WHERE user_id = ?");
    $stmt_ultima_transacao->execute([$user_id]);
    $data_ultima_transacao = $stmt_ultima_transacao->fetchColumn();

    if ($data_ultima_transacao) {
        $filtro_transacao_sql = " AND p.data_finalizacao > :data_ultima_transacao";
        $params[':data_ultima_transacao'] = $data_ultima_transacao;
    }
    
    $sql_lucro_ciclo = "SELECT p.status, i.valor_investido, p.odd 
                        FROM investimentos i JOIN partidas p ON i.partida_id = p.id
                        WHERE i.user_id = :user_id AND i.tipo_conta = :conta_ativa
                        AND (p.status = 'ganhou' OR p.status = 'perdeu')
                        $filtro_transacao_sql";
    
    $stmt_lucro_ciclo = $pdo->prepare($sql_lucro_ciclo);
    $stmt_lucro_ciclo->execute($params);
    $investimentos_ciclo = $stmt_lucro_ciclo->fetchAll();

    $lucro_desde_ultima_transacao = 0;
    foreach($investimentos_ciclo as $inv) {
        if ($inv['status'] == 'ganhou') {
            $lucro_desde_ultima_transacao += ($inv['valor_investido'] * $inv['odd']) - $inv['valor_investido'];
        } else {
            $lucro_desde_ultima_transacao -= $inv['valor_investido'];
        }
    }

    $saldo_atual_real = $dados_usuario['total_depositado_real'] + $dados_usuario['total_lucro_real'];
    $banca_base_ciclo = $saldo_atual_real - $lucro_desde_ultima_transacao;

    if ($banca_base_ciclo > 0 && $lucro_desde_ultima_transacao != 0) {
        $evolucao_percentual = ($lucro_desde_ultima_transacao / $banca_base_ciclo) * 100;
    } else if ($lucro_desde_ultima_transacao > 0 && $banca_base_ciclo <= 0) {
        $evolucao_percentual = 100.0;
    }

} else { 
    $lucro_total_demo = $dados_usuario['total_lucro_demo'];
    $deposito_total_demo = $dados_usuario['total_depositado_demo'];
    if ($deposito_total_demo > 0) {
        $evolucao_percentual = ($lucro_total_demo / $deposito_total_demo) * 100;
    } else if ($lucro_total_demo > 0) {
        $evolucao_percentual = 100.0;
    }
}

if ($dados_usuario['conta_ativa'] == 'real') {
    $status_para_usar = $dados_usuario['status_real'];
    $stake_para_usar = $dados_usuario['stake_real'];
    $saldo_total = $dados_usuario['total_depositado_real'] + $dados_usuario['total_lucro_real'];
} else {
    $status_para_usar = 'ativo';
    $stake_para_usar = $dados_usuario['stake_demo'];
    $saldo_total = $dados_usuario['total_depositado_demo'] + $dados_usuario['total_lucro_demo'];
}

$periodo = $_GET['periodo'] ?? '1m';
$filtro_data_sql_grafico = "";
if ($periodo !== 'all') {
    switch ($periodo) {
        case '24h': $filtro_data_sql_grafico = " AND p.data_finalizacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"; break;
        case '7d':  $filtro_data_sql_grafico = " AND p.data_finalizacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; break;
        case '1m':  $filtro_data_sql_grafico = " AND p.data_finalizacao >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"; break;
        case '1y':  $filtro_data_sql_grafico = " AND p.data_finalizacao >= DATE_SUB(NOW(), INTERVAL 1 YEAR)"; break;
    }
}

function abreviarTitulo($titulo) {
    $times = explode(' x ', $titulo);
    if (count($times) === 2) {
        $time1_abrev = substr(trim($times[0]), 0, 5);
        $time2_abrev = substr(trim($times[1]), 0, 5);
        return strtolower($time1_abrev . ' x ' . $time2_abrev);
    }
    return strtolower(substr($titulo, 0, 13));
}

$sql_em_jogo = "SELECT SUM(i.valor_investido) as total_em_jogo FROM investimentos i JOIN partidas p ON i.partida_id = p.id WHERE i.user_id = ? AND i.tipo_conta = ? AND p.status = 'aberta'";
$stmt_em_jogo = $pdo->prepare($sql_em_jogo);
$stmt_em_jogo->execute([$user_id, $conta_ativa]);
$total_em_jogo = $stmt_em_jogo->fetchColumn() ?? 0;
$saldo_para_mostrar = $saldo_total - $total_em_jogo;

$aposta_aberta_nome = null;
$aposta_aberta_ganho_potencial = 0;
if ($total_em_jogo > 0) {
    $sql_aposta_aberta = "SELECT p.titulo, p.odd, i.valor_investido FROM investimentos i JOIN partidas p ON i.partida_id = p.id WHERE i.user_id = ? AND i.tipo_conta = ? AND p.status = 'aberta' LIMIT 1";
    $stmt_aposta_aberta = $pdo->prepare($sql_aposta_aberta);
    $stmt_aposta_aberta->execute([$user_id, $conta_ativa]);
    $aposta_aberta = $stmt_aposta_aberta->fetch();
    if ($aposta_aberta) {
        $aposta_aberta_nome = $aposta_aberta['titulo'];
        $valor_investido = $aposta_aberta['valor_investido'];
        $odd = $aposta_aberta['odd'];
        $aposta_aberta_ganho_potencial = ($valor_investido * $odd) - $valor_investido;
    }
}

$sql_ticker = "SELECT i.valor_investido, p.odd, p.status, p.titulo FROM investimentos i JOIN partidas p ON i.partida_id = p.id WHERE i.user_id = ? AND (p.status = 'ganhou' OR p.status = 'perdeu') AND i.tipo_conta = ? ORDER BY p.data_finalizacao DESC LIMIT 30";
$stmt_ticker = $pdo->prepare($sql_ticker);
$stmt_ticker->execute([$user_id, $conta_ativa]);
$investimentos_ticker = $stmt_ticker->fetchAll();
$ultimos_resultados = [];
foreach ($investimentos_ticker as $invest) {
    $lucro_perda = ($invest['status'] == 'ganhou') ? ($invest['valor_investido'] * $invest['odd']) - $invest['valor_investido'] : -$invest['valor_investido'];
    $ultimos_resultados[] = [ 'titulo' => abreviarTitulo($invest['titulo']), 'lucro' => round($lucro_perda, 2) ];
}

$sql_grafico = "SELECT i.valor_investido, p.odd, p.status, p.data_finalizacao 
                FROM investimentos i JOIN partidas p ON i.partida_id = p.id 
                WHERE i.user_id = :user_id AND i.tipo_conta = :conta_ativa AND (p.status = 'ganhou' OR p.status = 'perdeu')
                $filtro_transacao_sql 
                $filtro_data_sql_grafico
                ORDER BY p.data_finalizacao ASC";
$stmt_grafico = $pdo->prepare($sql_grafico);
$stmt_grafico->execute($params);
$investimentos_grafico = $stmt_grafico->fetchAll();

$lucro_acumulado = 0.00;
$labels_grafico = [];
$datapoints_grafico = [];
if (!empty($investimentos_grafico)) {
    $data_inicial_grafico = $data_ultima_transacao ?? $investimentos_grafico[0]['data_finalizacao'];
    $labels_grafico[] = $data_inicial_grafico;
    $datapoints_grafico[] = 0;
    foreach ($investimentos_grafico as $invest) {
        $lucro_perda_da_aposta = ($invest['status'] == 'ganhou') ? ($invest['valor_investido'] * $invest['odd']) - $invest['valor_investido'] : -$invest['valor_investido'];
        $lucro_acumulado += $lucro_perda_da_aposta;
        $labels_grafico[] = $invest['data_finalizacao']; 
        $datapoints_grafico[] = round($lucro_acumulado, 2);
    }
}

$dados_para_enviar = [
    'saldo' => (float) $saldo_para_mostrar,
    'stake' => (int) $stake_para_usar,
    'status' => $status_para_usar,
    'conta_ativa' => $conta_ativa,
    'grafico_labels' => $labels_grafico,
    'grafico_datapoints' => $datapoints_grafico,
    'evolucao_percentual' => round($evolucao_percentual, 2),
    'tem_aposta_aberta' => ($total_em_jogo > 0),
    'aposta_nome' => $aposta_aberta_nome,
    'aposta_ganho' => (float) $aposta_aberta_ganho_potencial,
    'ultimos_resultados' => $ultimos_resultados
];

header('Content-Type: application/json');
echo json_encode($dados_para_enviar);
?>