<?php
session_start();
// Caminho corrigido
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => 'error', 'message' => 'Não autorizado']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

if ($dados && isset($dados['nova_conta']) && in_array($dados['nova_conta'], ['real', 'demo'])) {
    
    $nova_conta = $dados['nova_conta'];
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET conta_ativa = ? WHERE id = ?");
        $stmt->execute([$nova_conta, $user_id]);
        echo json_encode(['status' => 'success', 'message' => 'Conta alterada para ' . $nova_conta]);
    } catch (\PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
}
?>