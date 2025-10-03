<?php
// 1. Inicia a sessão
// (Obrigatório para guardar que o usuário está logado)
session_start();

// 2. Inclui a conexão
require 'db.php';

// 3. Pega os dados do formulário
$login_field = $_POST['login_field'] ?? null; // (o campo "Email ou CPF")
$password = $_POST['password'] ?? null;

// 4. Validação
if (empty($login_field) || empty($password)) {
    die("Preencha ambos os campos. <a href='login.php'>Tentar novamente</a>");
}

// 5. Procura o usuário no banco (pelo email OU pelo cpf)
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? OR cpf = ?");
    $stmt->execute([$login_field, $login_field]);
    $usuario = $stmt->fetch();

    // 6. Verifica a senha
    // password_verify() compara a senha digitada com o hash salvo no banco
    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        
        // Senha correta!
        
        // 7. Salva os dados do usuário na sessão
        session_regenerate_id(true); // Segurança extra
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_email'] = $usuario['email'];
        
        // 8. Redireciona para a página principal do aplicativo
        header("Location: index.php");
        exit;
        
    } else {
        // Erro no login
        die("Usuário ou senha inválidos. <a href='login.php'>Tentar novamente</a>");
    }

} catch (\PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage() . " <a href='login.php'>Voltar</a>");
}
?>