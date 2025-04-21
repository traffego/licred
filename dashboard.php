<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/autenticacao.php';
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/queries.php';

// Inicializa variáveis com valores padrão
$emprestimos = [];
$ultimos_emprestimos = [];
$total_atrasado = 0;
$total_emprestimos_ativos = 0;

try {
    // Busca empréstimos com tratamento de erro
    $emprestimos = buscarTodosEmprestimosComCliente($conn);
    if (!empty($emprestimos)) {
        $ultimos_emprestimos = array_slice($emprestimos, 0, 5);
    }

    // Calcula totais com tratamento de erro
    $total_atrasado = calcularTotalParcelasAtrasadas($conn);
    $total_emprestimos_ativos = contarEmprestimosAtivos($conn);
    
    // Cálculos adicionais para cards
    $total_emprestado = 0;
    $total_recebido = 0;
    $total_pendente = 0;
    $emprestimos_atrasados = 0;
    
    foreach ($emprestimos as $e) {
        $total_emprestado += floatval($e['valor_emprestado']);
        if (isset($e['total_pago'])) {
            $total_recebido += floatval($e['total_pago']);
        }
        
        // Verifica se há parcelas atrasadas
        $stmt = $conn->prepare("SELECT status, vencimento FROM parcelas WHERE emprestimo_id = ?");
        $stmt->bind_param("i", $e['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $parcelas = $result->fetch_all(MYSQLI_ASSOC);
        
        $tem_atrasada = false;
        foreach ($parcelas as $p) {
            if ($p['status'] !== 'pago') {
                $data_vencimento = new DateTime($p['vencimento']);
                if ($data_vencimento < new DateTime()) {
                    $tem_atrasada = true;
                    break;
                }
            }
        }
        
        if ($tem_atrasada) {
            $emprestimos_atrasados++;
        }
    }
    
    $total_pendente = $total_emprestado - $total_recebido;
} catch (Exception $e) {
    // Log do erro (você pode implementar um sistema de log)
    error_log("Erro no dashboard: " . $e->getMessage());
}
?>

<div class="container py-1">
    <h2 class="mb-4 text-uppercase">🚀 Painel Financeiro</h2>

    <!-- Área de Ações Rápidas Modernizada -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-muted">Ações Rápidas</h6>
                        <div class="d-flex gap-2">
                            <a href="emprestimos/novo.php" class="btn btn-sm btn-success d-flex align-items-center gap-2">
                                <i class="bi bi-plus-circle-fill"></i>
                                <span class="d-none d-md-inline">Novo Empréstimo</span>
                            </a>
                            <a href="clientes/novo.php" class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                                <i class="bi bi-person-plus-fill"></i>
                                <span class="d-none d-md-inline">Novo Cliente</span>
                            </a>
                            <a href="relatorios/diario.php" class="btn btn-sm btn-info text-white d-flex align-items-center gap-2">
                                <i class="bi bi-graph-up"></i>
                                <span class="d-none d-md-inline">Relatório Diário</span>
                            </a>
                            <a href="emprestimos/parcelas/cobrancas/" class="btn btn-sm btn-warning d-flex align-items-center gap-2">
                                <i class="bi bi-bell-fill"></i>
                                <span class="d-none d-md-inline">Cobranças</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards Informativos (estilo de emprestimos/index.php) -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Total Emprestado</h6>
                    <h4 class="mb-0">R$ <?= number_format($total_emprestado, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Total Recebido</h6>
                    <h4 class="mb-0">R$ <?= number_format($total_recebido, 2, ',', '.') ?></h4>
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
                    <h4 class="mb-0"><?= (int)$total_emprestimos_ativos ?></h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Atrasados</h6>
                    <h4 class="mb-0"><?= $emprestimos_atrasados ?></h4>
                    <small>R$ <?= number_format($total_atrasado, 2, ',', '.') ?> em atraso</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos Empréstimos -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Últimos Empréstimos</h5>
            <a href="emprestimos/" class="btn btn-sm btn-primary">Ver Todos</a>
        </div>
        <div class="card-body p-0">
            <!-- Filtro de busca -->
            <div class="p-3 border-bottom">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" id="filtroCliente" class="form-control form-control-sm" placeholder="Filtrar por cliente...">
                    </div>
                    <div class="col-md-3">
                        <select id="filtroStatus" class="form-select form-select-sm">
                            <option value="">Todos os status</option>
                            <option value="Ativo">Ativo</option>
                            <option value="Atrasado">Atrasado</option>
                            <option value="Quitado">Quitado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filtroTipo" class="form-select form-select-sm">
                            <option value="">Todos os tipos</option>
                            <option value="Parcelamento Comum">Parcelamento Comum</option>
                            <option value="Reparcelado c/ Juros">Reparcelado c/ Juros</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="limparFiltros" class="btn btn-sm btn-outline-secondary w-100">Limpar</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%">Cliente</th>
                            <th style="width: 15%" class="d-none d-md-table-cell">Tipo</th>
                            <th style="width: 15%">Valor</th>
                            <th style="width: 15%">Parcelas</th>
                            <th style="width: 15%">Progresso</th>
                            <th style="width: 15%">Status</th>
                            <th style="width: 15%" class="d-none d-md-table-cell">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ultimos_emprestimos)): ?>
                            <?php foreach ($ultimos_emprestimos as $e): 
                                // Busca as parcelas na tabela parcelas
                                $stmt = $conn->prepare("SELECT 
                                    status, valor, valor_pago, vencimento 
                                    FROM parcelas 
                                    WHERE emprestimo_id = ?");
                                $stmt->bind_param("i", $e['id']);
                                $stmt->execute();
                                $result_parcelas = $stmt->get_result();
                                $parcelas = $result_parcelas->fetch_all(MYSQLI_ASSOC);
                                
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
                                        if ($data_vencimento < new DateTime()) {
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
                            ?>
                                <tr class="clickable-row" data-href="emprestimos/visualizar.php" data-id="<?= htmlspecialchars($e['id']) ?>">
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
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge text-bg-info">
                                            <?php
                                                $tipos = [
                                                    'parcelada_comum' => 'Parcelamento Comum',
                                                    'reparcelada_com_juros' => 'Reparcelado c/ Juros'
                                                ];
                                                $tipo = $e['tipo_de_cobranca'] ?? '';
                                                echo htmlspecialchars($tipos[$tipo] ?? 'Não definido');
                                            ?>
                                        </span>
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
                                    <td class="d-none d-md-table-cell">
                                        <div class="btn-group">
                                            <a href="emprestimos/visualizar.php?id=<?= $e['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver Detalhes">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-success" 
                                                    title="Registrar Pagamento"
                                                    onclick="window.location.href='emprestimos/visualizar.php?id=<?= $e['id'] ?>#pagamento'">
                                                <i class="bi bi-cash-coin"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-info" 
                                                    title="Enviar Cobrança"
                                                    onclick="enviarCobranca(<?= $e['id'] ?>)">
                                                <i class="bi bi-whatsapp"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Nenhum empréstimo encontrado
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Ajusta o tamanho das colunas */
    .table th, .table td {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    
    /* Trunca texto longo com reticências */
    .text-truncate {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Ajusta o tamanho dos badges */
    .badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Ajusta o tamanho dos botões */
    .btn-sm {
        padding: 0.5rem 0.8rem;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
    }

    .btn-sm:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .btn-sm i {
        font-size: 1rem;
    }

    .gap-2 {
        gap: 0.5rem;
    }

    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }

    .border-0 {
        border: none;
    }

    /* Estilo para linhas clicáveis */
    .clickable-row {
        cursor: pointer;
    }
    .clickable-row:hover {
        background-color: rgba(0,0,0,.05);
    }

    /* Estilos para os cards informativos */
    .cardBox {
        padding: 1.5rem;
        border-radius: 0.5rem;
        position: relative;
        overflow: hidden;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .bg-red {
        background-color: #dc3545;
        color: white;
    }

    .bg-gray {
        background-color: #6c757d;
        color: white;
    }

    .cardBox .title {
        font-size: 1rem;
        font-weight: 500;
        opacity: 0.8;
    }

    .cardBox .subtitle {
        font-size: 1.5rem;
        font-weight: 600;
    }

    .icon-bg-bi i {
        position: absolute;
        right: 1rem;
        bottom: 1rem;
        font-size: 3rem;
        opacity: 0.2;
    }
    
    /* Classes para cores de fundo */
    .text-bg-primary {
        background-color: var(--bs-primary);
        color: white;
    }
    
    .text-bg-danger {
        background-color: var(--bs-danger);
        color: white;
    }
    
    .text-bg-success {
        background-color: var(--bs-success);
        color: white;
    }
    
    .text-bg-info {
        background-color: var(--bs-info);
        color: white;
    }
    
    .text-bg-secondary {
        background-color: var(--bs-secondary);
        color: white;
    }
    
    /* Ajuste para a barra de progresso */
    .progress {
        border-radius: 0.25rem;
        margin-bottom: 0.25rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.clickable-row');
        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Previne o clique se o usuário clicar em um botão ou formulário
                if (e.target.closest('button') || e.target.closest('form') || e.target.closest('a')) {
                    return;
                }
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.dataset.href;
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = this.dataset.id;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            });
        });

        // Implementação do filtro de tabela
        const filtroCliente = document.getElementById('filtroCliente');
        const filtroStatus = document.getElementById('filtroStatus');
        const filtroTipo = document.getElementById('filtroTipo');
        const limparFiltros = document.getElementById('limparFiltros');
        const tabela = document.querySelector('.table tbody');
        const linhas = tabela.querySelectorAll('tr');

        function aplicarFiltros() {
            const valorCliente = filtroCliente.value.toLowerCase();
            const valorStatus = filtroStatus.value;
            const valorTipo = filtroTipo.value;

            linhas.forEach(linha => {
                if (linha.querySelector('td:nth-child(1)')) { // Verifica se é uma linha de dados
                    const cliente = linha.querySelector('td:nth-child(1)').textContent.toLowerCase();
                    const status = linha.querySelector('td:nth-child(6) .badge').textContent.trim();
                    const tipo = linha.querySelector('td:nth-child(2)').textContent.trim();
                    
                    const matchCliente = cliente.includes(valorCliente);
                    const matchStatus = valorStatus === '' || status === valorStatus;
                    const matchTipo = valorTipo === '' || tipo === valorTipo;
                    
                    if (matchCliente && matchStatus && matchTipo) {
                        linha.style.display = '';
                    } else {
                        linha.style.display = 'none';
                    }
                }
            });
        }

        filtroCliente.addEventListener('input', aplicarFiltros);
        filtroStatus.addEventListener('change', aplicarFiltros);
        filtroTipo.addEventListener('change', aplicarFiltros);
        
        limparFiltros.addEventListener('click', function() {
            filtroCliente.value = '';
            filtroStatus.value = '';
            filtroTipo.value = '';
            aplicarFiltros();
        });
        
        // Função para enviar cobrança via WhatsApp
        window.enviarCobranca = function(id) {
            // Implementar lógica de envio de cobrança
            alert('Função de envio de cobrança será implementada em breve!');
        };
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php' ?>
