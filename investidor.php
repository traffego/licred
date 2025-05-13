<?php
// Iniciar buffer de saída para evitar o erro "headers already sent"
ob_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/autenticacao.php';
require_once __DIR__ . '/includes/head.php';

// Verificar se o usuário logado é um investidor
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Negar acesso a administradores (que devem usar o dashboard.php)
if (isset($_SESSION['nivel_autoridade']) && 
    ($_SESSION['nivel_autoridade'] === 'administrador' || 
     $_SESSION['nivel_autoridade'] === 'superadmin')) {
    header("Location: dashboard.php");
    exit;
}

// Verificar se o usuário é um investidor
if (!isset($_SESSION['nivel_autoridade']) || $_SESSION['nivel_autoridade'] !== 'investidor') {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Buscar informações do investidor
$sql_usuario = "SELECT nome, email FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();

// Buscar contas do investidor
$sql_contas = "SELECT 
                c.id, 
                c.nome, 
                c.comissao,
                c.saldo_inicial + COALESCE(SUM(CASE WHEN mc.tipo = 'entrada' THEN mc.valor ELSE -mc.valor END), 0) as saldo_atual 
               FROM contas c 
               LEFT JOIN movimentacoes_contas mc ON c.id = mc.conta_id 
               WHERE c.usuario_id = ? AND c.status = 'ativo' 
               GROUP BY c.id";
               
$stmt_contas = $conn->prepare($sql_contas);
$stmt_contas->bind_param("i", $usuario_id);
$stmt_contas->execute();
$result_contas = $stmt_contas->get_result();
$contas = [];
$total_saldo = 0;

if ($result_contas && $result_contas->num_rows > 0) {
    while ($conta = $result_contas->fetch_assoc()) {
        $contas[] = $conta;
        $total_saldo += floatval($conta['saldo_atual']);
    }
}

// Buscar empréstimos associados ao investidor
$sql_emprestimos = "SELECT 
                    e.id, 
                    e.cliente_id,
                    e.valor_emprestado,
                    e.valor_parcela,
                    e.parcelas as total_parcelas,
                    e.juros_percentual,
                    c.nome as cliente_nome,
                    COUNT(CASE WHEN p.status = 'pago' THEN 1 END) as parcelas_pagas,
                    SUM(CASE WHEN p.status = 'pago' THEN p.valor_pago ELSE 0 END) as total_recebido
                FROM 
                    emprestimos e
                JOIN
                    clientes c ON e.cliente_id = c.id
                LEFT JOIN
                    parcelas p ON e.id = p.emprestimo_id
                WHERE 
                    e.investidor_id = ?
                GROUP BY
                    e.id
                ORDER BY
                    e.data_criacao DESC";

$stmt_emprestimos = $conn->prepare($sql_emprestimos);
$stmt_emprestimos->bind_param("i", $usuario_id);
$stmt_emprestimos->execute();
$result_emprestimos = $stmt_emprestimos->get_result();
$emprestimos = [];
$total_emprestado = 0;
$total_recebido = 0;
$total_parcelas_pagas = 0;
$comissoes_calculadas = 0;

if ($result_emprestimos && $result_emprestimos->num_rows > 0) {
    while ($emprestimo = $result_emprestimos->fetch_assoc()) {
        $emprestimos[] = $emprestimo;
        $total_emprestado += floatval($emprestimo['valor_emprestado']);
        $total_recebido += floatval($emprestimo['total_recebido']);
        $total_parcelas_pagas += intval($emprestimo['parcelas_pagas']);
        
        // Calcular comissão baseada nas parcelas pagas (se houver conta com comissão configurada)
        if (!empty($contas) && floatval($contas[0]['comissao']) > 0) {
            $percentual_comissao = floatval($contas[0]['comissao']);
            $valor_parcelas_pagas = floatval($emprestimo['total_recebido']);
            $comissao_calculada = $valor_parcelas_pagas * ($percentual_comissao / 100);
            $comissoes_calculadas += $comissao_calculada;
        }
    }
}

// Buscar últimas movimentações das contas do investidor
$sql_movimentacoes = "SELECT 
                         mc.id, 
                         mc.conta_id, 
                         c.nome as conta_nome, 
                         mc.tipo, 
                         mc.valor, 
                         mc.descricao, 
                         mc.data_movimentacao
                     FROM 
                         movimentacoes_contas mc
                     INNER JOIN 
                         contas c ON mc.conta_id = c.id
                     WHERE 
                         c.usuario_id = ?
                     ORDER BY 
                         mc.data_movimentacao DESC
                     LIMIT 10";

$stmt_movimentacoes = $conn->prepare($sql_movimentacoes);
$stmt_movimentacoes->bind_param("i", $usuario_id);
$stmt_movimentacoes->execute();
$result_movimentacoes = $stmt_movimentacoes->get_result();
$movimentacoes = [];

if ($result_movimentacoes && $result_movimentacoes->num_rows > 0) {
    while ($mov = $result_movimentacoes->fetch_assoc()) {
        $movimentacoes[] = $mov;
    }
}

// Processar aporte (adição de saldo)
if (isset($_POST['realizar_aporte'])) {
    $conta_id = intval($_POST['conta_id']);
    $valor = floatval(str_replace(',', '.', $_POST['valor_aporte']));
    $descricao = trim($_POST['descricao']);
    
    // Verificar se o investidor possui apenas uma conta ativa e se é a conta informada
    $sql_verificar_conta = "SELECT id FROM contas WHERE id = ? AND usuario_id = ? AND status = 'ativo' LIMIT 1";
    $stmt_verificar = $conn->prepare($sql_verificar_conta);
    $stmt_verificar->bind_param("ii", $conta_id, $usuario_id);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar && $result_verificar->num_rows > 0) {
        // Conta pertence ao usuário e está ativa, registrar o aporte
        $sql_aporte = "INSERT INTO movimentacoes_contas (conta_id, tipo, valor, descricao, data_movimentacao) 
                      VALUES (?, 'entrada', ?, ?, NOW())";
        $stmt_aporte = $conn->prepare($sql_aporte);
        $stmt_aporte->bind_param("ids", $conta_id, $valor, $descricao);
        
        if ($stmt_aporte->execute()) {
            // Aporte registrado com sucesso
            $mensagem = "Aporte de R$ " . number_format($valor, 2, ',', '.') . " realizado com sucesso!";
            $tipo_alerta = "success";
            
            // Redirecionar para evitar reenvio do formulário
            header("Location: investidor.php?sucesso=1&msg=" . urlencode($mensagem));
            exit;
        } else {
            $mensagem = "Erro ao registrar aporte: " . $conn->error;
            $tipo_alerta = "danger";
        }
    } else {
        $mensagem = "Conta inválida ou não pertence a você.";
        $tipo_alerta = "danger";
    }
}

