<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/head.php';

// Verifica se o usuário tem permissão adequada
$nivel_usuario = $_SESSION['nivel_autoridade'] ?? '';
if ($nivel_usuario !== 'administrador' && $nivel_usuario !== 'superadmin') {
    echo '<div class="container py-4"><div class="alert alert-danger">Você não tem permissão para acessar esta página.</div></div>';
    exit;
}

// Opções de filtro
$filtro_investidor = isset($_GET['investidor']) ? intval($_GET['investidor']) : 0;
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '30';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

// Determinar datas com base no período
$data_fim = date('Y-m-d');
switch ($filtro_periodo) {
    case '7':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30':
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90':
        $data_inicio = date('Y-m-d', strtotime('-90 days'));
        break;
    case '180':
        $data_inicio = date('Y-m-d', strtotime('-180 days'));
        break;
    case '365':
        $data_inicio = date('Y-m-d', strtotime('-1 year'));
        break;
    case 'all':
        $data_inicio = '2000-01-01'; // Data no passado para pegar todos
        break;
    default:
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
}

// Buscar lista de investidores para o filtro
$sql_investidores = "SELECT id, nome FROM usuarios WHERE tipo = 'investidor' ORDER BY nome ASC";
$result_investidores = $conn->query($sql_investidores);
$investidores = [];

if ($result_investidores && $result_investidores->num_rows > 0) {
    while ($row = $result_investidores->fetch_assoc()) {
        $investidores[] = $row;
    }
}

// Consulta principal para clientes e investidores
$sql = "SELECT 
            u.id AS investidor_id,
            u.nome AS investidor_nome,
            COUNT(DISTINCT c.id) AS total_clientes,
            COUNT(DISTINCT e.id) AS total_emprestimos,
            COALESCE(SUM(e.valor_emprestado), 0) AS total_valor_emprestado,
            (
                SELECT COALESCE(SUM(p.valor_pago), 0)
                FROM parcelas p
                JOIN emprestimos e2 ON p.emprestimo_id = e2.id
                JOIN clientes c2 ON e2.cliente_id = c2.id
                WHERE c2.indicacao = u.id
                AND p.data_pagamento BETWEEN ? AND ?
            ) AS total_valor_recebido,
            (
                SELECT COALESCE(SUM(p.valor), 0) - COALESCE(SUM(p.valor_pago), 0)
                FROM parcelas p
                JOIN emprestimos e2 ON p.emprestimo_id = e2.id
                JOIN clientes c2 ON e2.cliente_id = c2.id
                WHERE c2.indicacao = u.id
                AND p.status IN ('pendente', 'atrasado', 'parcial')
            ) AS total_pendente,
            (
                SELECT COUNT(p.id)
                FROM parcelas p
                JOIN emprestimos e2 ON p.emprestimo_id = e2.id
                JOIN clientes c2 ON e2.cliente_id = c2.id
                WHERE c2.indicacao = u.id
                AND p.status IN ('pendente', 'atrasado')
                AND p.vencimento < CURRENT_DATE()
            ) AS total_parcelas_atrasadas
        FROM 
            usuarios u
            LEFT JOIN clientes c ON u.id = c.indicacao
            LEFT JOIN emprestimos e ON c.id = e.cliente_id AND c.indicacao = u.id
        WHERE 
            u.tipo = 'investidor'";

// Adicionar filtro por investidor específico se selecionado
if ($filtro_investidor > 0) {
    $sql .= " AND u.id = $filtro_investidor";
}

$sql .= " GROUP BY u.id";

// Adicionar filtros de status
if ($filtro_status === 'inadimplentes') {
    $sql .= " HAVING total_parcelas_atrasadas > 0";
} elseif ($filtro_status === 'sem_clientes') {
    $sql .= " HAVING total_clientes = 0";
} elseif ($filtro_status === 'com_emprestimos') {
    $sql .= " HAVING total_emprestimos > 0";
}

