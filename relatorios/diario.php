<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/funcoes_data.php';
require_once __DIR__ . '/../includes/funcoes_moeda.php';

// Definir data do relatório (hoje por padrão ou selecionada pelo usuário)
$data_relatorio = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$data_formatada = formatarData($data_relatorio);

// Verificar se a data é válida
if (!validarData($data_relatorio)) {
    $data_relatorio = date('Y-m-d');
    $data_formatada = formatarData($data_relatorio);
}

// Datas para navegação
$dia_anterior = date('Y-m-d', strtotime($data_relatorio . ' -1 day'));
$dia_seguinte = date('Y-m-d', strtotime($data_relatorio . ' +1 day'));
$hoje = date('Y-m-d');

// Inicializar variáveis para estatísticas
$total_emprestimos_concedidos = 0;
$valor_emprestimos_concedidos = 0;
$total_pagamentos_recebidos = 0;
$valor_pagamentos_recebidos = 0;
$total_parcelas_vencidas = 0;
$valor_parcelas_vencidas = 0;
$total_parcelas_atualizadas = 0;
$total_atrasadas = 0;

// Arrays para os dados das tabelas e gráficos
$emprestimos_dia = [];
$pagamentos_dia = [];
$parcelas_vencidas_dia = [];
$projecao_amanha = [];

// 1. Buscar empréstimos concedidos no dia
$sql_emprestimos = "
    SELECT e.id, e.cliente_id, c.nome as cliente_nome, e.valor_emprestado, e.data_inicio, e.juros_percentual
    FROM emprestimos e
    INNER JOIN clientes c ON e.cliente_id = c.id
    WHERE DATE(e.data_inicio) = ?
    ORDER BY e.data_inicio DESC
";
$stmt = $conn->prepare($sql_emprestimos);
$stmt->bind_param('s', $data_relatorio);
$stmt->execute();
$result_emprestimos = $stmt->get_result();

while ($emprestimo = $result_emprestimos->fetch_assoc()) {
    $emprestimos_dia[] = $emprestimo;
    $valor_emprestimos_concedidos += $emprestimo['valor_emprestado'];
}
$total_emprestimos_concedidos = count($emprestimos_dia);

// 2. Buscar pagamentos recebidos no dia
$sql_pagamentos = "
    SELECT p.id, p.emprestimo_id, p.parcela_id, p.valor, p.data_pagamento, p.forma_pagamento,
           c.nome as cliente_nome, c.id as cliente_id
    FROM pagamentos p
    INNER JOIN emprestimos e ON p.emprestimo_id = e.id
    INNER JOIN clientes c ON e.cliente_id = c.id
    WHERE DATE(p.data_pagamento) = ?
    ORDER BY p.data_pagamento DESC
";
$stmt = $conn->prepare($sql_pagamentos);
$stmt->bind_param('s', $data_relatorio);
$stmt->execute();
$result_pagamentos = $stmt->get_result();

while ($pagamento = $result_pagamentos->fetch_assoc()) {
    $pagamentos_dia[] = $pagamento;
    $valor_pagamentos_recebidos += $pagamento['valor'];
}
$total_pagamentos_recebidos = count($pagamentos_dia);

// 3. Buscar parcelas que venceram no dia
$sql_vencidas = "
    SELECT p.id, p.emprestimo_id, p.numero_parcela, p.valor, p.data_vencimento, p.status,
           c.nome as cliente_nome, c.id as cliente_id, e.valor_emprestado
    FROM parcelas p
    INNER JOIN emprestimos e ON p.emprestimo_id = e.id
    INNER JOIN clientes c ON e.cliente_id = c.id
    WHERE p.data_vencimento = ? AND p.status != 'pago'
    ORDER BY c.nome
";
$stmt = $conn->prepare($sql_vencidas);
$stmt->bind_param('s', $data_relatorio);
$stmt->execute();
$result_vencidas = $stmt->get_result();

while ($parcela = $result_vencidas->fetch_assoc()) {
    $parcelas_vencidas_dia[] = $parcela;
    $valor_parcelas_vencidas += $parcela['valor'];
}
$total_parcelas_vencidas = count($parcelas_vencidas_dia);

// 4. Buscar quantidade de parcelas atualizadas para "atrasado" no dia
$data_inicio = $data_relatorio . ' 00:00:00';
$data_fim = $data_relatorio . ' 23:59:59';

