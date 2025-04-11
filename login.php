<?php
// Configurações básicas de segurança da sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once __DIR__ . '/config.php';             // define BASE_URL
require_once __DIR__ . '/includes/head.php';      // usa BASE_URL

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitização mais rigorosa dos dados
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    
    $senha = filter_input(INPUT_POST, 'senha', FILTER_UNSAFE_RAW);
    $senha = trim($senha);
    
    $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
    $csrf_token = trim($csrf_token);

    if (!$email) {
        $erro = "Email inválido.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($csrf_token !== $_SESSION['csrf_token']) {
        $erro = "Erro de segurança. Por favor, tente novamente.";
    } else {
        if (!isset($conn) || !$conn) {
            $erro = "Erro de conexão. Tente novamente mais tarde.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, senha FROM usuarios WHERE email = ?");
            if (!$stmt) {
                $erro = "Erro ao processar login. Tente novamente mais tarde.";
            } else {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $resultado = mysqli_stmt_get_result($stmt);

                if ($usuario = mysqli_fetch_assoc($resultado)) {
                    if (password_verify($senha, $usuario['senha'])) {
                        $_SESSION['usuario_id'] = $usuario['id'];
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $erro = "Credenciais inválidas.";
                    }
                } else {
                    $erro = "Credenciais inválidas.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-4">
            <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="Logo do Sistema" class="img-fluid" style="max-height: 100px;">
        </div>
        <h4 class="mb-4 text-center">Acesso ao Sistema</h4>
        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <form method="POST" autocomplete="off" id="loginForm">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Digite seu email" 
                           aria-describedby="emailHelp"
                           required>
                </div>
                <div id="emailHelp" class="form-text">Digite o email cadastrado no sistema</div>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="senha" name="senha" class="form-control" 
                           placeholder="Digite sua senha"
                           required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="text-end mt-2">
                    <a href="<?php echo BASE_URL; ?>/recuperar_senha.php" class="text-decoration-none">
                        <i class="bi bi-question-circle"></i> Esqueceu a senha?
                    </a>
                </div>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit" class="btn btn-primary w-100 mb-3" id="submitButton">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <span class="button-text">Entrar</span>
            </button>
            <div class="text-center">
                <a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Voltar à página inicial
                </a>
            </div>
        </form>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const senha = document.getElementById('senha');
            const icon = this.querySelector('i');
            if (senha.type === 'password') {
                senha.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                senha.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Show loading state on form submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = document.getElementById('submitButton');
            const spinner = button.querySelector('.spinner-border');
            const text = button.querySelector('.button-text');
            
            spinner.classList.remove('d-none');
            text.textContent = 'Entrando...';
            button.disabled = true;
        });
    </script>
</body>
</html>
