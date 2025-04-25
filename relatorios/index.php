<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/funcoes_data.php';
require_once __DIR__ . '/../includes/funcoes_moeda.php';

// Definir período do relatório (mês atual por padrão)
$data_inicial = isset($_GET['data_inicial']) ? $_GET['data_inicial'] : date('Y-m-01');
$data_final = isset($_GET['data_final']) ? $_GET['data_final'] : date('Y-m-t');
$tipo_relatorio = isset($_GET['tipo']) ? $_GET['tipo'] : 'emprestimos';
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';
$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

// Formatação para exibição
$data_inicial_formatada = formatarData($data_inicial);
$data_final_formatada = formatarData($data_final);

// Variáveis para armazenar os resultados
$emprestimos = [];
$pagamentos = [];
$parcelas_vencidas = [];
$parcelas_pagas = [];
$clientes = [];

// Inicializar variáveis de totais
$total_emprestimos = 0;
$total_pagamentos = 0;
$total_vencidas = 0;
$total_pagas = 0;
$total_pendente = 0;
$total_atrasado = 0;
$total_pago = 0;

// Buscar clientes para o filtro
$sql_clientes = "SELECT id, nome FROM clientes ORDER BY nome";
$result_clientes = $conn->query($sql_clientes);
while ($row = $result_clientes->fetch_assoc()) {
    $clientes[] = $row;
}

// Executar consulta com base no tipo selecionado
if ($tipo_relatorio == 'emprestimos') {
    $sql = "
        SELECT 
            e.id, 
            e.cliente_id, 
            c.nome as cliente_nome, 
            e.valor_emprestado, 
            e.data_inicio, 
            e.parcelas,
            e.valor_parcela,
            e.juros_percentual
        FROM 
            emprestimos e
        INNER JOIN 
            clientes c ON e.cliente_id = c.id
        WHERE 
            e.data_inicio BETWEEN ? AND ?
    ";
    
    // Adicionar filtro de cliente se necessário
    $params = [$data_inicial, $data_final];
    $tipos = 'ss';
    
    if ($cliente_id > 0) {
        $sql .= " AND e.cliente_id = ?";
        $params[] = $cliente_id;
        $tipos .= 'i';
    }
    
    $sql .= " ORDER BY e.data_inicio DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $emprestimos[] = $row;
        $total_emprestimos += $row['valor_emprestado'];
    }
    
    // Calcular parcelas pagas e progresso para cada empréstimo
    foreach ($emprestimos as &$emp) {
        $sql_parcelas = "SELECT COUNT(*) as total_parcelas, 
                              SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as parcelas_pagas,
                              SUM(valor) as valor_total
                         FROM parcelas 
                         WHERE emprestimo_id = ?";
        $stmt_parcelas = $conn->prepare($sql_parcelas);
        $stmt_parcelas->bind_param("i", $emp['id']);
        $stmt_parcelas->execute();
        $result_parcelas = $stmt_parcelas->get_result()->fetch_assoc();
        
        $emp['total_parcelas'] = $result_parcelas['total_parcelas'];
        $emp['parcelas_pagas'] = $result_parcelas['parcelas_pagas'];
        $emp['valor_total'] = $result_parcelas['valor_total'];
        $emp['progresso'] = $emp['total_parcelas'] > 0 ? 
                          round(($emp['parcelas_pagas'] / $emp['total_parcelas']) * 100) : 0;
    }
} 
else if ($tipo_relatorio == 'pagamentos') {
    $sql = "
        SELECT 
            p.id, 
            p.emprestimo_id, 
            p.numero as parcela_numero, 
            p.valor, 
            p.valor_pago,
            p.data_pagamento, 
            p.forma_pagamento,
            e.cliente_id,
            c.nome as cliente_nome
        FROM 
            parcelas p
        INNER JOIN 
            emprestimos e ON p.emprestimo_id = e.id
        INNER JOIN 
            clientes c ON e.cliente_id = c.id
        WHERE 
            p.status IN ('pago', 'parcial')
            AND p.data_pagamento BETWEEN ? AND ?
    ";
    
    // Adicionar filtro de cliente se necessário
    $params = [$data_inicial, $data_final];
    $tipos = 'ss';
    
    if ($cliente_id > 0) {
        $sql .= " AND c.id = ?";
        $params[] = $cliente_id;
        $tipos .= 'i';
    }
    
    $sql .= " ORDER BY p.data_pagamento DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $pagamentos[] = $row;
        $total_pagamentos += $row['valor_pago'] ?? $row['valor']; // Usar valor_pago se existir, senão usar valor
    }
}
else if ($tipo_relatorio == 'parcelas') {
    // Consulta para relatório de parcelas
    $sql = "
        SELECT 
            p.id,
            p.emprestimo_id, 
            p.numero,
            p.vencimento,
            p.valor,
            p.status,
            e.cliente_id,
            c.nome as cliente_nome
        FROM 
            parcelas p
        INNER JOIN 
            emprestimos e ON p.emprestimo_id = e.id
        INNER JOIN 
            clientes c ON e.cliente_id = c.id
        WHERE 
            p.vencimento BETWEEN ? AND ?
    ";
    
    $params = [$data_inicial, $data_final];
    $tipos = 'ss';
    
    // Adicionar filtro de cliente se necessário
    if ($cliente_id > 0) {
        $sql .= " AND e.cliente_id = ?";
        $params[] = $cliente_id;
        $tipos .= 'i';
    }
    
    // Adicionar filtro de status se necessário
    if ($status_filtro != 'todos') {
        $sql .= " AND p.status = ?";
        $params[] = $status_filtro;
        $tipos .= 's';
    }
    
    $sql .= " ORDER BY p.vencimento ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $todas_parcelas = [];
    
    while ($row = $result->fetch_assoc()) {
        // Verificar se a parcela está atrasada
        if ($row['status'] == 'pendente' && strtotime($row['vencimento']) < strtotime(date('Y-m-d'))) {
            $row['status'] = 'atrasado';
        }
        
        $todas_parcelas[] = $row;
        
        // Calcular totais
        if ($row['status'] == 'pago') {
            $total_pago += $row['valor'];
        } elseif ($row['status'] == 'atrasado') {
            $total_atrasado += $row['valor'];
        } elseif ($row['status'] == 'pendente') {
            $total_pendente += $row['valor'];
        }
    }
}

