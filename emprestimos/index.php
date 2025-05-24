<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';

// Verificar permissões administrativas
apenasAdmin();

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/queries.php';

$emprestimos = buscarTodosEmprestimosComCliente($conn);

// Calcula totais para os cards
$total_emprestado = 0;
$total_recebido = 0;

// Usar a função corrigida para contar empréstimos ativos
$emprestimos_ativos = contarEmprestimosAtivos($conn);

foreach ($emprestimos as $e) {
    $total_emprestado += floatval($e['valor_emprestado']);
    if (isset($e['total_pago'])) {
        $total_recebido += floatval($e['total_pago']);
    }
}

// Calculando o total pendente - somando todas as parcelas pendentes
$sql_pendente = "SELECT 
                    SUM(
                        CASE 
                            WHEN p.status = 'pendente' THEN p.valor 
                            WHEN p.status = 'parcial' THEN (p.valor - IFNULL(p.valor_pago, 0))
                            ELSE 0 
                        END
                    ) AS total 
                FROM parcelas p
                INNER JOIN emprestimos e ON p.emprestimo_id = e.id
                WHERE p.status IN ('pendente', 'parcial') 
                AND (e.status != 'inativo' OR e.status IS NULL)";
$result_pendente = $conn->query($sql_pendente);
if ($result_pendente && $row_pendente = $result_pendente->fetch_assoc()) {
    $total_pendente = floatval($row_pendente['total'] ?? 0);
} else {
    $total_pendente = 0;
}

// Calculando o total a receber - somando todas as parcelas (pagas e não pagas) de empréstimos ativos
$sql_a_receber = "SELECT 
                    SUM(p.valor) AS total 
                FROM parcelas p
                INNER JOIN emprestimos e ON p.emprestimo_id = e.id
                WHERE e.status = 'ativo'";
$result_a_receber = $conn->query($sql_a_receber);
if ($result_a_receber && $row_a_receber = $result_a_receber->fetch_assoc()) {
    $total_a_receber = floatval($row_a_receber['total'] ?? 0);
} else {
    $total_a_receber = 0;
}

// Calculando o total que falta receber - somando todas as parcelas não pagas
$sql_falta_receber = "SELECT 
                    SUM(
                        CASE 
                            WHEN p.status = 'pendente' THEN p.valor 
                            WHEN p.status = 'parcial' THEN (p.valor - IFNULL(p.valor_pago, 0))
                            WHEN p.status = 'atrasado' THEN p.valor
                            ELSE 0 
                        END
                    ) AS total 
                FROM parcelas p
                INNER JOIN emprestimos e ON p.emprestimo_id = e.id
                WHERE p.status != 'pago'";
$result_falta_receber = $conn->query($sql_falta_receber);
if ($result_falta_receber && $row_falta_receber = $result_falta_receber->fetch_assoc()) {
    $total_falta_receber = floatval($row_falta_receber['total'] ?? 0);
} else {
    $total_falta_receber = 0;
}

// Calculando total atrasado corretamente
$ontem = date('Y-m-d', strtotime('-1 day'));

// Consulta simplificada para parcelas atrasadas
$sql_atrasado = "SELECT 
                    SUM(
                        CASE 
                            WHEN status = 'parcial' THEN (valor - IFNULL(valor_pago, 0))
                            ELSE valor 
                        END
                    ) AS total_valor,
                    COUNT(DISTINCT emprestimo_id) AS total_emprestimos,
                    COUNT(id) AS total_parcelas
                 FROM parcelas 
                 WHERE (status = 'atrasado' OR (status IN ('pendente', 'parcial') AND vencimento < ?))";

$stmt_atrasado = $conn->prepare($sql_atrasado);
if (!$stmt_atrasado) {
    $total_atrasado = 0;
    $emprestimos_atrasados = 0;
    $parcelas_atrasadas = 0;
} else {
    $stmt_atrasado->bind_param("s", $ontem);
    $stmt_atrasado->execute();
    $result_atrasado = $stmt_atrasado->get_result();
    
    if ($result_atrasado && $row_atrasado = $result_atrasado->fetch_assoc()) {
        $total_atrasado = floatval($row_atrasado['total_valor'] ?? 0);
        $emprestimos_atrasados = intval($row_atrasado['total_emprestimos'] ?? 0);
        $parcelas_atrasadas = intval($row_atrasado['total_parcelas'] ?? 0);
    } else {
        $total_atrasado = 0;
        $emprestimos_atrasados = 0;
        $parcelas_atrasadas = 0;
    }
}

