<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VISION</title>
    <link rel="stylesheet" href="login_style.css">
</head>
<body>
    <div class="modal-backdrop">
        <div class="auth-modal">
            
            <div class="logo-banner">
                <img src="003.png" alt="VISION Logo">
            </div>

            <form action="acao_login.php" method="POST" class="auth-form">
                
                <div class="input-group">
                    <input type="text" name="login_field" placeholder="Email ou CPF" required>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" placeholder="Senha" required>
                </div>

                <button type="submit">Entrar</button>

                <div class="form-links">
                    <a href="#">Esqueceu a senha?</a>
                </div>

                <div class="switch-form">
                    <a href="registrar.php">
                        Você ainda não possui uma conta? 
                        <span class="highlight">Crie uma agora!</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>