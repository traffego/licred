<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/queries.php';

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo '<div class="container py-4"><div class="alert alert-danger">ID do empréstimo não recebido.</div></div>';
    exit;
}

$emprestimo_id = (int) $_POST['id'];

// Buscar informações básicas do empréstimo
$stmt = $conn->prepare("SELECT e.*, c.nome AS cliente_nome, c.cpf, c.telefone FROM emprestimos e JOIN clientes c ON e.cliente_id = c.id WHERE e.id = ?");
$stmt->bind_param("i", $emprestimo_id);
$stmt->execute();
$emprestimo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$emprestimo) {
    echo '<div class="container py-4"><div class="alert alert-danger">Empréstimo não encontrado.</div></div>';
    exit;
}

// Buscar resumo do empréstimo
$resumo = buscarResumoEmprestimoId($conn, $emprestimo_id);

// Calcular status das parcelas
$pagas = 0;
$parciais = 0;
$pendentes = 0;
$vencidas = 0;

foreach ($resumo['parcelas'] as $p) {
    if (!empty($p['paga'])) {
        $pagas++;
    } elseif (!empty($p['valor_pago']) && $p['valor_pago'] > 0) {
        $parciais++;
    } elseif (!empty($p['data']) && DateTime::createFromFormat('d/m/Y', $p['data']) < new DateTime()) {
        $vencidas++;
    } else {
        $pendentes++;
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes do Empréstimo #<?= $emprestimo['id'] ?></h2>
        <div>
            <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary me-2">← Voltar</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPagarMassa">
                <i class="bi bi-cash-stack"></i> Pagamento em Massa
            </button>
        </div>
    </div>

    <div class="row g-4">
        <!-- Card Cliente -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header cliente-header">
                    <h5 class="card-title mb-0">Cliente</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3"><?= htmlspecialchars($emprestimo['cliente_nome']) ?></h6>
                    <p class="mb-2"><i class="bi bi-person-vcard me-2"></i> CPF: <?= formatarCPF($emprestimo['cpf']) ?></p>
                    <p class="mb-0"><i class="bi bi-telephone me-2"></i> Tel: <?= formatarTelefone($emprestimo['telefone']) ?></p>
                </div>
            </div>
        </div>

        <!-- Card Valores -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header valores-header">
                    <h5 class="card-title mb-0">Valores</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Capital:</label>
                        <h6 class="mb-0">R$ <?= number_format($emprestimo['valor'], 2, ',', '.') ?></h6>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Juros:</label>
                        <h6 class="mb-0"><?= number_format($emprestimo['juros'], 2, ',', '') ?>%</h6>
                    </div>
                    <div>
                        <label class="text-muted small">Total:</label>
                        <h6 class="mb-0">R$ <?= number_format($resumo['total_previsto'], 2, ',', '.') ?></h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Status das Parcelas -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header status-header">
                    <h5 class="card-title mb-0">Status das Parcelas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="text-muted small d-block">Pagas:</label>
                            <h6 class="mb-3"><?= $pagas ?></h6>
                            
                            <label class="text-muted small d-block">Parciais:</label>
                            <h6 class="mb-0"><?= $parciais ?></h6>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small d-block">Pendentes:</label>
                            <h6 class="mb-3"><?= $pendentes ?></h6>
                            
                            <label class="text-muted small d-block">Vencidas:</label>
                            <h6 class="mb-0"><?= $vencidas ?></h6>
                        </div>
                    </div>
                    <?php
                        $total_parcelas = count($resumo['parcelas']);
                        $percentual_pago = ($pagas / $total_parcelas) * 100;
                        
                        // Define a cor baseada no percentual
                        if ($percentual_pago < 25) {
                            $cor = '#dc3545'; // Vermelho
                        } elseif ($percentual_pago < 50) {
                            $cor = '#ffc107'; // Amarelo
                        } elseif ($percentual_pago < 75) {
                            $cor = '#28a745'; // Verde
                        } else {
                            $cor = '#198754'; // Verde escuro
                        }
                    ?>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= $percentual_pago ?>%; background-color: <?= $cor ?>;" 
                             aria-valuenow="<?= $percentual_pago ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Resumo Financeiro -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header resumo-header">
                    <h5 class="card-title mb-0">Resumo Financeiro</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Valor Pago:</label>
                        <h6 class="text-success mb-0">R$ <?= number_format($resumo['total_pago'], 2, ',', '.') ?></h6>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Juros por Atraso:</label>
                        <h6 class="text-danger mb-0">R$ <?= number_format($resumo['juros_atraso'] ?? 0, 2, ',', '.') ?></h6>
                    </div>
                    <div>
                        <label class="text-muted small">Valor Pendente:</label>
                        <h6 class="text-warning mb-0">R$ <?= number_format($resumo['falta'], 2, ',', '.') ?></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Parcelas -->
    <div class="card mt-4">
        <div class="card-header parcelas-header">
            <h5 class="card-title mb-0">Parcelas</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Vencimento</th>
                            <th>Valor Original</th>
                            <th>Valor Final</th>
                            <th>Juros/Atraso</th>
                            <th>Recebido</th>
                            <th>Status</th>
                            <th>Doc</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumo['parcelas'] as $p): 
                            $valor_original = (float)str_replace(',', '.', $p['valor']);
                            $valor_final = $valor_original;
                            $juros_atraso = 0;
                            
                            if (!empty($p['data']) && empty($p['paga'])) {
                                $vencimento = DateTime::createFromFormat('d/m/Y', $p['data']);
                                if ($vencimento < new DateTime()) {
                                    // Calcula juros de atraso
                                    $dias_atraso = $vencimento->diff(new DateTime())->days;
                                    $juros_atraso = $valor_original * (0.1 * $dias_atraso); // 10% ao dia
                                    $valor_final = $valor_original + $juros_atraso;
                                }
                            }
                        ?>
                            <tr>
                                <td><?= $p['parcela'] ?></td>
                                <td><?= $p['data'] ?></td>
                                <td>R$ <?= number_format($valor_original, 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($valor_final, 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($juros_atraso, 2, ',', '.') ?></td>
                                <td><?= !empty($p['valor_pago']) ? 'R$ ' . number_format($p['valor_pago'], 2, ',', '.') : '-' ?></td>
                                <td>
                                    <?php
                                        if (!empty($p['paga'])) {
                                            echo '<span class="badge bg-success bg-opacity-10 text-success px-2 py-1">Pago</span>';
                                        } elseif (!empty($p['valor_pago']) && $p['valor_pago'] > 0) {
                                            echo '<span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1">Parcial</span>';
                                        } elseif (!empty($p['data']) && DateTime::createFromFormat('d/m/Y', $p['data']) < new DateTime()) {
                                            echo '<span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1">Pendente</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">Pendente</span>';
                                        }
                                    ?>
                                </td>
                                <td>-</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" title="Recibo">
                                            <i class="bi bi-receipt"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" title="Pagar Parcela">
                                            <i class="bi bi-cash-coin"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-whatsapp" title="WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    $tamanho = strlen($telefone);
    if ($tamanho == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }
    return $telefone;
}
?>

<style>
/* Estilos para os cards */
.card {
    border: 1px solid rgba(0,0,0,.125);
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
}

.card-header {
    padding: 1rem;
    color: white;
}

.card-header.cliente-header {
    background: linear-gradient(45deg, #2c7744, #1a4e2c);
}

.card-header.valores-header {
    background: linear-gradient(45deg, #277553, #1b503a);
}

.card-header.status-header {
    background: linear-gradient(45deg, #246e62, #194d45);
}

.card-header.resumo-header {
    background: linear-gradient(45deg, #1f6470, #17454d);
}

.card-header.parcelas-header {
    background: linear-gradient(45deg, #1b5962, #13404a);
}

.card-title {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
}

.card-body h6 {
    font-size: 1.5rem;
    font-weight: 600;
}

.card-body .text-muted {
    font-size: 0.9rem;
}

.progress {
    height: 10px;
    margin-top: 1rem;
    background-color: #f8f9fa;
    border-radius: 10px;
}

.progress-bar {
    transition: width .6s ease, background-color .6s ease;
    border-radius: 10px;
}

/* Estilos para badges */
.badge {
    font-weight: 500;
    font-size: 0.85rem;
    padding: 0.5em 0.8em;
}

/* Estilos para a tabela */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: white;
    font-size: 0.75rem;
    text-transform: uppercase;
    padding: 0.75rem;
}

.table td {
    font-size: 0.875rem;
    color: #344767;
    vertical-align: middle;
    padding: 0.75rem;
}

/* Estilos para botões */
.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.btn-group .btn i {
    font-size: 1rem;
}

.btn-whatsapp {
    background-color: #25D366;
    border-color: #25D366;
    color: white;
}

.btn-whatsapp:hover {
    background-color: #128C7E;
    border-color: #128C7E;
    color: white;
}

/* Estilos para textos */
.text-muted {
    color: #6c757d !important;
}

.small {
    font-size: 0.875rem;
}

/* Estilos para ícones */
.bi {
    font-size: 1.1rem;
}
</style>

<!-- Modais existentes permanecem aqui -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</rewritten_file> 