$sql_atualizadas = "
    SELECT COUNT(*) as total
    FROM log_verificacao_parcelas
    WHERE data_hora BETWEEN ? AND ? AND parcelas_atualizadas > 0
";
$stmt = $conn->prepare($sql_atualizadas);

if ($stmt) {
    $stmt->bind_param('ss', $data_inicio, $data_fim);
    $stmt->execute();
    $result_atualizadas = $stmt->get_result();
    $row = $result_atualizadas->fetch_assoc();
    $total_parcelas_atualizadas = $row ? $row['total'] : 0;
}

// 5. Buscar projeção para o dia seguinte
$sql_projecao = "
    SELECT p.id, p.emprestimo_id, p.numero_parcela, p.valor, p.data_vencimento, p.status,
           c.nome as cliente_nome, c.id as cliente_id, c.telefone
    FROM parcelas p
    INNER JOIN emprestimos e ON p.emprestimo_id = e.id
    INNER JOIN clientes c ON e.cliente_id = c.id
    WHERE p.data_vencimento = ? AND p.status != 'pago'
    ORDER BY c.nome
";
$stmt = $conn->prepare($sql_projecao);
$stmt->bind_param('s', $dia_seguinte);
$stmt->execute();
$result_projecao = $stmt->get_result();

$valor_projecao_amanha = 0;
while ($parcela = $result_projecao->fetch_assoc()) {
    $projecao_amanha[] = $parcela;
    $valor_projecao_amanha += $parcela['valor'];
}
$total_projecao_amanha = count($projecao_amanha);

// 6. Dados para gráfico dos últimos 7 dias
$dados_ultimos_dias = [];
for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime($data_relatorio . " -{$i} days"));
    $data_formatada_grafico = date('d/m', strtotime($data));
    
    // Empréstimos concedidos
    $sql = "SELECT SUM(valor_emprestado) as total FROM emprestimos WHERE DATE(data_inicio) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $emprestimos_valor = $row['total'] ? $row['total'] : 0;
    
    // Pagamentos recebidos
    $sql = "SELECT SUM(valor) as total FROM pagamentos WHERE DATE(data_pagamento) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $pagamentos_valor = $row['total'] ? $row['total'] : 0;
    
    $dados_ultimos_dias[] = [
        'data' => $data_formatada_grafico,
        'emprestimos' => $emprestimos_valor,
        'pagamentos' => $pagamentos_valor
    ];
}

// Calcular total de parcelas vencidas
$total_atrasadas = 0;
foreach ($parcelas_vencidas_dia as $parcela) {
    $total_atrasadas += $parcela['valor'];
}

// Atualizar status de parcelas vencidas
foreach ($parcelas_vencidas_dia as &$parcela) {
    if ($parcela['status'] == 'pendente') {
        $parcela['status'] = 'atrasado';
    }
}
unset($parcela);

