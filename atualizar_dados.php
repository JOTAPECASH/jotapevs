<?php
session_start();
require 'db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Pega os dados do formulário
    $nome_completo = $_POST['nome_completo'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $cep = $_POST['cep'] ?? '';

    // Prepara e executa a atualização no banco de dados
    try {
        $sql = "UPDATE usuarios SET 
                    nome_completo = :nome_completo,
                    cpf = :cpf,
                    telefone = :telefone,
                    endereco = :endereco,
                    cep = :cep
                WHERE id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':nome_completo', $nome_completo);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':cep', $cep);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        $stmt->execute();

        // Redireciona de volta para a página de perfil
        header("Location: perfil.php?sucesso=1");
        exit;

    } catch (PDOException $e) {
        // Em caso de erro, redireciona de volta com uma mensagem de erro
        header("Location: perfil.php?erro=1");
        // Para depuração: error_log("Erro ao atualizar perfil: " . $e->getMessage());
        exit;
    }
} else {
    // Se alguém tentar acessar o arquivo diretamente, redireciona para o perfil
    header("Location: perfil.php");
    exit;
}
?>