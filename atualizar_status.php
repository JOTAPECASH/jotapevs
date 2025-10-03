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

if ($dados && isset($dados['status']) && in_array($dados['status'], ['ativo', 'pausado'])) {
    
    $novo_status = $dados['status'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET status_real = ? WHERE id = ?");
        $stmt->execute([$novo_status, $user_id]);

        echo json_encode(['status' => 'success', 'novo_status' => $novo_status]);

    } catch (\PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
}
?>