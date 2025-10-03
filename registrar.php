<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - VISION</title>
    <link rel="stylesheet" href="login_style.css">
</head>
<body>
    <div class="modal-backdrop">
        <div class="auth-modal">
            
            <div class="logo-banner">
                <img src="003.png" alt="VISION Logo">
            </div>

            <form action="acao_registrar.php" method="POST" class="auth-form">
                
                <div class="input-group">
                    <input type="text" name="cpf" placeholder="CPF" required>
                </div>

                <div class="input-group">
                    <input type="email" name="email" placeholder="E-mail" required>
                </div>

                <div class="input-group">
                    <input type="text" name="telefone" placeholder="Telefone com DDD" required>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" placeholder="Senha" required>
                </div>

                <button type="submit">Concluir cadastro</button>

                <div class="switch-form">
                    <a href="login.php">
                        Já tem uma conta? 
                        <span class="highlight">Entre agora!</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>