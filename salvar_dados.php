<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Não autorizado']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

// Agora esperamos o stake E o tipo de conta
if ($dados && isset($dados['stake']) && isset($dados['tipo_conta']) && in_array($dados['tipo_conta'], ['real', 'demo'])) {
    
    $stake = intval($dados['stake']);
    $tipo_conta = $dados['tipo_conta'];
    $user_id = $_SESSION['user_id'];

    // Define qual coluna do banco será atualizada
    $coluna_stake = ($tipo_conta == 'real') ? 'stake_real' : 'stake_demo';

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET $coluna_stake = ? WHERE id = ?");
        $stmt->execute([$stake, $user_id]);
        echo json_encode(['status' => 'success']);
    } catch (\PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
}
?>