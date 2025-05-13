<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/head.php';

// Verificar se é um investidor acessando suas próprias movimentações
$is_investidor = isset($_SESSION['nivel_autoridade']) && $_SESSION['nivel_autoridade'] === 'investidor';
$is_admin = temPermissao('admin');
$meus_aportes = isset($_GET['meus_aportes']) && $_GET['meus_aportes'] == 1;
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Verificar permissões
if (!$is_admin && !$is_investidor) {
    header("Location: ../index.php");
    exit;
}

// Processar ações
$mensagem = '';
$tipo_alerta = '';

// Verificar se um ID de conta foi fornecido
if ($is_investidor && $meus_aportes) {
    // Se for investidor acessando "meus aportes", buscar automaticamente a conta do investidor
    $stmt = $conn->prepare("SELECT id FROM contas WHERE usuario_id = ? AND status = 'ativo' LIMIT 1");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conta_id = $result->fetch_assoc()['id'];
    } else {
        // Investidor não tem conta, redirecionar para página do investidor
        header("Location: ../investidor.php?erro=1&msg=" . urlencode("Você não possui uma conta ativa."));
        exit;
    }
} elseif (!isset($_GET['conta_id']) || empty($_GET['conta_id'])) {
    // Não é o caso especial de "meus aportes" e não tem conta_id
    header("Location: " . ($is_admin ? "contas.php" : "../investidor.php"));
    exit;
} else {
    $conta_id = intval($_GET['conta_id']);
}

// Obter detalhes da conta
$stmt = $conn->prepare("SELECT c.*, u.nome as usuario_nome FROM contas c 
                        JOIN usuarios u ON c.usuario_id = u.id 
                        WHERE c.id = ?");
$stmt->bind_param("i", $conta_id);
$stmt->execute();
$conta = $stmt->get_result()->fetch_assoc();

if (!$conta) {
    header("Location: " . ($is_admin ? "contas.php" : "../investidor.php"));
    exit;
}

// Verificar se o investidor está tentando acessar sua própria conta
if ($is_investidor && !$is_admin && $conta['usuario_id'] != $usuario_id) {
    // Tentativa de acesso a conta de outro investidor
    $_SESSION['acesso_negado'] = true;
    $_SESSION['acesso_negado_mensagem'] = "Você só pode visualizar movimentações da sua própria conta!";
    $_SESSION['pagina_tentativa'] = $_SERVER['REQUEST_URI'];
    header("Location: ../investidor.php?erro=acesso_negado");
    exit;
}

// Processar adição de nova movimentação (apenas admin)
if ($is_admin && isset($_POST['adicionar_movimentacao'])) {
    $tipo = $_POST['tipo'];
    $valor = floatval(str_replace(['.', ','], ['', '.'], $_POST['valor']));
    $descricao = trim($_POST['descricao']);
    $data_movimentacao = $_POST['data_movimentacao'];
    
    if (empty($tipo) || empty($valor) || $valor <= 0) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios corretamente.";
        $tipo_alerta = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO movimentacoes_contas (conta_id, tipo, valor, descricao, data_movimentacao) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $conta_id, $tipo, $valor, $descricao, $data_movimentacao);
        
        if ($stmt->execute()) {
            $mensagem = "Movimentação adicionada com sucesso!";
            $tipo_alerta = "success";
        } else {
            $mensagem = "Erro ao adicionar movimentação: " . $conn->error;
            $tipo_alerta = "danger";
        }
    }
}

// Excluir movimentação (apenas admin)
if ($is_admin && isset($_POST['excluir_movimentacao']) && isset($_POST['movimentacao_id'])) {
    $movimentacao_id = intval($_POST['movimentacao_id']);
    
    $stmt = $conn->prepare("DELETE FROM movimentacoes_contas WHERE id = ? AND conta_id = ?");
    $stmt->bind_param("ii", $movimentacao_id, $conta_id);
    
    if ($stmt->execute()) {
        $mensagem = "Movimentação excluída com sucesso!";
        $tipo_alerta = "success";
    } else {
        $mensagem = "Erro ao excluir movimentação: " . $conn->error;
        $tipo_alerta = "danger";
    }
}

// Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Total de movimentações
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM movimentacoes_contas WHERE conta_id = ?");
$stmt->bind_param("i", $conta_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_rows = $row['total'];
$total_pages = ceil($total_rows / $per_page);

// Consulta das movimentações
$stmt = $conn->prepare("SELECT * FROM movimentacoes_contas 
                       WHERE conta_id = ? 
                       ORDER BY data_movimentacao DESC, id DESC 
                       LIMIT ?, ?");
$stmt->bind_param("iii", $conta_id, $offset, $per_page);
$stmt->execute();
$movimentacoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular saldo atual
$stmt = $conn->prepare("SELECT 
                        SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) as total_movimentacoes
                       FROM movimentacoes_contas 
                       WHERE conta_id = ?");
$stmt->bind_param("i", $conta_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_movimentacoes = $row['total_movimentacoes'] ?: 0;
$saldo_atual = $conta['saldo_inicial'] + $total_movimentacoes;

// Definir o título da página
$titulo_pagina = $is_investidor && $meus_aportes ? "Histórico de Aportes" : "Movimentações da Conta";
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><?= $titulo_pagina ?></h2>
            <p class="text-muted mb-0">
                <i class="bi bi-person-fill"></i> <?= htmlspecialchars($conta['usuario_nome']) ?> - 
                <i class="bi bi-wallet2"></i> <?= htmlspecialchars($conta['nome']) ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $is_investidor && !$is_admin ? '../investidor.php' : 'contas.php' ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <?php if ($is_admin): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adicionarMovimentacaoModal">
                <i class="bi bi-plus-circle"></i> Nova Movimentação
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($mensagem): ?>
        <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
            <?= $mensagem ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    
    <!-- Card de resumo -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Saldo Inicial</h6>
                    <h4 class="mb-0">R$ <?= number_format($conta['saldo_inicial'], 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total de Movimentações</h6>
                    <h4 class="mb-0 <?= $total_movimentacoes < 0 ? 'text-danger' : 'text-success' ?>">
                        R$ <?= number_format($total_movimentacoes, 2, ',', '.') ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Saldo Atual</h6>
                    <h4 class="mb-0 <?= $saldo_atual < 0 ? 'text-danger' : 'text-success' ?>">
                        R$ <?= number_format($saldo_atual, 2, ',', '.') ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabela de movimentações -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($movimentacoes)): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> Nenhuma movimentação encontrada para esta conta.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Descrição</th>
                                <?php if ($is_admin): ?>
                                <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $saldo_calculado = $conta['saldo_inicial'];
                            $movimentacoes_ordenadas = array_reverse($movimentacoes); // Ordem cronológica
                            
                            foreach ($movimentacoes_ordenadas as $mov): 
                                $valor = floatval($mov['valor']);
                                if ($mov['tipo'] === 'entrada') {
                                    $saldo_calculado += $valor;
                                } else {
                                    $saldo_calculado -= $valor;
                                }
                                
                                $tipo_class = $mov['tipo'] === 'entrada' ? 'text-success' : 'text-danger';
                                $tipo_texto = $mov['tipo'] === 'entrada' ? 'Entrada' : 'Saída';
                                $tipo_icone = $mov['tipo'] === 'entrada' ? 'arrow-up-circle-fill' : 'arrow-down-circle-fill';
                            ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($mov['data_movimentacao'])) ?></td>
                                    <td>
                                        <span class="<?= $tipo_class ?>">
                                            <i class="bi bi-<?= $tipo_icone ?>"></i> <?= $tipo_texto ?>
                                        </span>
                                    </td>
                                    <td class="<?= $tipo_class ?> fw-bold">
                                        R$ <?= number_format($valor, 2, ',', '.') ?>
                                    </td>
                                    <td><?= htmlspecialchars($mov['descricao']) ?></td>
                                    <?php if ($is_admin): ?>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger excluir-movimentacao" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#excluirMovimentacaoModal"
                                                data-id="<?= $mov['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navegação de movimentações">
                        <ul class="pagination justify-content-center mt-3">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= $meus_aportes ? 'meus_aportes=1' : "conta_id=$conta_id" ?>&page=<?= $page-1 ?>">Anterior</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= $meus_aportes ? 'meus_aportes=1' : "conta_id=$conta_id" ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= $meus_aportes ? 'meus_aportes=1' : "conta_id=$conta_id" ?>&page=<?= $page+1 ?>">Próxima</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($is_admin): ?>
<!-- Modal para Adicionar Movimentação -->
<div class="modal fade" id="adicionarMovimentacaoModal" tabindex="-1" aria-labelledby="adicionarMovimentacaoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Movimentação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor (R$)</label>
                        <input type="text" class="form-control money" id="valor" name="valor" value="0,00" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="data_movimentacao" class="form-label">Data</label>
                        <input type="date" class="form-control" id="data_movimentacao" name="data_movimentacao" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="adicionar_movimentacao" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="excluirMovimentacaoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta movimentação?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> Esta ação não poderá ser desfeita.
                </div>
            </div>
            <div class="modal-footer">
                <form method="post">
                    <input type="hidden" name="movimentacao_id" id="movimentacao_id_excluir">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="excluir_movimentacao" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para campos monetários
    document.querySelectorAll('.money').forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2).replace('.', ',');
            e.target.value = value;
        });
    });
    
    // Inicializar campos de valor
    document.querySelectorAll('.money').forEach(function(input) {
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
    });
    
    // Configurar modal de exclusão
    document.querySelectorAll('.excluir-movimentacao').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('movimentacao_id_excluir').value = this.getAttribute('data-id');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 