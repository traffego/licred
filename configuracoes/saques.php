<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/autenticacao.php';

// Verificar permissões administrativas
apenasAdmin();

require_once __DIR__ . '/../includes/head.php';

// Verificar se a tabela existe
$table_exists = $conn->query("SHOW TABLES LIKE 'solicitacoes_saque'");
if ($table_exists->num_rows == 0) {
    // Criar a tabela se não existir
    $sql_create_table = "CREATE TABLE IF NOT EXISTS solicitacoes_saque (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        usuario_id INT NOT NULL, 
        conta_id INT NOT NULL, 
        valor DECIMAL(10,2) NOT NULL, 
        status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente', 
        descricao TEXT, 
        data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP, 
        data_processamento DATETIME NULL, 
        observacao_admin TEXT
    )";
    $conn->query($sql_create_table);
}

// Processar aprovação/rejeição de saque
if (isset($_POST['processar_saque'])) {
    $saque_id = intval($_POST['saque_id']);
    $status = $_POST['status'];
    $observacao_admin = trim($_POST['observacao_admin']);
    
    if (!in_array($status, ['aprovado', 'rejeitado'])) {
        $mensagem = "Status inválido.";
        $tipo_alerta = "danger";
    } else {
        // Buscar informações da solicitação
        $stmt_info = $conn->prepare("SELECT s.*, c.id as conta_id FROM solicitacoes_saque s 
                                     INNER JOIN contas c ON s.conta_id = c.id 
                                     WHERE s.id = ? LIMIT 1");
        $stmt_info->bind_param("i", $saque_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        
        if ($result_info && $result_info->num_rows > 0) {
            $saque_info = $result_info->fetch_assoc();
            
            // Iniciar transação
            $conn->begin_transaction();
            
            try {
                // Atualizar status da solicitação
                $stmt_atualizar = $conn->prepare("UPDATE solicitacoes_saque 
                                                 SET status = ?, data_processamento = NOW(), observacao_admin = ? 
                                                 WHERE id = ?");
                $stmt_atualizar->bind_param("ssi", $status, $observacao_admin, $saque_id);
                
                if (!$stmt_atualizar->execute()) {
                    throw new Exception("Erro ao atualizar solicitação: " . $stmt_atualizar->error);
                }
                
                // Se aprovado, registrar saída na conta
                if ($status === 'aprovado') {
                    $conta_id = $saque_info['conta_id'];
                    $valor = $saque_info['valor'];
                    $descricao = "Saque aprovado (Solicitação #" . $saque_id . ")";
                    
                    $stmt_movimentacao = $conn->prepare("INSERT INTO movimentacoes_contas (conta_id, tipo, valor, descricao, data_movimentacao) 
                                                       VALUES (?, 'saida', ?, ?, NOW())");
                    $stmt_movimentacao->bind_param("ids", $conta_id, $valor, $descricao);
                    
                    if (!$stmt_movimentacao->execute()) {
                        throw new Exception("Erro ao registrar movimentação: " . $stmt_movimentacao->error);
                    }
                }
                
                // Commit das operações
                $conn->commit();
                
                $mensagem = "Solicitação de saque " . ($status === 'aprovado' ? "aprovada" : "rejeitada") . " com sucesso!";
                $tipo_alerta = "success";
                
            } catch (Exception $e) {
                // Rollback em caso de erro
                $conn->rollback();
                $mensagem = "Erro ao processar solicitação: " . $e->getMessage();
                $tipo_alerta = "danger";
            }
        } else {
            $mensagem = "Solicitação de saque não encontrada.";
            $tipo_alerta = "danger";
        }
    }
}

// Buscar solicitações de saque pendentes
$sql_saques = "SELECT s.*, u.nome as usuario_nome, c.nome as conta_nome 
               FROM solicitacoes_saque s 
               INNER JOIN usuarios u ON s.usuario_id = u.id 
               INNER JOIN contas c ON s.conta_id = c.id 
               ORDER BY 
                  CASE WHEN s.status = 'pendente' THEN 0 ELSE 1 END, 
                  s.data_solicitacao DESC";
$result_saques = $conn->query($sql_saques);
$saques = [];

if ($result_saques && $result_saques->num_rows > 0) {
    while ($saque = $result_saques->fetch_assoc()) {
        $saques[] = $saque;
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Solicitações de Saque</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <?php if (isset($mensagem)): ?>
    <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($mensagem) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($saques)): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>Nenhuma solicitação de saque encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Investidor</th>
                                <th>Conta</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($saques as $saque): 
                                $status_class = '';
                                switch ($saque['status']) {
                                    case 'pendente':
                                        $status_class = 'bg-warning';
                                        break;
                                    case 'aprovado':
                                        $status_class = 'bg-success';
                                        break;
                                    case 'rejeitado':
                                        $status_class = 'bg-danger';
                                        break;
                                }
                            ?>
                                <tr class="<?= $saque['status'] === 'pendente' ? 'table-warning' : '' ?>">
                                    <td><?= $saque['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($saque['data_solicitacao'])) ?></td>
                                    <td><?= htmlspecialchars($saque['usuario_nome']) ?></td>
                                    <td><?= htmlspecialchars($saque['conta_nome']) ?></td>
                                    <td>R$ <?= number_format($saque['valor'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge <?= $status_class ?>">
                                            <?= ucfirst($saque['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($saque['status'] === 'pendente'): ?>
                                            <button class="btn btn-sm btn-primary processar-saque" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#processarSaqueModal"
                                                    data-id="<?= $saque['id'] ?>"
                                                    data-usuario="<?= htmlspecialchars($saque['usuario_nome']) ?>"
                                                    data-conta="<?= htmlspecialchars($saque['conta_nome']) ?>"
                                                    data-valor="<?= number_format($saque['valor'], 2, ',', '.') ?>"
                                                    data-descricao="<?= htmlspecialchars($saque['descricao']) ?>">
                                                <i class="bi bi-check2-circle"></i> Processar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-info visualizar-saque"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#visualizarSaqueModal"
                                                    data-id="<?= $saque['id'] ?>"
                                                    data-usuario="<?= htmlspecialchars($saque['usuario_nome']) ?>"
                                                    data-conta="<?= htmlspecialchars($saque['conta_nome']) ?>"
                                                    data-valor="<?= number_format($saque['valor'], 2, ',', '.') ?>"
                                                    data-descricao="<?= htmlspecialchars($saque['descricao']) ?>"
                                                    data-status="<?= $saque['status'] ?>"
                                                    data-data-processamento="<?= date('d/m/Y H:i', strtotime($saque['data_processamento'])) ?>"
                                                    data-observacao="<?= htmlspecialchars($saque['observacao_admin']) ?>">
                                                <i class="bi bi-eye"></i> Detalhes
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal para Processar Solicitação de Saque -->
<div class="modal fade" id="processarSaqueModal" tabindex="-1" aria-labelledby="processarSaqueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processarSaqueModalLabel">Processar Solicitação de Saque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" id="saque_id" name="saque_id">
                    
                    <div class="alert alert-info mb-3">
                        <div class="mb-2"><strong>Investidor:</strong> <span id="modal_usuario"></span></div>
                        <div class="mb-2"><strong>Conta:</strong> <span id="modal_conta"></span></div>
                        <div class="mb-2"><strong>Valor:</strong> R$ <span id="modal_valor"></span></div>
                        <div><strong>Motivo:</strong> <span id="modal_descricao">-</span></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Decisão:</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="status" id="status_aprovado" value="aprovado" checked>
                            <label class="form-check-label" for="status_aprovado">
                                <span class="text-success"><i class="bi bi-check-circle"></i> Aprovar</span>
                                <small class="text-muted d-block">O valor será debitado da conta do investidor.</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_rejeitado" value="rejeitado">
                            <label class="form-check-label" for="status_rejeitado">
                                <span class="text-danger"><i class="bi bi-x-circle"></i> Rejeitar</span>
                                <small class="text-muted d-block">A solicitação será rejeitada sem movimentação na conta.</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacao_admin" class="form-label">Observação (opcional):</label>
                        <textarea class="form-control" id="observacao_admin" name="observacao_admin" rows="2" placeholder="Informe uma observação para o investidor (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="processar_saque" class="btn btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualizar Detalhes do Saque -->
<div class="modal fade" id="visualizarSaqueModal" tabindex="-1" aria-labelledby="visualizarSaqueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="visualizarSaqueModalLabel">Detalhes da Solicitação de Saque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Informações da Solicitação</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2"><strong>Investidor:</strong> <span id="view_usuario"></span></div>
                        <div class="mb-2"><strong>Conta:</strong> <span id="view_conta"></span></div>
                        <div class="mb-2"><strong>Valor:</strong> R$ <span id="view_valor"></span></div>
                        <div class="mb-2"><strong>Data da Solicitação:</strong> <span id="view_data_solicitacao"></span></div>
                        <div><strong>Motivo:</strong> <span id="view_descricao">-</span></div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header" id="view_status_header">
                        <h6 class="mb-0">Resultado do Processamento</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2"><strong>Status:</strong> <span id="view_status"></span></div>
                        <div class="mb-2"><strong>Data do Processamento:</strong> <span id="view_data_processamento"></span></div>
                        <div><strong>Observação do Administrador:</strong> <span id="view_observacao">-</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preencher o modal de processar saque
    document.querySelectorAll('.processar-saque').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('saque_id').value = this.getAttribute('data-id');
            document.getElementById('modal_usuario').textContent = this.getAttribute('data-usuario');
            document.getElementById('modal_conta').textContent = this.getAttribute('data-conta');
            document.getElementById('modal_valor').textContent = this.getAttribute('data-valor');
            document.getElementById('modal_descricao').textContent = this.getAttribute('data-descricao') || '-';
        });
    });
    
    // Preencher o modal de visualizar saque
    document.querySelectorAll('.visualizar-saque').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('view_usuario').textContent = this.getAttribute('data-usuario');
            document.getElementById('view_conta').textContent = this.getAttribute('data-conta');
            document.getElementById('view_valor').textContent = this.getAttribute('data-valor');
            document.getElementById('view_data_solicitacao').textContent = this.getAttribute('data-data-solicitacao');
            document.getElementById('view_descricao').textContent = this.getAttribute('data-descricao') || '-';
            
            const status = this.getAttribute('data-status');
            const statusHeader = document.getElementById('view_status_header');
            let statusBadge = '';
            
            if (status === 'aprovado') {
                statusHeader.className = 'card-header bg-success text-white';
                statusBadge = '<span class="badge bg-success">Aprovado</span>';
            } else {
                statusHeader.className = 'card-header bg-danger text-white';
                statusBadge = '<span class="badge bg-danger">Rejeitado</span>';
            }
            
            document.getElementById('view_status').innerHTML = statusBadge;
            document.getElementById('view_data_processamento').textContent = this.getAttribute('data-data-processamento');
            document.getElementById('view_observacao').textContent = this.getAttribute('data-observacao') || '-';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 