// Processar solicitação de saque
if (isset($_POST['solicitar_saque'])) {
    $conta_id = intval($_POST['conta_id']);
    $valor = floatval(str_replace(',', '.', $_POST['valor_saque']));
    $descricao = trim($_POST['descricao_saque']);
    
    // Verificar se o investidor possui apenas uma conta ativa e se é a conta informada
    $sql_verificar_conta = "SELECT id, saldo_inicial + COALESCE((SELECT SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) FROM movimentacoes_contas WHERE conta_id = contas.id), 0) as saldo_atual FROM contas WHERE id = ? AND usuario_id = ? AND status = 'ativo' LIMIT 1";
    $stmt_verificar = $conn->prepare($sql_verificar_conta);
    $stmt_verificar->bind_param("ii", $conta_id, $usuario_id);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar && $result_verificar->num_rows > 0) {
        $conta_info = $result_verificar->fetch_assoc();
        $saldo_atual = floatval($conta_info['saldo_atual']);
        
        // Verificar se há saldo suficiente
        if ($valor <= 0) {
            $mensagem = "O valor do saque deve ser maior que zero.";
            $tipo_alerta = "danger";
        } elseif ($valor > $saldo_atual) {
            $mensagem = "Saldo insuficiente para realizar o saque.";
            $tipo_alerta = "danger";
        } else {
            // Verificar se a tabela solicitacoes_saque existe
            $table_exists = $conn->query("SHOW TABLES LIKE 'solicitacoes_saque'");
            if ($table_exists->num_rows == 0) {
                // Criar a tabela se não existir
                $sql_create_table = "CREATE TABLE IF NOT EXISTS solicitacoes_saque (
                    id INT AUTO_INCREMENT PRIMARY KEY, 
                    usuario_id INT NOT NULL, 
                    conta_id INT NOT NULL, 
                    valor DECIMAL(10,2) NOT NULL, 
                    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente', 
                    descricao TEXT, 
                    data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP, 
                    data_processamento DATETIME NULL, 
                    observacao_admin TEXT
                )";
                $conn->query($sql_create_table);
            }
            
            // Registrar a solicitação de saque
            $sql_saque = "INSERT INTO solicitacoes_saque (usuario_id, conta_id, valor, descricao) 
                         VALUES (?, ?, ?, ?)";
            $stmt_saque = $conn->prepare($sql_saque);
            $stmt_saque->bind_param("iids", $usuario_id, $conta_id, $valor, $descricao);
            
            if ($stmt_saque->execute()) {
                // Solicitação registrada com sucesso
                $mensagem = "Solicitação de saque de R$ " . number_format($valor, 2, ',', '.') . " enviada com sucesso! Aguarde a aprovação do administrador.";
                $tipo_alerta = "success";
                
                // Redirecionar para evitar reenvio do formulário
                header("Location: investidor.php?sucesso=1&msg=" . urlencode($mensagem));
                exit;
            } else {
                $mensagem = "Erro ao registrar solicitação de saque: " . $conn->error;
                $tipo_alerta = "danger";
            }
        }
    } else {
        $mensagem = "Conta inválida ou não pertence a você.";
        $tipo_alerta = "danger";
    }
}

