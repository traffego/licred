<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/autenticacao.php';
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/queries.php';

$emprestimos = buscarTodosEmprestimosComCliente($conn);
$ultimos_emprestimos = array_slice($emprestimos, 0, 5); // Pega apenas os 5 mais recentes
$total_atrasado = calcularTotalParcelasAtrasadas($conn);
$total_emprestimos_ativos = contarEmprestimosAtivos($conn);
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
                <span class="subtitle py-2">R$ <?= number_format($total_atrasado, 2, ',', '.') ?></span>
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
                <span class="subtitle py-2"><?= $total_emprestimos_ativos ?> contratos</span>
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
                        <?php foreach ($ultimos_emprestimos as $e): ?>
                            <tr class="clickable-row" data-href="emprestimos/visualizar.php" data-id="<?= $e['id'] ?>">
                                <td class="text-truncate" title="<?= htmlspecialchars($e['cliente_nome']) ?>">
                                    <?= htmlspecialchars($e['cliente_nome']) ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php
                                        $tipos = ['gota' => 'Dia a Dia', 'quitacao' => 'Quitação'];
                                        echo $tipos[$e['tipo'] ?? ''];
                                    ?>
                                </td>
                                <td>R$ <?= number_format($e['valor_emprestado'], 2, ',', '.') ?></td>
                                <td>
                                    <?php
                                        $statusClasse = '';
                                        switch (strtolower($e['status'])) {
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
                                        <?= ucfirst($e['status']) ?>
                                    </span>
                                </td>
                                <td><?= $e['parcelas_pagas'] ?>/<?= $e['total_parcelas'] ?></td>
                                <td class="d-none d-md-table-cell">
                                    <form action="emprestimos/visualizar.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.clickable-row');
        rows.forEach(row => {
            row.addEventListener('click', function() {
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
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php' ?>