// Título da página
$titulo_pagina = "Relatório Diário - " . $data_formatada;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo_pagina ?> - Sistema de Empréstimos</title>
    <?php require_once __DIR__ . '/../includes/head.php'; ?>
    <!-- Incluir Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables para tabelas avançadas -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <style>
        .report-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--bs-primary);
        }
        
        .card-indicator {
            width: 4px;
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
        }
        
        .card-indicator.positive {
            background-color: var(--bs-success);
        }
        
        .card-indicator.negative {
            background-color: var(--bs-danger);
        }
        
        .card-indicator.neutral {
            background-color: var(--bs-info);
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        .date-navigator {
            background-color: rgba(0,0,0,0.03);
            border-radius: 10px;
            padding: 10px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .container-fluid {
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h1 class="mb-1"><?= $titulo_pagina ?></h1>
                <p class="text-muted mb-0">Visão geral das operações financeiras do dia</p>
            </div>
            <div class="col-md-6">
                <div class="date-navigator d-flex justify-content-between align-items-center p-2 no-print">
                    <a href="?data=<?= $dia_anterior ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-chevron-left"></i> Dia Anterior
                    </a>
                    
                    <form method="GET" class="d-flex align-items-center">
                        <input type="date" name="data" value="<?= $data_relatorio ?>" class="form-control form-control-sm mx-2" onchange="this.form.submit()">
                    </form>
                    
                    <?php if ($data_relatorio < $hoje): ?>
                    <a href="?data=<?= $dia_seguinte ?>" class="btn btn-sm btn-outline-primary">
                        Próximo Dia <i class="bi bi-chevron-right"></i>
                    </a>
                    <?php else: ?>
                    <button class="btn btn-sm btn-outline-secondary" disabled>
                        Próximo Dia <i class="bi bi-chevron-right"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cards de Resumo -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card report-card h-100 position-relative">
                    <div class="card-indicator <?= $total_emprestimos_concedidos > 0 ? 'positive' : 'neutral' ?>"></div>
                    <div class="card-body">
                        <h6 class="card-title text-muted">Empréstimos Concedidos</h6>
                        <h3 class="mb-0"><?= $total_emprestimos_concedidos ?></h3>
                        <p class="card-text text-success mb-0">
                            R$ <?= number_format($valor_emprestimos_concedidos, 2, ',', '.') ?>
                        </p>
                        <small class="text-muted">Total emprestado no dia</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card report-card h-100 position-relative">
                    <div class="card-indicator <?= $total_pagamentos_recebidos > 0 ? 'positive' : 'neutral' ?>"></div>
                    <div class="card-body">
                        <h6 class="card-title text-muted">Pagamentos Recebidos</h6>
                        <h3 class="mb-0"><?= $total_pagamentos_recebidos ?></h3>
                        <p class="card-text text-success mb-0">
                            R$ <?= number_format($valor_pagamentos_recebidos, 2, ',', '.') ?>
                        </p>
                        <small class="text-muted">Total recebido no dia</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card report-card h-100 position-relative">
                    <div class="card-indicator <?= $total_parcelas_vencidas > 0 ? 'negative' : 'neutral' ?>"></div>
                    <div class="card-body">
                        <h6 class="card-title text-muted">Parcelas Vencidas</h6>
                        <h3 class="mb-0"><?= $total_parcelas_vencidas ?></h3>
                        <p class="card-text text-danger mb-0">
                            R$ <?= number_format($valor_parcelas_vencidas, 2, ',', '.') ?>
                        </p>
                        <small class="text-muted">Total vencido no dia</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card report-card h-100 position-relative">
                    <div class="card-indicator neutral"></div>
                    <div class="card-body">
                        <h6 class="card-title text-muted">Balanço do Dia</h6>
                        <h3 class="mb-0">
                            <?php 
                            $balanco = $valor_pagamentos_recebidos - $valor_emprestimos_concedidos;
                            echo $balanco >= 0 ? '+' : '';
                            echo 'R$ ' . number_format(abs($balanco), 2, ',', '.'); 
                            ?>
                        </h3>
                        <p class="card-text <?= $balanco >= 0 ? 'text-success' : 'text-danger' ?> mb-0">
                            <?= $balanco >= 0 ? 'Positivo' : 'Negativo' ?>
                        </p>
                        <small class="text-muted">Recebido - Emprestado</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Evolução dos Últimos 7 Dias</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="graficoEvolucao"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Distribuição do Dia</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="graficoPizza"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Abas para Conteúdo Detalhado -->
        <div class="card mb-4">
            <div class="card-header bg-light p-3">
                <ul class="nav nav-pills card-header-pills" id="abasDetalhes" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="emprestimos-tab" data-bs-toggle="tab" data-bs-target="#emprestimos" type="button" role="tab" aria-controls="emprestimos" aria-selected="true">
                            <i class="bi bi-cash"></i> Empréstimos (<?= $total_emprestimos_concedidos ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pagamentos-tab" data-bs-toggle="tab" data-bs-target="#pagamentos" type="button" role="tab" aria-controls="pagamentos" aria-selected="false">
                            <i class="bi bi-credit-card"></i> Pagamentos (<?= $total_pagamentos_recebidos ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="vencidas-tab" data-bs-toggle="tab" data-bs-target="#vencidas" type="button" role="tab" aria-controls="vencidas" aria-selected="false">
                            <i class="bi bi-exclamation-triangle"></i> Vencidas (<?= $total_parcelas_vencidas ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="projecao-tab" data-bs-toggle="tab" data-bs-target="#projecao" type="button" role="tab" aria-controls="projecao" aria-selected="false">
                            <i class="bi bi-graph-up-arrow"></i> Projeção (<?= $total_projecao_amanha ?>)
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="abasDetalhesConteudo">
                    <!-- Aba de Empréstimos -->
                    <div class="tab-pane fade show active" id="emprestimos" role="tabpanel" aria-labelledby="emprestimos-tab">
                        <?php if (empty($emprestimos_dia)): ?>
                            <div class="alert alert-info">
                                Nenhum empréstimo foi concedido em <?= $data_formatada ?>.
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Valor</th>
                                            <th>Taxa</th>
                                            <th>Hora</th>
                                            <th class="no-print">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($emprestimos_dia as $emp): ?>
                                            <tr>
                                                <td>#<?= $emp['id'] ?></td>
                                                <td><?= htmlspecialchars($emp['cliente_nome']) ?></td>
                                                <td>R$ <?= number_format($emp['valor_emprestado'], 2, ',', '.') ?></td>
                                                <td><?= number_format($emp['juros_percentual'], 2) ?>%</td>
                                                <td><?= date('H:i', strtotime($emp['data_inicio'])) ?></td>
                                                <td class="no-print">
                                                    <a href="<?= BASE_URL ?>emprestimos/visualizar.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Aba de Pagamentos -->
                    <div class="tab-pane fade" id="pagamentos" role="tabpanel" aria-labelledby="pagamentos-tab">
                        <?php if (empty($pagamentos_dia)): ?>
                            <div class="alert alert-info">
                                Nenhum pagamento foi recebido em <?= $data_formatada ?>.
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Empréstimo</th>
                                            <th>Parcela</th>
                                            <th>Valor</th>
                                            <th>Forma</th>
                                            <th>Hora</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pagamentos_dia as $pag): ?>
                                            <tr>
                                                <td>#<?= $pag['id'] ?></td>
                                                <td><?= htmlspecialchars($pag['cliente_nome']) ?></td>
                                                <td>#<?= $pag['emprestimo_id'] ?></td>
                                                <td><?= $pag['parcela_id'] ? "#" . $pag['parcela_id'] : 'N/A' ?></td>
                                                <td>R$ <?= number_format($pag['valor'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php
                                                    $forma = $pag['forma_pagamento'];
                                                    $cor = '';
                                                    $icone = '';
                                                    
                                                    switch($forma) {
                                                        case 'dinheiro':
                                                            $cor = 'success';
                                                            $icone = 'cash';
                                                            break;
                                                        case 'pix':
                                                            $cor = 'info';
                                                            $icone = 'x-diamond';
                                                            break;
                                                        case 'cartao':
                                                            $cor = 'primary';
                                                            $icone = 'credit-card';
                                                            break;
                                                        default:
                                                            $cor = 'secondary';
                                                            $icone = 'credit-card';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $cor ?>">
                                                        <i class="bi bi-<?= $icone ?>"></i> <?= ucfirst($forma) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('H:i', strtotime($pag['data_pagamento'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Aba de Parcelas Vencidas -->
                    <div class="tab-pane fade" id="vencidas" role="tabpanel" aria-labelledby="vencidas-tab">
                        <?php if (empty($parcelas_vencidas_dia)): ?>
                            <div class="alert alert-success">
                                Não há parcelas vencidas em <?= $data_formatada ?>.
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Parcela</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th class="no-print">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($parcelas_vencidas_dia as $pv): ?>
                                            <tr>
                                                <td>#<?= $pv['emprestimo_id'] ?></td>
                                                <td><?= htmlspecialchars($pv['cliente_nome']) ?></td>
                                                <td><?= $pv['numero_parcela'] ?></td>
                                                <td>R$ <?= number_format($pv['valor'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php
                                                    $status = $pv['status'];
                                                    $cor = '';
                                                    
                                                    switch($status) {
                                                        case 'pendente':
                                                            $cor = 'warning';
                                                            break;
                                                        case 'atrasado':
                                                            $cor = 'danger';
                                                            break;
                                                        case 'parcial':
                                                            $cor = 'info';
                                                            break;
                                                        default:
                                                            $cor = 'secondary';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $cor ?>"><?= ucfirst($status) ?></span>
                                                </td>
                                                <td class="no-print">
                                                    <a href="<?= BASE_URL ?>emprestimos/registrar_pagamento.php?emprestimo=<?= $pv['emprestimo_id'] ?>&parcela=<?= $pv['numero_parcela'] ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-cash"></i> Pagar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Aba de Projeção -->
                    <div class="tab-pane fade" id="projecao" role="tabpanel" aria-labelledby="projecao-tab">
                        <h5 class="card-title">Projeção para <?= formatarData($dia_seguinte) ?></h5>
                        <?php if (empty($projecao_amanha)): ?>
                            <div class="alert alert-info">
                                Não há parcelas previstas para vencer em <?= formatarData($dia_seguinte) ?>.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-primary">
                                <i class="bi bi-info-circle"></i> Valor total previsto para recebimento: <strong>R$ <?= number_format($valor_projecao_amanha, 2, ',', '.') ?></strong>
                            </div>
                            <div class="table-container">
                                <table class="table table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Empréstimo</th>
                                            <th>Parcela</th>
                                            <th>Valor</th>
                                            <th>Telefone</th>
                                            <th class="no-print">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projecao_amanha as $proj): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($proj['cliente_nome']) ?></td>
                                                <td>#<?= $proj['emprestimo_id'] ?></td>
                                                <td><?= $proj['numero_parcela'] ?></td>
                                                <td>R$ <?= number_format($proj['valor'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php
                                                    $tel = preg_replace('/[^0-9]/', '', $proj['telefone']);
                                                    echo '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                                                    ?>
                                                </td>
                                                <td class="no-print">
                                                    <a href="https://wa.me/55<?= $tel ?>?text=Olá%20<?= urlencode($proj['cliente_nome']) ?>,%20lembrando%20que%20amanhã%20vence%20sua%20parcela%20no%20valor%20de%20R$%20<?= number_format($proj['valor'], 2, ',', '.') ?>." 
                                                       target="_blank" class="btn btn-sm btn-success">
                                                        <i class="bi bi-whatsapp"></i> Lembrar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="d-flex justify-content-end mt-4 no-print">
            <button type="button" class="btn btn-outline-secondary me-2" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir Relatório
            </button>
            <button type="button" class="btn btn-outline-primary me-2" id="exportarPDF">
                <i class="bi bi-file-pdf"></i> Exportar PDF
            </button>
            <button type="button" class="btn btn-outline-success" id="exportarExcel">
                <i class="bi bi-file-excel"></i> Exportar Excel
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Dados para o gráfico de evolução
        const ctx1 = document.getElementById('graficoEvolucao').getContext('2d');
        const graficoEvolucao = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($dados_ultimos_dias as $dia): ?>
                        '<?= $dia['data'] ?>',
                    <?php endforeach; ?>
                ],
                datasets: [
                    {
                        label: 'Empréstimos',
                        data: [
                            <?php foreach ($dados_ultimos_dias as $dia): ?>
                                <?= $dia['emprestimos'] ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pagamentos',
                        data: [
                            <?php foreach ($dados_ultimos_dias as $dia): ?>
                                <?= $dia['pagamentos'] ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Empréstimos vs. Pagamentos - Últimos 7 dias'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de pizza para distribuição do dia
        const ctx2 = document.getElementById('graficoPizza').getContext('2d');
        const graficoPizza = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Empréstimos', 'Pagamentos', 'Vencidas'],
                datasets: [{
                    label: 'Valor (R$)',
                    data: [
                        <?= $valor_emprestimos_concedidos ?>,
                        <?= $valor_pagamentos_recebidos ?>,
                        <?= $valor_parcelas_vencidas ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribuição Financeira do Dia'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        });

        // Inicializar DataTables
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
        });

        // Botão para exportar para Excel
        document.getElementById('exportarExcel').addEventListener('click', function() {
            window.location.href = `<?= BASE_URL ?>relatorios/exportar_excel.php?tipo=diario&data=<?= $data_relatorio ?>`;
            // Nota: Esta funcionalidade exigirá a criação do arquivo exportar_excel.php
        });

        // Botão para exportar para PDF
        document.getElementById('exportarPDF').addEventListener('click', function() {
            window.location.href = `<?= BASE_URL ?>relatorios/exportar_pdf.php?tipo=diario&data=<?= $data_relatorio ?>`;
            // Nota: Esta funcionalidade exigirá a criação do arquivo exportar_pdf.php
        });
    });
    </script>
</body>
</html> 