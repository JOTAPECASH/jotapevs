<?php
// 1. Inclui o arquivo de conexão
require 'db.php';

// 2. Pega os dados do formulário
$cpf = $_POST['cpf'] ?? null;
$email = $_POST['email'] ?? null;
$telefone = $_POST['telefone'] ?? null;
$password = $_POST['password'] ?? null;

// 3. Validação simples
if (empty($cpf) || empty($email) || empty($password)) {
    // Se algum campo estiver vazio, morre e exibe o erro.
    die("Por favor, preencha todos os campos obrigatórios. <a href='registrar.php'>Voltar</a>");
}

// 4. Criptografa a senha (NUNCA salve senhas em texto puro)
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// 5. Tenta inserir no banco de dados
try {
    // Prepara o comando SQL para evitar injeção de SQL
    $stmt = $pdo->prepare("INSERT INTO usuarios (cpf, email, telefone, password_hash) VALUES (?, ?, ?, ?)");
    
    // Executa o comando, substituindo os "?" pelos dados
    $stmt->execute([$cpf, $email, $telefone, $password_hash]);

    // 6. Se deu certo, redireciona para o login
    // O "registro=sucesso" é opcional, só para sabermos que deu certo
    header("Location: login.php?registro=sucesso");
    exit;

} catch (\PDOException $e) {
    // 7. Se deu erro...
    if ($e->errorInfo[1] == 1062) {
        // Erro 1062 = "Entrada duplicada" (CPF ou Email já existe)
        die("Erro: Este CPF ou E-mail já está cadastrado. <a href='login.php'>Tente fazer login</a>");
    } else {
        // Outro erro
        die("Erro ao registrar: " . $e->getMessage() . " <a href='registrar.php'>Voltar</a>");
    }
}
?>