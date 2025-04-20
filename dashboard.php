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
                            <a href="cobrancas/pendentes.php" class="btn btn-sm btn-warning d-flex align-items-center gap-2">
                                <i class="bi bi-bell-fill"></i>
                                <span class="d-none d-md-inline">Cobranças</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards Informativos -->
    <div class="row">
        <div class="col-12 col-md-4 mb-3">
            <div class="cardBox bg-red icon-bg-bi" data-icon="bi-exclamation-triangle">
                <span class="title">Parcelas atrasadas</span>
                <span class="subtitle py-2">R$ <?= is_numeric($total_atrasado) ? number_format($total_atrasado, 2, ',', '.') : '0,00' ?></span>
            </div>
        </div>

        <div class="col-12 col-md-4 mb-3">
            <div class="cardBox bg-dark icon-bg-bi" data-icon="bi-cash-coin">
                <span class="title">Saldo Disponível</span>
                <span class="subtitle py-2">R$25.900,23</span>
            </div>
        </div>

        <div class="col-12 col-md-4 mb-3">
            <div class="cardBox bg-gray icon-bg-bi" data-icon="bi-receipt">
                <span class="title">Emprestimos</span>
                <span class="subtitle py-2"><?= (int)$total_emprestimos_ativos ?> contratos</span>
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
                            <th style="width: 15%">Status</th>
                            <th style="width: 15%">Prazo</th>
                            <th style="width: 15%" class="d-none d-md-table-cell">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ultimos_emprestimos)): ?>
                            <?php foreach ($ultimos_emprestimos as $e): ?>
                                <tr class="clickable-row" data-href="emprestimos/visualizar.php" data-id="<?= htmlspecialchars($e['id']) ?>">
                                    <td class="text-truncate" title="<?= htmlspecialchars($e['cliente_nome']) ?>">
                                        <?= htmlspecialchars($e['cliente_nome']) ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php
                                            $tipos = [
                                                'parcelada_comum' => 'Parcelamento Comum',
                                                'reparcelada_com_juros' => 'Reparcelado c/ Juros'
                                            ];
                                            $tipo = $e['tipo_de_cobranca'] ?? '';
                                            echo htmlspecialchars($tipos[$tipo] ?? 'Não definido');
                                        ?>
                                    </td>
                                    <td>R$ <?= number_format((float)$e['valor_emprestado'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php
                                            // Calcula o status baseado nas parcelas do JSON
                                            $statusClasse = '';
                                            $status = 'Ativo';
                                            
                                            if (!empty($e['json_parcelas'])) {
                                                $parcelas = json_decode($e['json_parcelas'], true);
                                                $todas_pagas = true;
                                                $tem_atrasada = false;
                                                
                                                if (is_array($parcelas)) {
                                                    foreach ($parcelas as $p) {
                                                        if (empty($p['paga'])) {
                                                            $todas_pagas = false;
                                                            
                                                            // Verifica se está atrasada
                                                            if (!empty($p['data'])) {
                                                                $data_vencimento = DateTime::createFromFormat('d/m/Y', $p['data']);
                                                                if ($data_vencimento && $data_vencimento < new DateTime()) {
                                                                    $tem_atrasada = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                if ($todas_pagas) {
                                                    $status = 'Quitado';
                                                } elseif ($tem_atrasada) {
                                                    $status = 'Atrasado';
                                                }
                                            }
                                            
                                            switch (strtolower($status)) {
                                                case 'ativo':
                                                    $statusClasse = 'text-primary bg-light border border-primary';
                                                    break;
                                                case 'atrasado':
                                                    $statusClasse = 'text-danger bg-light border border-danger';
                                                    break;
                                                case 'quitado':
                                                    $statusClasse = 'text-success bg-light border border-success';
                                                    break;
                                                default:
                                                    $statusClasse = 'text-secondary bg-light border';
                                            }
                                        ?>
                                        <span class="badge rounded-pill fw-semibold <?= $statusClasse ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $parcelas_pagas = 0;
                                            $total_parcelas = (int)$e['parcelas'];
                                            
                                            if (!empty($e['json_parcelas'])) {
                                                $parcelas = json_decode($e['json_parcelas'], true);
                                                if (is_array($parcelas)) {
                                                    foreach ($parcelas as $p) {
                                                        if (!empty($p['paga'])) {
                                                            $parcelas_pagas++;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            echo "{$parcelas_pagas}/{$total_parcelas}";
                                        ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <form action="emprestimos/visualizar.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
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
        padding: 0.5rem 1rem;
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.clickable-row');
        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Previne o clique se o usuário clicar em um botão ou formulário
                if (e.target.closest('button') || e.target.closest('form')) {
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
                    const status = linha.querySelector('td:nth-child(4) .badge').textContent.trim();
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
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php' ?>
