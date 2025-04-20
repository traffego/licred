<?php
// Verificar se a sessão já foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    // Verificar se não estamos já na página de login para evitar loops
    $pagina_atual = basename($_SERVER['PHP_SELF']);
    if ($pagina_atual !== 'login.php') {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}