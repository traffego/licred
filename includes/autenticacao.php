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

/**
 * Verifica se o usuário tem a permissão especificada
 * 
 * @param string $permissao Nome da permissão a ser verificada
 * @return bool Retorna true se o usuário tem a permissão, false caso contrário
 */
function temPermissao($permissao) {
    // Usuário com ID 1 é sempre administrador e tem todas as permissões
    if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == 1) {
        return true;
    }
    
    // TODO: Implementar verificação de permissões para outros usuários
    // Por enquanto, apenas o usuário com ID 1 tem permissões administrativas
    return false;
}