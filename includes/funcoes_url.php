<?php
/**
 * Funções para manipulação e geração de URLs amigáveis
 */

/**
 * Gera uma URL amigável para uma página do sistema
 * 
 * @param string $module Módulo do sistema (clientes, emprestimos, etc)
 * @param string $action Ação a ser executada (novo, editar, visualizar, etc)
 * @param int|null $id Identificador do registro (opcional)
 * @param array $params Parâmetros adicionais para a URL (opcional)
 * @return string URL amigável formatada
 */
function url_amigavel($module, $action = '', $id = null, $params = []) {
    $base_url = rtrim(BASE_URL, '/');
    $url = $base_url . '/' . $module;
    
    if (!empty($action)) {
        $url .= '/' . $action;
    }
    
    if (!is_null($id)) {
        $url .= '/' . $id;
    }
    
    // Adiciona parâmetros extras se existirem
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Gera URL para o módulo de clientes
 * 
 * @param string $action Ação a ser executada
 * @param int|null $id ID do cliente (opcional)
 * @param array $params Parâmetros adicionais (opcional)
 * @return string URL formatada
 */
function url_clientes($action = '', $id = null, $params = []) {
    return url_amigavel('clientes', $action, $id, $params);
}

/**
 * Gera URL para o módulo de empréstimos
 * 
 * @param string $action Ação a ser executada
 * @param int|null $id ID do empréstimo (opcional)
 * @param array $params Parâmetros adicionais (opcional)
 * @return string URL formatada
 */
function url_emprestimos($action = '', $id = null, $params = []) {
    return url_amigavel('emprestimos', $action, $id, $params);
}

/**
 * Gera URL para o módulo de parcelas
 * 
 * @param int $emprestimo_id ID do empréstimo
 * @param string $action Ação a ser executada (opcional)
 * @param int|null $parcela_id ID da parcela (opcional)
 * @param array $params Parâmetros adicionais (opcional)
 * @return string URL formatada
 */
function url_parcelas($emprestimo_id, $action = '', $parcela_id = null, $params = []) {
    $base_url = rtrim(BASE_URL, '/');
    $url = $base_url . '/emprestimos/parcelas/' . $emprestimo_id;
    
    if (!empty($action)) {
        $url .= '/' . $action;
    }
    
    if (!is_null($parcela_id)) {
        $url .= '/' . $parcela_id;
    }
    
    // Adiciona parâmetros extras se existirem
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Gera URL para o módulo de mensagens
 * 
 * @param string $action Ação a ser executada
 * @param int|null $id ID do template (opcional)
 * @param array $params Parâmetros adicionais (opcional)
 * @return string URL formatada
 */
function url_mensagens($action = '', $id = null, $params = []) {
    return url_amigavel('mensagens', $action, $id, $params);
}

/**
 * Obtém o slug amigável para URL a partir de uma string
 * 
 * @param string $texto Texto a ser convertido em slug
 * @return string Slug formatado
 */
function gerar_slug($texto) {
    // Remove acentos
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    
    // Converte para minúsculas
    $texto = strtolower($texto);
    
    // Remove caracteres especiais
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    
    // Substitui espaços e outros caracteres por hífen
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    
    // Remove hífens do início e fim
    $texto = trim($texto, '-');
    
    return $texto;
} 