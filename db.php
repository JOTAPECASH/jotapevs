<?php
/*
  Configuração da Conexão com o Banco de Dados
*/

// Seus novos dados do painel da Hostinger
$host = 'localhost'; 
$db   = 'u537505403_vision';
$user = 'u537505403_jotapecash';
$pass = 'majuka2016MA$';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     // Tenta conectar ao banco de dados
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, $options);
     
} catch (\PDOException $e) {
     // Se der erro, exibe a mensagem e para o script
     die("Erro ao conectar ao banco de dados. Verifique o db.php: " . $e->getMessage());
}
?>