<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Empréstimos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/tiny-slider.css">
    <script src="<?= BASE_URL ?>assets/js/mascaras.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilo2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilo.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/volt.css">
    <script src="<?= BASE_URL ?>assets/js/logo-detector.js"></script>
    <style>
    /* Breadcrumb personalizado */
    .breadcrumb {
        background: transparent;
        padding: 0;
        margin: 0;
    }

    .breadcrumb-item a {
        color: #344767;
        text-decoration: none;
    }

    .breadcrumb-item.active {
        color: #6c757d;
    }
    </style>
</head>
<?php 

// require_once 'conexao.php';
// require 'autenticacao.php';

require 'conexao.php';

$pagina_atual = basename($_SERVER['PHP_SELF']);
if ($pagina_atual !== 'login.php' && $pagina_atual !== 'login2.php') {
    require_once 'navbar.php';

    // Gerar breadcrumb dinamicamente
    $caminho = $_SERVER['REQUEST_URI'];
    $caminho = str_replace('/sistema_emprestimos_v1/', '', $caminho);
    $partes = explode('/', trim($caminho, '/'));
    $base_url = BASE_URL;
    
    // Remove partes específicas do caminho e extensões
    $partes = array_filter($partes, function($parte) {
        return !empty($parte);
    });

    // Remove extensão .php dos nomes
    $partes = array_map(function($parte) {
        return str_replace('.php', '', $parte);
    }, $partes);

    // Tradução de nomes para português
    $traducoes = [
        'emprestimos' => 'Empréstimos',
        'clientes' => 'Clientes',
        'visualizar' => 'Visualizar',
        'novo' => 'Novo',
        'editar' => 'Editar',
        'dashboard' => 'Painel'
    ];

    echo '<div class="container py-4">';
    echo '<nav aria-label="breadcrumb" class="mb-4">';
    echo '<ol class="breadcrumb">';
    echo '<li class="breadcrumb-item"><a href="' . $base_url . '">Home</a></li>';

    $caminho_atual = '';
    foreach ($partes as $parte) {
        if (empty($parte)) continue;
        
        $caminho_atual .= $parte . '/';
        $nome_exibicao = isset($traducoes[$parte]) ? $traducoes[$parte] : ucfirst($parte);
        
        if ($parte === end($partes)) {
            echo '<li class="breadcrumb-item active">' . $nome_exibicao . '</li>';
        } else {
            echo '<li class="breadcrumb-item"><a href="' . $base_url . $caminho_atual . '">' . $nome_exibicao . '</a></li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
    echo '</div>';
}

?>

<body>