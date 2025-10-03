<?php
session_start();
// O caminho para o db.php precisa voltar um diretório para a pasta raiz
require '../db.php'; 

// Define o fuso horário para garantir que a data da transação seja salva corretamente
date_default_timezone_set('America/Campo_Grande');

// Verificação de segurança para garantir que apenas um admin logado possa executar esta ação
// A sua variável de sessão de admin é 'admin_logado_vision'
if (!isset($_SESSION['admin_logado_vision']) || $_SESSION['admin_logado_vision'] !== true) {
    header("Location: usuarios.php?erro=Acesso negado.");
    exit;
}

// Garante que o script só execute se os dados vierem do formulário via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: usuarios.php");
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$tipo = $_POST['tipo'] ?? null;
$valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;

if (!$user_id || !$tipo || $valor <= 0) {
    header("Location: usuarios.php?erro=Dados inválidos fornecidos.");
    exit;
}

try {
    // Inicia uma transação para garantir que ambas as operações (INSERT e UPDATE) funcionem ou falhem juntas
    $pdo->beginTransaction();

    if ($tipo === 'deposito') {
        // 1. Insere o registro na tabela de transações
        $stmt_trans = $pdo->prepare("INSERT INTO transacoes (user_id, tipo, valor, data_transacao) VALUES (?, ?, ?, ?)");
        $stmt_trans->execute([$user_id, 'deposito', $valor, date('Y-m-d H:i:s')]);

        // 2. Atualiza o saldo depositado do usuário
        $stmt_user = $pdo->prepare("UPDATE usuarios SET total_depositado_real = total_depositado_real + ? WHERE id = ?");
        $stmt_user->execute([$valor, $user_id]);

        $sucesso_msg = "Depósito de R$".number_format($valor, 2)." registrado com sucesso!";

    } elseif ($tipo === 'saque') {
        // 1. Verifica se o usuário tem saldo suficiente antes de fazer qualquer coisa
        $stmt_saldo = $pdo->prepare("SELECT (total_depositado_real + total_lucro_real) as saldo_total FROM usuarios WHERE id = ?");
        $stmt_saldo->execute([$user_id]);
        $saldo_atual = $stmt_saldo->fetchColumn();

        if ($saldo_atual === false || $saldo_atual < $valor) {
            throw new Exception("Saldo insuficiente para realizar o saque.");
        }

        // 2. Insere o registro na tabela de transações
        $stmt_trans = $pdo->prepare("INSERT INTO transacoes (user_id, tipo, valor, data_transacao) VALUES (?, ?, ?, ?)");
        $stmt_trans->execute([$user_id, 'saque', $valor, date('Y-m-d H:i:s')]);
        
        // 3. Atualiza o lucro do usuário (saques são abatidos do lucro)
        $stmt_user = $pdo->prepare("UPDATE usuarios SET total_lucro_real = total_lucro_real - ? WHERE id = ?");
        $stmt_user->execute([$valor, $user_id]);

        $sucesso_msg = "Saque de R$".number_format($valor, 2)." registrado com sucesso!";

    } else {
        throw new Exception("Tipo de transação inválido.");
    }

    // Se tudo deu certo, confirma as alterações no banco de dados
    $pdo->commit();
    header("Location: usuarios.php?sucesso=" . urlencode($sucesso_msg));
    exit;

} catch (Exception $e) {
    // Se algo deu errado, desfaz todas as alterações
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: usuarios.php?erro=" . urlencode("Erro: " . $e->getMessage()));
    exit;
}
?>