$sql .= " ORDER BY u.nome ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$investidores_dados = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $investidores_dados[] = $row;
    }
}

// Calcular totais gerais
$total_geral_clientes = 0;
$total_geral_emprestimos = 0;
$total_geral_emprestado = 0;
$total_geral_recebido = 0;
$total_geral_pendente = 0;
$total_geral_atrasadas = 0;

foreach ($investidores_dados as $investidor) {
    $total_geral_clientes += $investidor['total_clientes'];
    $total_geral_emprestimos += $investidor['total_emprestimos'];
    $total_geral_emprestado += $investidor['total_valor_emprestado'];
    $total_geral_recebido += $investidor['total_valor_recebido'];
    $total_geral_pendente += $investidor['total_pendente'];
    $total_geral_atrasadas += $investidor['total_parcelas_atrasadas'];
}

// Consulta para detalhes dos clientes por investidor (usado quando um investidor específico é selecionado)
$clientes_investidor = [];
if ($filtro_investidor > 0) {
    $sql_clientes = "SELECT
                     c.id AS cliente_id,
                     c.nome AS cliente_nome,
                     c.telefone AS cliente_telefone,
                     COUNT(e.id) AS emprestimos_ativos,
                     COALESCE(SUM(e.valor_emprestado), 0) AS valor_emprestado,
                     (
                         SELECT COALESCE(SUM(p.valor_pago), 0)
                         FROM parcelas p
                         JOIN emprestimos e2 ON p.emprestimo_id = e2.id
                         WHERE e2.cliente_id = c.id
                         AND c.indicacao = ?
                         AND p.data_pagamento BETWEEN ? AND ?
                     ) AS valor_pago,
                     (
                         SELECT COALESCE(SUM(p.valor), 0) - COALESCE(SUM(p.valor_pago), 0)
                         FROM parcelas p
                         JOIN emprestimos e2 ON p.emprestimo_id = e2.id
                         WHERE e2.cliente_id = c.id
                         AND c.indicacao = ?
                         AND p.status IN ('pendente', 'atrasado', 'parcial')
                     ) AS valor_pendente,
                     (
                         SELECT COUNT(p.id)
                         FROM parcelas p
                         JOIN emprestimos e2 ON p.emprestimo_id = e2.id
                         WHERE e2.cliente_id = c.id
                         AND c.indicacao = ?
                         AND p.status IN ('pendente', 'atrasado')
                         AND p.vencimento < CURRENT_DATE()
                     ) AS parcelas_atrasadas
                 FROM
                     clientes c
                     LEFT JOIN emprestimos e ON c.id = e.cliente_id
                 WHERE
                     c.indicacao = ?
                 GROUP BY
                     c.id
                 ORDER BY
                     c.nome ASC";
    
    $stmt_clientes = $conn->prepare($sql_clientes);
    $stmt_clientes->bind_param("isiii", $filtro_investidor, $data_inicio, $data_fim, $filtro_investidor, $filtro_investidor, $filtro_investidor);
    $stmt_clientes->execute();
    $result_clientes = $stmt_clientes->get_result();
    
    if ($result_clientes && $result_clientes->num_rows > 0) {
        while ($row = $result_clientes->fetch_assoc()) {
            $clientes_investidor[] = $row;
        }
    }
}

// Nome do investidor selecionado para exibição
$investidor_selecionado_nome = '';
if ($filtro_investidor > 0) {
    foreach ($investidores as $inv) {
        if ($inv['id'] == $filtro_investidor) {
            $investidor_selecionado_nome = $inv['nome'];
            break;
        }
    }
}
?>

