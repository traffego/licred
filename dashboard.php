<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/autenticacao.php';
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/queries.php';

$emprestimos = buscarTodosEmprestimosComCliente($conn);
$ultimos_emprestimos = array_slice($emprestimos, 0, 5); // Pega apenas os 5 mais recentes
?>

<div class="container py-1">
    <h2 class="mb-4 text-uppercase">🚀 Painel Financeiro</h2>
    <div class="row g-4 mb-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="emprestimos/novo.php" class="btn btn-lg btn-success w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-plus-circle-fill"></i>
                                Novo Empréstimo
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="clientes/novo.php" class="btn btn-lg btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-person-plus-fill"></i>
                                Novo Cliente
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="relatorios/diario.php" class="btn btn-lg btn-info w-100 d-flex align-items-center justify-content-center gap-2 text-white">
                                <i class="bi bi-graph-up"></i>
                                Relatório Diário
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="cobrancas/pendentes.php" class="btn btn-lg btn-warning w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-bell-fill"></i>
                                Cobranças Pendentes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-md-4 mb-3">
            <div class="cardBox bg-red icon-bg-bi" data-icon="bi-exclamation-triangle">
                <span class="title">Parcelas atrasadas</span>
                <span class="subtitle py-2">R$25.900,23</span>
            </div>
        </div>

        <div class="col-12 col-md-4 mb-3">
            <div class="cardBox bg-green icon-bg-bi" data-icon="bi-cash-coin">
                <span class="title">Saldo Disponível</span>
                <span class="subtitle py-2">R$25.900,23</span>
            </div>
        </div>

        <div class="col-12 col-md-4 mb-3">
            <div class="cardBox bg-dark-sapphire icon-bg-bi" data-icon="bi-receipt">
                <span class="title">Emprestimos</span>
                <span class="subtitle py-2">5 contratos</span>
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
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
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