// Busca o empréstimo recém criado se houver
$emprestimo_novo = null;
if (isset($_GET['sucesso']) && isset($_GET['id'])) {
    $emprestimo_novo = buscarEmprestimoPorId($conn, $_GET['id']);
}
?>

<div class="container py-4">
    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h4 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Sucesso!</h4>
            <p class="mb-0"><?= htmlspecialchars($_GET['msg'] ?? 'Operação realizada com sucesso!') ?></p>
            <?php if ($emprestimo_novo): ?>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Resumo do Empréstimo:</h5>
                        <ul class="list-unstyled">
                            <li><strong>Cliente:</strong> <?= htmlspecialchars($emprestimo_novo['cliente_nome']) ?></li>
                            <?php if (!empty($emprestimo_novo['investidor_nome'])): ?>
                            <li><strong>Investidor:</strong> <?= htmlspecialchars($emprestimo_novo['investidor_nome']) ?></li>
                            <?php endif; ?>
                            <li><strong>Valor:</strong> R$ <?= number_format($emprestimo_novo['valor_emprestado'], 2, ',', '.') ?></li>
                            <li><strong>Parcelas:</strong> <?= $emprestimo_novo['parcelas'] ?>x de R$ <?= number_format($emprestimo_novo['valor_parcela'], 2, ',', '.') ?></li>
                            <?php if ($emprestimo_novo['juros_percentual'] > 0): ?>
                                <li><strong>Juros:</strong> <?= number_format($emprestimo_novo['juros_percentual'], 2, ',', '.') ?>%</li>
                            <?php endif; ?>
                            <li><strong>Início:</strong> <?= date('d/m/Y', strtotime($emprestimo_novo['data_inicio'])) ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="visualizar.php?id=<?= $emprestimo_novo['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-eye"></i> Ver Detalhes
                        </a>
                        <a href="novo.php" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Novo Empréstimo
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Erro!</h4>
            <p class="mb-0"><?= htmlspecialchars($_GET['msg'] ?? 'Ocorreu um erro na operação.') ?></p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
  </div>
    <?php endif; ?>

    <!-- Cards de Resumo - Desktop -->
    <div class="d-none d-md-block">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Emprestado</h6>
                        <h4 class="mb-0">R$ <?= number_format($total_emprestado, 2, ',', '.') ?></h4>
                        <p class="mt-1 mb-0">A receber: R$ <?= number_format($total_a_receber, 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Recebido</h6>
                        <h4 class="mb-0">R$ <?= number_format($total_recebido, 2, ',', '.') ?></h4>
                        <p class="mt-1 mb-0">Falta receber: R$ <?= number_format($total_falta_receber, 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Pendente</h6>
                        <h4 class="mb-0">R$ <?= number_format($total_pendente, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Empréstimos</h6>
                        <h4 class="mb-0"><?= count($emprestimos) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card bg-primary bg-opacity-75 text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Ativos</h6>
                        <h4 class="mb-0"><?= (int)$emprestimos_ativos ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Parcelas Atrasadas</h6>
                        <h4><?= $parcelas_atrasadas ?> parcelas | R$ <?= number_format($total_atrasado, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cards de Resumo - Mobile -->
    <div class="d-md-none mb-4">
        <div class="row g-3">
            <div class="col-6">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Emprestado</h6>
                        <h4 class="mb-0">R$ <?= number_format($total_emprestado, 2, ',', '.') ?></h4>
                        <p class="mt-1 mb-0">A receber: R$ <?= number_format($total_a_receber, 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Recebido</h6>
                        <h4 class="mb-0">R$ <?= number_format($total_recebido, 2, ',', '.') ?></h4>
                        <p class="mt-1 mb-0">Falta receber: R$ <?= number_format($total_falta_receber, 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Pendente</h6>
                        <h4 class="mb-0">R$ <?= number_format($total_pendente, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Empréstimos</h6>
                        <h4 class="mb-0"><?= count($emprestimos) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-primary bg-opacity-75 text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Ativos</h6>
                        <h4 class="mb-0"><?= (int)$emprestimos_ativos ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Atrasados</h6>
                        <h4 class="mb-0"><?= $emprestimos_atrasados ?></h4>
                        <small><?= $parcelas_atrasadas ?> parcelas | R$ <?= number_format($total_atrasado, 2, ',', '.') ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cabeçalho com Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Empréstimos</h5>
                        <div>
                            <a href="novo.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Novo
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row g-2">
                        <div class="col-sm-6 col-md-5">
                            <input type="text" id="filtro-cliente" class="form-control form-control-sm" placeholder="Buscar por cliente...">
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <select id="filtro-tipo" class="form-select form-select-sm">
        <option value="">Todos os tipos</option>
                                <option value="parcelada_comum">Parcelada Comum</option>
                                <option value="reparcelada_com_juros">Reparcelada com Juros</option>
      </select>
    </div>
                        <div class="col-sm-6 col-md-2">
                            <select id="filtro-status" class="form-select form-select-sm">
                                <option value="">Status</option>
                                <option value="ativo">Ativo</option>
                                <option value="atrasado">Atrasado</option>
                                <option value="quitado">Quitado</option>
      </select>
    </div>
                        <div class="col-sm-6 col-md-2">
                            <select id="linhasPorPagina" class="form-select form-select-sm">
      <option value="10">10</option>
      <option value="25">25</option>
      <option value="50">50</option>
      <option value="-1">Todos</option>
    </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
  </div>

    <!-- Tabela de Empréstimos (Desktop) e Cards (Mobile) -->
    <div class="card">
        <!-- Tabela para Desktop -->
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabela-emprestimos">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%">Cliente</th>
                            <th style="width: 15%">Quanto falta para terminar o empréstimo</th>
                            <th style="width: 15%">Valor</th>
                            <th style="width: 15%">Parcelas</th>
                            <th style="width: 15%">Progresso</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 7%" class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emprestimos as $e): 
                            // Busca as parcelas do empréstimo
                            $stmt = $conn->prepare("
                                SELECT numero, valor, valor_pago, status, vencimento 
                                FROM parcelas 
                                WHERE emprestimo_id = ?
                            ");
                            $stmt->bind_param("i", $e['id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $parcelas = $result->fetch_all(MYSQLI_ASSOC);
                            
                            // Calcula o progresso
                            $total_parcelas = count($parcelas);
                            $pagas = 0;
                            $valor_total_pago = 0;
                            foreach ($parcelas as $p) {
                                if ($p['status'] === 'pago') {
                                    $pagas++;
                                    $valor_total_pago += isset($p['valor']) ? floatval($p['valor']) : 0;
                                } elseif ($p['status'] === 'parcial') {
                                    $valor_total_pago += isset($p['valor_pago']) ? floatval($p['valor_pago']) : 0;
                                }
                            }
                            $progresso = ($total_parcelas > 0) ? ($pagas / $total_parcelas) * 100 : 0;
                            
                            // Calcula o status do empréstimo
                            $status = 'quitado';
                            $tem_atrasada = false;
                            $tem_pendente = false;

                            foreach ($parcelas as $p) {
                                if ($p['status'] !== 'pago') {
                                    $tem_pendente = true;
                                    $status = 'ativo';
                                    
                                    $data_vencimento = new DateTime($p['vencimento']);
                                    $hoje_menos_um = new DateTime();
                                    $hoje_menos_um->modify('-1 day');
                                    
                                    if ($data_vencimento < $hoje_menos_um) {
                                        $tem_atrasada = true;
                                        $status = 'atrasado';
                                        break;
                                    }
                                }
                            }
                            
                            // Define as classes de status
                            $status_class = match($status) {
                                'ativo' => 'text-bg-primary',
                                'atrasado' => 'text-bg-danger',
                                'quitado' => 'text-bg-success',
                                default => 'text-bg-secondary'
                            };
                            
                            // Define tipos de empréstimo
                            $tipos = [
                                'parcelada_comum' => 'Parcelamento Comum',
                                'reparcelada_com_juros' => 'Reparcelado c/ Juros'
                            ];
                            $tipo = $e['tipo_de_cobranca'] ?? '';
                        ?>
                            <tr class="clickable-row" data-href="visualizar.php?id=<?= htmlspecialchars($e['id']) ?>" style="cursor: pointer;">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($e['cliente_nome']) ?></div>
                                            <small class="text-muted">
                                                Início: <?= date('d/m/Y', strtotime($e['data_inicio'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    // Calcula o valor total que falta pagar
                                    $valor_faltante = 0;
                                    foreach ($parcelas as $p) {
                                        if ($p['status'] === 'pendente') {
                                            $valor_faltante += floatval($p['valor']);
                                        } elseif ($p['status'] === 'parcial') {
                                            $valor_faltante += (floatval($p['valor']) - floatval($p['valor_pago'] ?? 0));
                                        } elseif ($p['status'] === 'atrasado') {
                                            $valor_faltante += floatval($p['valor']);
                                        }
                                    }
                                    ?>
                                    <div class="fw-bold">R$ <?= number_format($valor_faltante, 2, ',', '.') ?></div>
                                    <small class="text-muted">
                                        <?= $total_parcelas - $pagas ?> parcelas restantes
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-bold">R$ <?= number_format((float)$e['valor_emprestado'], 2, ',', '.') ?></div>
                                    <?php if (!empty($e['juros_percentual']) && $e['juros_percentual'] > 0): ?>
                                        <small class="text-muted">
                                            <?= $e['juros_percentual'] ?>% juros
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= (int)$e['parcelas'] ?>x R$ <?= number_format((float)$e['valor_parcela'], 2, ',', '.') ?></div>
                                    <small class="text-muted">
                                        <?= $pagas ?> pagas (R$ <?= number_format($valor_total_pago, 2, ',', '.') ?>)
                                    </small>
                                </td>
                                <td>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?= $progresso ?>%"
                                             aria-valuenow="<?= $progresso ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?= number_format($progresso, 1) ?>%</small>
                                </td>
                                <td>
                                    <span class="badge <?= $status_class ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="visualizar.php?id=<?= $e['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver Detalhes">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="excluir.php?id=<?= $e['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Excluir Empréstimo">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cards para Mobile -->
        <div class="d-md-none">
            <div class="list-group list-group-flush">
                <?php foreach ($emprestimos as $e): 
                    // Busca as parcelas do empréstimo
                    $stmt = $conn->prepare("
                        SELECT numero, valor, valor_pago, status, vencimento 
                        FROM parcelas 
                        WHERE emprestimo_id = ?
                    ");
                    $stmt->bind_param("i", $e['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $parcelas = $result->fetch_all(MYSQLI_ASSOC);
                    
                    // Calcula o progresso
                    $total_parcelas = count($parcelas);
                    $pagas = 0;
                    $valor_total_pago = 0;
                    foreach ($parcelas as $p) {
                        if ($p['status'] === 'pago') {
                            $pagas++;
                            $valor_total_pago += isset($p['valor']) ? floatval($p['valor']) : 0;
                        } elseif ($p['status'] === 'parcial') {
                            $valor_total_pago += isset($p['valor_pago']) ? floatval($p['valor_pago']) : 0;
                        }
                    }
                    $progresso = ($total_parcelas > 0) ? ($pagas / $total_parcelas) * 100 : 0;
                    
                    // Calcula o status do empréstimo
                    $status = 'quitado';
                    $tem_atrasada = false;
                    $tem_pendente = false;

                    foreach ($parcelas as $p) {
                        if ($p['status'] !== 'pago') {
                            $tem_pendente = true;
                            $status = 'ativo';
                            
                            $data_vencimento = new DateTime($p['vencimento']);
                            $hoje_menos_um = new DateTime();
                            $hoje_menos_um->modify('-1 day');
                            
                            if ($data_vencimento < $hoje_menos_um) {
                                $tem_atrasada = true;
                                $status = 'atrasado';
                                break;
                            }
                        }
                    }
                    
                    // Define as classes de status
                    $status_class = match($status) {
                        'ativo' => 'text-bg-primary',
                        'atrasado' => 'text-bg-danger',
                        'quitado' => 'text-bg-success',
                        default => 'text-bg-secondary'
                    };
                    
                    // Define tipos de empréstimo
                    $tipos = [
                        'parcelada_comum' => 'Parcelamento Comum',
                        'reparcelada_com_juros' => 'Reparcelado c/ Juros'
                    ];
                    $tipo = $e['tipo_de_cobranca'] ?? '';
                ?>
                    <div class="list-group-item p-3 mb-3 border rounded shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($e['cliente_nome']) ?></h6>
                            <span class="badge <?= $status_class ?>">
                                <?= ucfirst($status) ?>
                            </span>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted d-block">
                                Início: <?= date('d/m/Y', strtotime($e['data_inicio'])) ?>
                            </small>
                            <?php
                            // Calcula o valor total que falta pagar
                            $valor_faltante = 0;
                            foreach ($parcelas as $p) {
                                if ($p['status'] === 'pendente') {
                                    $valor_faltante += floatval($p['valor']);
                                } elseif ($p['status'] === 'parcial') {
                                    $valor_faltante += (floatval($p['valor']) - floatval($p['valor_pago'] ?? 0));
                                } elseif ($p['status'] === 'atrasado') {
                                    $valor_faltante += floatval($p['valor']);
                                }
                            }
                            ?>
                            <div>
                                <small class="text-muted d-block">Falta para finalizar:</small>
                                <strong>R$ <?= number_format($valor_faltante, 2, ',', '.') ?></strong>
                                <small class="text-muted d-block"><?= $total_parcelas - $pagas ?> parcelas restantes</small>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <small class="text-muted d-block">Valor</small>
                                <strong>R$ <?= number_format($e['valor_emprestado'], 2, ',', '.') ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Parcelas</small>
                                <strong><?= $e['parcelas'] ?>x R$ <?= number_format($e['valor_parcela'], 2, ',', '.') ?></strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?= $progresso ?>%"
                                     aria-valuenow="<?= $progresso ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted"><?= number_format($progresso, 1) ?>% concluído</small>
                                <small class="text-muted"><?= $pagas ?>/<?= $total_parcelas ?> parcelas</small>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <a href="visualizar.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-eye"></i> Visualizar
                            </a>
                            <a href="excluir.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger flex-fill">
                                <i class="bi bi-trash"></i> Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Adiciona padding no final para o último card não ficar colado no fim da página -->
            <div class="pb-3"></div>
        </div>
    </div>
</div>

<script>
// Inicialização do DataTable com configurações otimizadas (apenas para desktop)
$(document).ready(function() {
    // Adiciona comportamento de clique nas linhas da tabela
    $('.clickable-row').on('click', function(e) {
        if (!$(e.target).closest('a').length) {
            window.location = $(this).data('href');
        }
    });
    
    if (window.innerWidth >= 768) {  // Só inicializa em desktop
        const table = $('#tabela-emprestimos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            pageLength: 10,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            order: [[5, 'desc']], // Ordena por status
            responsive: true,
            stateSave: true
        });

        // Filtro de cliente
        $('#filtro-cliente').on('keyup', function() {
            table.column(0).search(this.value).draw();
        });

        // Filtro de tipo
        $('#filtro-tipo').on('change', function() {
            table.column(1).search(this.value).draw();
        });

        // Filtro de status
        $('#filtro-status').on('change', function() {
            table.column(5).search(this.value).draw();
        });

        // Linhas por página
        $('#linhasPorPagina').on('change', function() {
            table.page.len(this.value).draw();
        });
    }
});

// Função para enviar cobrança via WhatsApp
function enviarCobranca(id) {
    // Implementar lógica de envio de cobrança
    alert('Função de envio de cobrança será implementada em breve!');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>