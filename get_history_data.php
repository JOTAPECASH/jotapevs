<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['erro' => 'Usuário não logado']);
    exit;
}
require 'db.php';

$user_id = $_SESSION['user_id'];
// Precisa saber qual conta está ativa para buscar o histórico correto
$stmt = $pdo->prepare("SELECT conta_ativa FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch();
$conta_ativa = $usuario['conta_ativa'];

// Busca o histórico COMPLETO (incluindo partidas 'aberta')
$historico_sql = "SELECT p.titulo, p.status, i.valor_investido, p.odd 
                  FROM investimentos i JOIN partidas p ON i.partida_id = p.id
                  WHERE i.user_id = ? AND i.tipo_conta = ?
                  ORDER BY p.data_criacao DESC";
$hist_stmt = $pdo->prepare($historico_sql);
$hist_stmt->execute([$user_id, $conta_ativa]);
$historico_list = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);

// Formata os dados para o JavaScript
$historico_final = [];
foreach($historico_list as $item) {
    if ($item['status'] == 'ganhou') {
        $item['lucro_perda'] = ($item['valor_investido'] * $item['odd']) - $item['valor_investido'];
    } else if ($item['status'] == 'perdeu') {
        $item['lucro_perda'] = -$item['valor_investido'];
    } else {
        $item['lucro_perda'] = 0; // Para partidas abertas
    }
    $historico_final[] = $item;
}

header('Content-Type: application/json');
echo json_encode($historico_final);
?>