<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?= $filtro_investidor > 0 ? "Clientes do Investidor: $investidor_selecionado_nome" : "Relatório de Investidores" ?></h1>
            <div class="btn-group">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
                <button type="button" class="btn btn-outline-success" onclick="exportarExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="exportarPDF()">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </button>
            </div>
        </div>
        
        <!-- Resumo -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Emprestado</h5>
                        <h3 class="mb-0">R$ <?= number_format($total_geral_emprestado, 2, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Recebido</h5>
                        <h3 class="mb-0">R$ <?= number_format($total_geral_recebido, 2, ',', '.') ?></h3>
                        <small>No período selecionado</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Total Pendente</h5>
                        <h3 class="mb-0">R$ <?= number_format($total_geral_pendente, 2, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Parcelas Atrasadas</h5>
                        <h3 class="mb-0"><?= $total_geral_atrasadas ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="investidor" class="form-label">Investidor</label>
                        <select name="investidor" id="investidor" class="form-select">
                            <option value="0" <?= $filtro_investidor === 0 ? 'selected' : '' ?>>Todos os Investidores</option>
                            <?php foreach ($investidores as $investidor): ?>
                                <option value="<?= $investidor['id'] ?>" <?= $filtro_investidor === $investidor['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($investidor['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="periodo" class="form-label">Período de Recebimentos</label>
                        <select name="periodo" id="periodo" class="form-select">
                            <option value="7" <?= $filtro_periodo === '7' ? 'selected' : '' ?>>Últimos 7 dias</option>
                            <option value="30" <?= $filtro_periodo === '30' ? 'selected' : '' ?>>Últimos 30 dias</option>
                            <option value="90" <?= $filtro_periodo === '90' ? 'selected' : '' ?>>Últimos 90 dias</option>
                            <option value="180" <?= $filtro_periodo === '180' ? 'selected' : '' ?>>Últimos 180 dias</option>
                            <option value="365" <?= $filtro_periodo === '365' ? 'selected' : '' ?>>Último ano</option>
                            <option value="all" <?= $filtro_periodo === 'all' ? 'selected' : '' ?>>Todo o período</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="" <?= $filtro_status === '' ? 'selected' : '' ?>>Todos</option>
                            <option value="inadimplentes" <?= $filtro_status === 'inadimplentes' ? 'selected' : '' ?>>Com Inadimplentes</option>
                            <option value="sem_clientes" <?= $filtro_status === 'sem_clientes' ? 'selected' : '' ?>>Sem Clientes</option>
                            <option value="com_emprestimos" <?= $filtro_status === 'com_emprestimos' ? 'selected' : '' ?>>Com Empréstimos Ativos</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter me-1"></i>Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($filtro_investidor === 0): ?>
            <!-- Tabela de Resumo por Investidor -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($investidores_dados) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Investidor</th>
                                        <th class="text-center">Clientes</th>
                                        <th class="text-center">Empréstimos</th>
                                        <th class="text-end">Valor Emprestado</th>
                                        <th class="text-end">Valor Recebido</th>
                                        <th class="text-end">Valor Pendente</th>
                                        <th class="text-center">Parcelas Atrasadas</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($investidores_dados as $investidor): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($investidor['investidor_nome']) ?></td>
                                        <td class="text-center"><?= $investidor['total_clientes'] ?></td>
                                        <td class="text-center"><?= $investidor['total_emprestimos'] ?></td>
                                        <td class="text-end">R$ <?= number_format($investidor['total_valor_emprestado'], 2, ',', '.') ?></td>
                                        <td class="text-end">R$ <?= number_format($investidor['total_valor_recebido'], 2, ',', '.') ?></td>
                                        <td class="text-end">R$ <?= number_format($investidor['total_pendente'], 2, ',', '.') ?></td>
                                        <td class="text-center">
                                            <?php if ($investidor['total_parcelas_atrasadas'] > 0): ?>
                                                <span class="badge bg-danger"><?= $investidor['total_parcelas_atrasadas'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="?investidor=<?= $investidor['investidor_id'] ?>&periodo=<?= $filtro_periodo ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-search"></i> Detalhes
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Totais</th>
                                        <th class="text-center"><?= $total_geral_clientes ?></th>
                                        <th class="text-center"><?= $total_geral_emprestimos ?></th>
                                        <th class="text-end">R$ <?= number_format($total_geral_emprestado, 2, ',', '.') ?></th>
                                        <th class="text-end">R$ <?= number_format($total_geral_recebido, 2, ',', '.') ?></th>
                                        <th class="text-end">R$ <?= number_format($total_geral_pendente, 2, ',', '.') ?></th>
                                        <th class="text-center"><?= $total_geral_atrasadas ?></th>
                                        <th class="text-center"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Não foram encontrados investidores com os filtros selecionados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Tabela de Clientes do Investidor Selecionado -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($clientes_investidor) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Telefone</th>
                                        <th class="text-center">Empréstimos Ativos</th>
                                        <th class="text-end">Valor Emprestado</th>
                                        <th class="text-end">Valor Pago</th>
                                        <th class="text-end">Valor Pendente</th>
                                        <th class="text-center">Parcelas Atrasadas</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_emprestimos_ativos = 0;
                                    $total_valor_emprestado = 0;
                                    $total_valor_pago = 0;
                                    $total_valor_pendente = 0;
                                    $total_parcelas_atrasadas = 0;
                                    
                                    foreach ($clientes_investidor as $cliente): 
                                        $total_emprestimos_ativos += $cliente['emprestimos_ativos'];
                                        $total_valor_emprestado += $cliente['valor_emprestado'];
                                        $total_valor_pago += $cliente['valor_pago'];
                                        $total_valor_pendente += $cliente['valor_pendente'];
                                        $total_parcelas_atrasadas += $cliente['parcelas_atrasadas'];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cliente['cliente_nome']) ?></td>
                                        <td><?= htmlspecialchars($cliente['cliente_telefone']) ?></td>
                                        <td class="text-center"><?= $cliente['emprestimos_ativos'] ?></td>
                                        <td class="text-end">R$ <?= number_format($cliente['valor_emprestado'], 2, ',', '.') ?></td>
                                        <td class="text-end">R$ <?= number_format($cliente['valor_pago'], 2, ',', '.') ?></td>
                                        <td class="text-end">R$ <?= number_format($cliente['valor_pendente'], 2, ',', '.') ?></td>
                                        <td class="text-center">
                                            <?php if ($cliente['parcelas_atrasadas'] > 0): ?>
                                                <span class="badge bg-danger"><?= $cliente['parcelas_atrasadas'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= BASE_URL ?>clientes/visualizar.php?id=<?= $cliente['cliente_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2">Totais</th>
                                        <th class="text-center"><?= $total_emprestimos_ativos ?></th>
                                        <th class="text-end">R$ <?= number_format($total_valor_emprestado, 2, ',', '.') ?></th>
                                        <th class="text-end">R$ <?= number_format($total_valor_pago, 2, ',', '.') ?></th>
                                        <th class="text-end">R$ <?= number_format($total_valor_pendente, 2, ',', '.') ?></th>
                                        <th class="text-center"><?= $total_parcelas_atrasadas ?></th>
                                        <th class="text-center"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Este investidor não possui clientes registrados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
        @media print {
            .navbar, .btn-group, footer, form {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .card-body {
                padding: 0 !important;
            }
            @page {
                size: landscape;
            }
        }
    </style>
    
    <script>
        // Função para exportar para Excel
        function exportarExcel() {
            // Criar dados CSV
            let csv = [];
            const rows = document.querySelectorAll('table tr');
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Limpar o texto (remover espaços extras, quebras de linha, etc)
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    // Colocar aspas ao redor de cada campo
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            
            // Baixar CSV
            const csvString = csv.join('\n');
            const filename = '<?= $filtro_investidor > 0 ? "clientes_investidor_" . $filtro_investidor : "relatorio_investidores" ?>_<?= date("Y-m-d") ?>.csv';
            
            let downloadLink = document.createElement('a');
            downloadLink.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvString);
            downloadLink.download = filename;
            
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Função para exportar para PDF
        function exportarPDF() {
            window.print();
        }
    </script>
    
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html> 