// Atualizar totais para resumo
foreach ($emprestimos as $emp) {
    $total_emprestimos += $emp['valor_emprestado'];
}

foreach ($pagamentos as $pag) {
    $total_pagamentos += $pag['valor_pago'] ?? $pag['valor'];
}

foreach ($parcelas_vencidas as $parc) {
    $total_vencidas += $parc['valor'];
}

foreach ($parcelas_pagas as $parc) {
    $total_pagas += $parc['valor'];
}

// Título da página
$titulo_pagina = "Relatórios - " . ($tipo_relatorio == 'emprestimos' ? 'Empréstimos' : 
                                  ($tipo_relatorio == 'pagamentos' ? 'Pagamentos' : 'Parcelas'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo_pagina ?> - Sistema de Empréstimos</title>
    <?php require_once __DIR__ . '/../includes/head.php'; ?>
    <!-- DataTables para tabelas avançadas -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            border-radius: 10px 10px 0 0;
            background-color: #f8f9fa;
            padding: 0.8rem 1.25rem;
        }
        
        .filters-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .total-card {
            background: linear-gradient(45deg, #0062cc, #007bff);
            color: white;
            border: none;
            padding: 1rem;
        }
        
        .total-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            opacity: 0.8;
        }
        
        .total-card .value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .total-card .count {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h1 class="mb-1"><?= $titulo_pagina ?></h1>
                <p class="text-muted mb-0">Filtrar e exportar relatórios do sistema</p>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-card no-print">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="tipo" class="form-label">Tipo de Relatório</label>
                        <select name="tipo" id="tipo" class="form-select" required>
                            <option value="emprestimos" <?= $tipo_relatorio == 'emprestimos' ? 'selected' : '' ?>>Empréstimos</option>
                            <option value="pagamentos" <?= $tipo_relatorio == 'pagamentos' ? 'selected' : '' ?>>Pagamentos</option>
                            <option value="parcelas" <?= $tipo_relatorio == 'parcelas' ? 'selected' : '' ?>>Parcelas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="data_inicial" class="form-label">Data Inicial</label>
                        <input type="date" class="form-control" id="data_inicial" name="data_inicial" value="<?= $data_inicial ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="data_final" class="form-label">Data Final</label>
                        <input type="date" class="form-control" id="data_final" name="data_final" value="<?= $data_final ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select name="cliente_id" id="cliente_id" class="form-select">
                            <option value="">Todos os clientes</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" <?= $cliente_id == $cliente['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="status-filter" class="col-md-3" <?= $tipo_relatorio != 'parcelas' ? 'style="display:none"' : '' ?>>
                        <label for="status" class="form-label">Status da Parcela</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="vencidas" <?= $status_filtro == 'vencidas' ? 'selected' : '' ?>>Vencidas</option>
                            <option value="pagas" <?= $status_filtro == 'pagas' ? 'selected' : '' ?>>Pagas</option>
                            <option value="pendentes" <?= $status_filtro == 'pendentes' ? 'selected' : '' ?>>Pendentes</option>
                        </select>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="limparFiltros()">
                            <i class="bi bi-x-circle"></i> Limpar Filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Resumo -->
        <div class="row mb-4">
            <?php if ($tipo_relatorio == 'emprestimos'): ?>
                <div class="col-md-4">
                    <div class="card total-card">
                        <div class="card-body">
                            <h6 class="card-title">Total Emprestado</h6>
                            <div class="value">R$ <?= number_format($total_emprestimos, 2, ',', '.') ?></div>
                            <div class="count"><?= count($emprestimos) ?> empréstimo(s)</div>
                        </div>
                    </div>
                </div>
            <?php elseif ($tipo_relatorio == 'pagamentos'): ?>
                <div class="col-md-4">
                    <div class="card total-card">
                        <div class="card-body">
                            <h6 class="card-title">Total Recebido</h6>
                            <div class="value">R$ <?= number_format($total_pagamentos, 2, ',', '.') ?></div>
                            <div class="count"><?= count($pagamentos) ?> pagamento(s)</div>
                        </div>
                    </div>
                </div>
            <?php elseif ($tipo_relatorio == 'parcelas'): ?>
                <div class="col-md-4">
                    <div class="card total-card">
                        <div class="card-body">
                            <h6 class="card-title">Parcelas Vencidas</h6>
                            <div class="value">R$ <?= number_format($total_vencidas, 2, ',', '.') ?></div>
                            <div class="count"><?= count($parcelas_vencidas) ?> parcela(s)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card total-card">
                        <div class="card-body">
                            <h6 class="card-title">Parcelas Pagas</h6>
                            <div class="value">R$ <?= number_format($total_pagas, 2, ',', '.') ?></div>
                            <div class="count"><?= count($parcelas_pagas) ?> parcela(s)</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Resultados -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Resultados</h5>
                <span class="text-muted">Período: <?= $data_inicial_formatada ?> a <?= $data_final_formatada ?></span>
            </div>
            <div class="card-body">
                <?php if ($tipo_relatorio == 'emprestimos'): ?>
                    <div class="table-responsive">
                        <table class="table table-striped datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Parcelas</th>
                                    <th>Valor Parcela</th>
                                    <th>Juros</th>
                                    <th>Data</th>
                                    <th>Progresso</th>
                                    <th class="no-print">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emprestimos as $emp): ?>
                                <tr>
                                    <td><?= $emp['id'] ?></td>
                                    <td><?= htmlspecialchars($emp['cliente_nome']) ?></td>
                                    <td>R$ <?= number_format($emp['valor_emprestado'], 2, ',', '.') ?></td>
                                    <td><?= $emp['parcelas_pagas'] ?>/<?= $emp['parcelas'] ?></td>
                                    <td>R$ <?= number_format($emp['valor_parcela'], 2, ',', '.') ?></td>
                                    <td><?= number_format($emp['juros_percentual'], 2, ',', '.') ?>%</td>
                                    <td><?= date('d/m/Y', strtotime($emp['data_inicio'])) ?></td>
                                    <td>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= $emp['progresso'] ?>%;" 
                                                 aria-valuenow="<?= $emp['progresso'] ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <small><?= $emp['progresso'] ?>%</small>
                                    </td>
                                    <td class="no-print">
                                        <a href="<?= BASE_URL ?>emprestimos/visualizar.php?id=<?= $emp['id'] ?>" 
                                            class="btn btn-sm btn-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($tipo_relatorio == 'pagamentos'): ?>
                    <div class="table-responsive">
                        <table class="table table-striped datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Empréstimo</th>
                                    <th>Parcela</th>
                                    <th>Valor</th>
                                    <th>Forma</th>
                                    <th>Data</th>
                                    <th class="no-print">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagamentos as $pag): ?>
                                <tr>
                                    <td><?= $pag['id'] ?></td>
                                    <td><?= htmlspecialchars($pag['cliente_nome']) ?></td>
                                    <td><?= $pag['emprestimo_id'] ?></td>
                                    <td><?= $pag['parcela_numero'] ?></td>
                                    <td>R$ <?= number_format($pag['valor_pago'] ?? $pag['valor'], 2, ',', '.') ?></td>
                                    <td><?= ucfirst($pag['forma_pagamento'] ?? 'Não informado') ?></td>
                                    <td><?= date('d/m/Y', strtotime($pag['data_pagamento'])) ?></td>
                                    <td class="no-print">
                                        <a href="<?= BASE_URL ?>emprestimos/visualizar.php?id=<?= $pag['emprestimo_id'] ?>" 
                                            class="btn btn-sm btn-primary" title="Visualizar Empréstimo">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($tipo_relatorio == 'parcelas'): ?>
                    <div class="table-responsive">
                        <table class="table table-striped datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Empréstimo</th>
                                    <th>Parcela</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th class="no-print">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todas_parcelas as $parcela): ?>
                                <tr>
                                    <td><?= $parcela['id'] ?></td>
                                    <td><?= htmlspecialchars($parcela['cliente_nome']) ?></td>
                                    <td><?= $parcela['emprestimo_id'] ?></td>
                                    <td><?= $parcela['numero'] ?></td>
                                    <td>R$ <?= number_format($parcela['valor'], 2, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($parcela['vencimento'])) ?></td>
                                    <td>
                                        <?php if ($parcela['status'] == 'pago'): ?>
                                            <span class="badge bg-success">Pago</span>
                                        <?php elseif ($parcela['status'] == 'atrasado'): ?>
                                            <span class="badge bg-danger">Atrasado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="no-print">
                                        <a href="<?= BASE_URL ?>emprestimos/visualizar.php?id=<?= $parcela['emprestimo_id'] ?>" 
                                           class="btn btn-sm btn-primary" title="Visualizar Empréstimo">
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
        </div>
        
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mostrar/esconder o filtro de status com base no tipo de relatório
        document.getElementById('tipo').addEventListener('change', function() {
            const statusFilter = document.getElementById('status-filter');
            statusFilter.style.display = this.value === 'parcelas' ? 'block' : 'none';
        });
        
        // Inicializar DataTables com recursos de exportação
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="bi bi-clipboard"></i> Copiar',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: {
                        columns: ':not(.no-print)'
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-excel"></i> Excel',
                    className: 'btn btn-sm btn-outline-success',
                    exportOptions: {
                        columns: ':not(.no-print)'
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-pdf"></i> PDF',
                    className: 'btn btn-sm btn-outline-danger',
                    exportOptions: {
                        columns: ':not(.no-print)'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Imprimir',
                    className: 'btn btn-sm btn-outline-primary',
                    exportOptions: {
                        columns: ':not(.no-print)'
                    }
                }
            ],
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]]
        });
    });
    
    // Função para limpar os filtros
    function limparFiltros() {
        document.getElementById('data_inicial').value = '<?= date('Y-m-01') ?>';
        document.getElementById('data_final').value = '<?= date('Y-m-t') ?>';
        document.getElementById('cliente_id').value = '';
        document.getElementById('status').value = '';
        document.querySelector('form').submit();
    }
    </script>
</body>
</html> 