// Buscar solicitações de saque pendentes do investidor
$sql_saques_pendentes = "SELECT * FROM solicitacoes_saque WHERE usuario_id = ? AND status = 'pendente' ORDER BY data_solicitacao DESC";
$stmt_saques = $conn->prepare($sql_saques_pendentes);
if ($stmt_saques) {
    $stmt_saques->bind_param("i", $usuario_id);
    $stmt_saques->execute();
    $result_saques = $stmt_saques->get_result();
    $saques_pendentes = [];

    if ($result_saques && $result_saques->num_rows > 0) {
        while ($saque = $result_saques->fetch_assoc()) {
            $saques_pendentes[] = $saque;
        }
    }
}

// Verificar e creditar automaticamente comissões de parcelas pagas recentemente
$creditar_comissoes = true; // Flag para habilitar/desabilitar o crédito automático
if ($creditar_comissoes && !empty($contas) && floatval($contas[0]['comissao']) > 0) {
    // Criar tabela temporária para controle de comissões, se não existir
    $verifica_tabela = $conn->query("SHOW TABLES LIKE 'controle_comissoes'");
    if ($verifica_tabela->num_rows === 0) {
        $sql_criar_tabela = "CREATE TABLE IF NOT EXISTS controle_comissoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parcela_id INT NOT NULL,
            usuario_id INT NOT NULL,
            conta_id INT NOT NULL,
            valor_comissao DECIMAL(10,2) NOT NULL,
            data_processamento DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (parcela_id, usuario_id)
        )";
        $conn->query($sql_criar_tabela);
    }
    
    // Buscar parcelas pagas que ainda não tiveram comissão creditada
    $sql_parcelas = "SELECT 
                        p.id as parcela_id,
                        p.emprestimo_id,
                        p.numero,
                        p.valor_pago,
                        p.data_pagamento,
                        e.cliente_id,
                        c.nome as cliente_nome
                     FROM 
                        parcelas p
                     INNER JOIN 
                        emprestimos e ON p.emprestimo_id = e.id
                     INNER JOIN 
                        clientes c ON e.cliente_id = c.id
                     LEFT JOIN 
                        controle_comissoes cc ON p.id = cc.parcela_id AND cc.usuario_id = ?
                     WHERE 
                        e.investidor_id = ? 
                        AND p.status = 'pago'
                        AND cc.id IS NULL
                     ORDER BY 
                        p.data_pagamento DESC";
    
    $stmt_parcelas = $conn->prepare($sql_parcelas);
    $stmt_parcelas->bind_param("ii", $usuario_id, $usuario_id);
    $stmt_parcelas->execute();
    $result_parcelas = $stmt_parcelas->get_result();
    
    if ($result_parcelas && $result_parcelas->num_rows > 0) {
        $conn->begin_transaction();
        
        try {
            $nova_comissao_total = 0;
            $parcelas_processadas = 0;
            
            while ($parcela = $result_parcelas->fetch_assoc()) {
                $percentual_comissao = floatval($contas[0]['comissao']);
                $valor_pago = floatval($parcela['valor_pago']);
                $valor_comissao = $valor_pago * ($percentual_comissao / 100);
                
                // Registrar na tabela de controle
                $stmt_controle = $conn->prepare("INSERT INTO controle_comissoes 
                                               (parcela_id, usuario_id, conta_id, valor_comissao) 
                                               VALUES (?, ?, ?, ?)");
                $stmt_controle->bind_param("iiid", 
                                          $parcela['parcela_id'], 
                                          $usuario_id, 
                                          $contas[0]['id'], 
                                          $valor_comissao);
                
                if (!$stmt_controle->execute()) {
                    throw new Exception("Erro ao registrar controle de comissão: " . $conn->error);
                }
                
                // Adicionar valor como entrada na conta
                $descricao = "Comissão - Parcela #{$parcela['numero']} do empréstimo #{$parcela['emprestimo_id']} ({$percentual_comissao}%)";
                
                $stmt_movimentacao = $conn->prepare("INSERT INTO movimentacoes_contas 
                                                   (conta_id, tipo, valor, descricao, data_movimentacao) 
                                                   VALUES (?, 'entrada', ?, ?, NOW())");
                $stmt_movimentacao->bind_param("ids", 
                                              $contas[0]['id'], 
                                              $valor_comissao, 
                                              $descricao);
                
                if (!$stmt_movimentacao->execute()) {
                    throw new Exception("Erro ao adicionar comissão na conta: " . $conn->error);
                }
                
                $nova_comissao_total += $valor_comissao;
                $parcelas_processadas++;
            }
            
            $conn->commit();
            
            if ($parcelas_processadas > 0) {
                $mensagem_comissao = "Foram creditadas comissões de R$ " . number_format($nova_comissao_total, 2, ',', '.') . 
                                   " referentes a {$parcelas_processadas} parcelas pagas recentemente.";
                
                if (!isset($mensagem)) {
                    $mensagem = $mensagem_comissao;
                    $tipo_alerta = "success";
                }
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            // Apenas log do erro, não mostrar ao usuário para não confundi-lo
            error_log("Erro ao processar comissões: " . $e->getMessage());
        }
    }
}

// Processar mensagens vindas por GET
if (isset($_GET['sucesso']) && isset($_GET['msg'])) {
    $mensagem = $_GET['msg'];
    $tipo_alerta = ($_GET['sucesso'] == '1') ? "success" : "danger";
}

// Certifique-se de liberar o buffer no final do arquivo
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard do Investidor</h2>
        <?php if (count($contas) > 0): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#aporteModal">
                <i class="bi bi-plus-circle"></i> Realizar Aporte
            </button>
        <?php endif; ?>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['acesso_negado']) && $_SESSION['acesso_negado']): ?>
        <div class="alert alert-danger alert-dismissible fade show border border-danger border-3" role="alert">
            <div class="d-flex align-items-center">
                <div class="fs-1 me-3"><i class="bi bi-shield-exclamation"></i></div>
                <div>
                    <h4 class="alert-heading">Acesso Negado!</h4>
                    <p class="mb-0"><?= htmlspecialchars($_SESSION['acesso_negado_mensagem']) ?></p>
                    <?php if(isset($_SESSION['pagina_tentativa'])): ?>
                    <small class="d-block mt-2">Tentativa de acesso a: <?= htmlspecialchars($_SESSION['pagina_tentativa']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="<?= isset($_SESSION['acesso_negado']) ? '$_SESSION[\'acesso_negado\'] = false;' : '' ?>"></button>
        </div>
        <?php 
        // Limpar a mensagem após exibição
        $_SESSION['acesso_negado'] = false;
        ?>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_negado' && !isset($_SESSION['acesso_negado'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Acesso Negado!</strong> Você não tem permissão para acessar a área administrativa.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Cards Resumo e Conta Unificados -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-wallet2 me-2"></i>Minha Conta</h5>
        </div>
        <div class="card-body">
            <?php if (empty($contas)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Você não possui uma conta ativa. Entre em contato com o administrador para criar sua conta de investimento.
                </div>
            <?php else: 
                // Como agora é apenas uma conta por investidor, pegamos a primeira (e única)
                $conta = $contas[0]; 
            ?>
                <div class="row g-4">
                    <!-- Informações da Conta -->
                    <div class="col-lg-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0">Minha Conta de Investimento</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text text-muted"><?= htmlspecialchars(isset($conta['descricao']) ? $conta['descricao'] : 'Sem descrição') ?></p>
                                
                                <div class="mt-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Saldo Atual:</span>
                                        <span class="fw-bold <?= $conta['saldo_atual'] < 0 ? 'text-danger' : 'text-success' ?>">
                                            R$ <?= number_format($conta['saldo_atual'], 2, ',', '.') ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Sua Comissão:</span>
                                        <span class="fw-bold"><?= number_format($conta['comissao'], 2, ',', '.') ?>%</span>
                                    </div>
                                    <small class="text-muted d-block text-end">(Administrador: <?= number_format(100 - $conta['comissao'], 2, ',', '.') ?>%)</small>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="button" class="btn btn-primary flex-fill" 
                                            data-bs-toggle="modal" data-bs-target="#aporteModal">
                                        <i class="bi bi-plus-circle me-1"></i>Realizar Aporte
                                    </button>
                                    <button type="button" class="btn btn-success flex-fill" 
                                            data-bs-toggle="modal" data-bs-target="#saqueModal"
                                            <?= $conta['saldo_atual'] <= 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-cash-coin me-1"></i>Solicitar Saque
                                    </button>
                                </div>
                                
                                <div class="mt-2">
                                    <a href="configuracoes/movimentacoes.php?conta_id=<?= $conta['id'] ?>" class="btn btn-outline-info w-100">
                                        <i class="bi bi-list-ul me-1"></i>Movimentações
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cards de Resumo -->
                    <div class="col-lg-6">
                        <div class="row row-cols-1 row-cols-md-2 g-3 h-100">
                            <div class="col">
                                <div class="card shadow-sm border-primary h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted">Total Emprestado</h6>
                                        <h4 class="card-text text-primary">R$ <?= number_format($total_emprestado, 2, ',', '.') ?></h4>
                                        <small class="text-muted">Em <?= count($emprestimos) ?> empréstimo(s) ativo(s)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card shadow-sm border-info h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted">Valor Recebido</h6>
                                        <h4 class="card-text text-info">R$ <?= number_format($total_recebido, 2, ',', '.') ?></h4>
                                        <small class="text-muted">De parcelas pagas</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card shadow-sm border-warning h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted">Sua Comissão</h6>
                                        <h4 class="card-text text-warning">R$ <?= number_format($comissoes_calculadas, 2, ',', '.') ?></h4>
                                        <small class="text-muted">Baseado nas suas taxas</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card shadow-sm border-success h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted">Parcelas Pagas</h6>
                                        <h4 class="card-text text-success"><?= $total_parcelas_pagas ?></h4>
                                        <small class="text-muted">De todos empréstimos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Empréstimos Ativos -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-cash-stack me-2"></i>Empréstimos Ativos</h5>
        </div>
        <div class="card-body">
            <?php if (empty($emprestimos)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Você não possui empréstimos ativos.
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($emprestimos as $emp): 
                        // Calcular valores usando as chaves disponíveis
                        $percentual_comissao = !empty($contas) ? floatval($contas[0]['comissao']) : 0;
                        $valor_recebido = floatval($emp['total_recebido']);
                        $comissao = $valor_recebido * ($percentual_comissao / 100);
                        
                        // Calcular progresso de pagamento
                        $parcelas_pagas = intval($emp['parcelas_pagas']);
                        $total_parcelas = intval($emp['total_parcelas']);
                        $percentual_pago = ($parcelas_pagas / $total_parcelas) * 100;
                    ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?= htmlspecialchars($emp['cliente_nome']) ?></h6>
                                        <span class="badge bg-light text-dark"><?= $parcelas_pagas ?>/<?= $total_parcelas ?> parcelas</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Valor emprestado:</span>
                                            <span class="fw-bold">R$ <?= number_format($emp['valor_emprestado'], 2, ',', '.') ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Taxa de juros:</span>
                                            <span><?= number_format($emp['juros_percentual'], 2, ',', '.') ?>%</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Valor da parcela:</span>
                                            <span>R$ <?= number_format($emp['valor_parcela'], 2, ',', '.') ?></span>
                                        </div>
                                    </div>
                                    <div class="mb-3 pt-2 border-top">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Valor recebido:</span>
                                            <span class="text-info fw-bold">R$ <?= number_format($valor_recebido, 2, ',', '.') ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Sua comissão:</span>
                                            <span class="text-warning fw-bold">R$ <?= number_format($comissao, 2, ',', '.') ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Progresso do empréstimo -->
                                    <div class="mt-3">
                                        <span class="text-muted small">Progresso de pagamento:</span>
                                        <div class="progress mt-1" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                style="width: <?= $percentual_pago ?>%;" 
                                                aria-valuenow="<?= $percentual_pago ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span class="text-muted small"><?= $parcelas_pagas ?> pagas</span>
                                            <span class="text-muted small"><?= number_format($percentual_pago, 0) ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Últimas Movimentações -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-activity me-2"></i>Últimas Movimentações</h5>
        </div>
        <div class="card-body">
            <?php if (empty($movimentacoes)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Nenhuma movimentação encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Conta</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></td>
                                    <td><?= htmlspecialchars($mov['conta_nome']) ?></td>
                                    <td>
                                        <span class="badge <?= $mov['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $mov['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?>
                                        </span>
                                    </td>
                                    <td>R$ <?= number_format($mov['valor'], 2, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($mov['descricao']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Solicitações de Saque Pendentes -->
    <?php if (!empty($saques_pendentes)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-hourglass-split me-2"></i>Solicitações de Saque Pendentes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Data Solicitação</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saques_pendentes as $saque): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($saque['data_solicitacao'])) ?></td>
                                <td>R$ <?= number_format($saque['valor'], 2, ',', '.') ?></td>
                                <td>
                                    <span class="badge bg-warning">Pendente</span>
                                </td>
                                <td><?= htmlspecialchars($saque['descricao'] ?: 'Sem descrição') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="alert alert-info small mt-3 mb-0">
                <i class="bi bi-info-circle me-2"></i>
                As solicitações de saque são analisadas pelo administrador em até 2 dias úteis. 
                Ao ser aprovada, o valor será transferido para sua conta bancária cadastrada.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detalhes de Empréstimos -->
    <?php if (count($emprestimos) > 0): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Detalhes dos Empréstimos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Valor Emprestado</th>
                            <th>Parcelas</th>
                            <th>Progresso</th>
                            <th>Recebido</th>
                            <th>Comissão</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emprestimos as $emp): 
                            // Calcular comissão
                            $percentual_comissao = !empty($contas) ? floatval($contas[0]['comissao']) : 0;
                            $valor_recebido = floatval($emp['total_recebido']);
                            $comissao = $valor_recebido * ($percentual_comissao / 100);
                            
                            // Calcular progresso
                            $parcelas_pagas = intval($emp['parcelas_pagas']);
                            $total_parcelas = intval($emp['total_parcelas']);
                            $progresso = $total_parcelas > 0 ? ($parcelas_pagas / $total_parcelas) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($emp['cliente_nome']) ?></td>
                            <td>R$ <?= number_format($emp['valor_emprestado'], 2, ',', '.') ?></td>
                            <td><?= $parcelas_pagas ?> / <?= $total_parcelas ?></td>
                            <td>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progresso ?>%;" 
                                         aria-valuenow="<?= $progresso ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                            <td>R$ <?= number_format($valor_recebido, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($comissao, 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th>Totais</th>
                            <th>R$ <?= number_format($total_emprestado, 2, ',', '.') ?></th>
                            <th><?= $total_parcelas_pagas ?> parcelas</th>
                            <th></th>
                            <th>R$ <?= number_format($total_recebido, 2, ',', '.') ?></th>
                            <th>R$ <?= number_format($comissoes_calculadas, 2, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Explicação do Cálculo de Comissões -->
            <div class="card bg-light mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle-fill me-2 text-primary"></i>Como são calculadas as comissões</h6>
                    <p class="card-text small">
                        As comissões são calculadas com base no percentual de <?= !empty($contas) ? number_format($contas[0]['comissao'], 2, ',', '.') : '0,00' ?>% 
                        definido em sua conta de investimento. Esse percentual é aplicado sobre o valor total recebido de parcelas pagas.
                    </p>
                    <ul class="mb-0 small">
                        <li>Total recebido de parcelas: R$ <?= number_format($total_recebido, 2, ',', '.') ?></li>
                        <li>Percentual de comissão: <?= !empty($contas) ? number_format($contas[0]['comissao'], 2, ',', '.') : '0,00' ?>%</li>
                        <li>Comissão calculada: R$ <?= number_format($comissoes_calculadas, 2, ',', '.') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para Realizar Aporte -->
<div class="modal fade" id="aporteModal" tabindex="-1" aria-labelledby="aporteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aporteModalLabel">Realizar Aporte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?php if (count($contas) > 0): ?>
                        <input type="hidden" name="conta_id" value="<?= $contas[0]['id'] ?>">
                        
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            O aporte será realizado na sua conta de investimento
                        </div>
                        
                        <div class="mb-3">
                            <label for="valor_aporte" class="form-label">Valor do Aporte (R$)</label>
                            <input type="text" class="form-control" id="valor_aporte" name="valor_aporte" required placeholder="0,00">
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="2" placeholder="Aporte de capital"></textarea>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Você não possui uma conta ativa para realizar aportes. Entre em contato com o administrador.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <?php if (count($contas) > 0): ?>
                        <button type="submit" name="realizar_aporte" class="btn btn-primary">Realizar Aporte</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Solicitar Saque -->
<div class="modal fade" id="saqueModal" tabindex="-1" aria-labelledby="saqueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="saqueModalLabel">Solicitar Saque</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?php if (count($contas) > 0): ?>
                        <input type="hidden" name="conta_id" value="<?= $contas[0]['id'] ?>">
                        
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <p class="mb-0">O saque será solicitado da sua conta de investimento.</p>
                            <p class="mb-0"><strong>Saldo Atual:</strong> R$ <?= number_format($contas[0]['saldo_atual'], 2, ',', '.') ?></p>
                        </div>

                        <?php if (!empty($saques_pendentes)): ?>
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Você já possui <?= count($saques_pendentes) ?> solicitação(ões) de saque pendente(s). 
                                Novas solicitações serão analisadas na ordem em que foram recebidas.
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="valor_saque" class="form-label">Valor do Saque (R$)</label>
                            <input type="text" class="form-control" id="valor_saque" name="valor_saque" required placeholder="0,00">
                            <div class="form-text">O valor máximo disponível para saque é de R$ <?= number_format($contas[0]['saldo_atual'], 2, ',', '.') ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="descricao_saque" class="form-label">Motivo do Saque (opcional)</label>
                            <textarea class="form-control" id="descricao_saque" name="descricao_saque" rows="2" placeholder="Informe o motivo do saque (opcional)"></textarea>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Você não possui uma conta ativa para solicitar saques. Entre em contato com o administrador.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <?php if (count($contas) > 0): ?>
                        <button type="submit" name="solicitar_saque" class="btn btn-success">Solicitar Saque</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Formatação para campo monetário (aporte)
    document.getElementById('valor_aporte').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value === '') {
            e.target.value = '';
            return;
        }
        value = (parseFloat(value) / 100).toFixed(2).replace('.', ',');
        e.target.value = value;
    });
    
    // Formatação para campo monetário (saque)
    document.getElementById('valor_saque').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value === '') {
            e.target.value = '';
            return;
        }
        value = (parseFloat(value) / 100).toFixed(2).replace('.', ',');
        e.target.value = value;
        
        // Verificar se o valor é maior que o saldo
        const saldoAtual = <?= !empty($contas) ? $contas[0]['saldo_atual'] : 0 ?>;
        const valorSaque = parseFloat(value.replace(',', '.'));
        
        if (valorSaque > saldoAtual) {
            this.classList.add('is-invalid');
            if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                const feedback = document.createElement('div');
                feedback.classList.add('invalid-feedback');
                feedback.textContent = 'O valor do saque não pode ser maior que o saldo disponível';
                this.parentNode.appendChild(feedback);
            }
        } else {
            this.classList.remove('is-invalid');
            if (this.nextElementSibling && this.nextElementSibling.classList.contains('invalid-feedback')) {
                this.nextElementSibling.remove();
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; 

// Liberar o buffer de saída no final
ob_end_flush();
?> 