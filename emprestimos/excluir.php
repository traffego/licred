<?php
// Instruções de saída de buffer (não remova esta linha)
ob_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';

// Verifica se o ID foi passado
$emprestimo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$emprestimo_id) {
    header("Location: index.php?erro=1&msg=" . urlencode("ID do empréstimo não informado"));
    exit;
}

// Busca informações do empréstimo
$stmt = $conn->prepare("
    SELECT e.*, c.nome AS cliente_nome 
    FROM emprestimos e 
    JOIN clientes c ON e.cliente_id = c.id 
    WHERE e.id = ?
");
$stmt->bind_param("i", $emprestimo_id);
$stmt->execute();
$emprestimo = $stmt->get_result()->fetch_assoc();

if (!$emprestimo) {
    header("Location: index.php?erro=1&msg=" . urlencode("Empréstimo não encontrado"));
    exit;
}

// Busca parcelas pagas
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_pagas
    FROM parcelas
    WHERE emprestimo_id = ? AND (status = 'pago' OR status = 'parcial')
");
$stmt->bind_param("i", $emprestimo_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$tem_parcelas_pagas = ($result['total_pagas'] > 0);

// Verifica se a confirmação foi dada
$confirmado = filter_input(INPUT_GET, 'confirmar', FILTER_VALIDATE_INT);

if ($confirmado == 1) {
    // Inicia a transação
    $conn->begin_transaction();
    
    try {
        // Se tiver parcelas pagas, marca o empréstimo como inativo em vez de excluir
        if ($tem_parcelas_pagas) {
            // Atualiza o status do empréstimo para inativo
            $stmt = $conn->prepare("UPDATE emprestimos SET status = 'inativo' WHERE id = ?");
            $stmt->bind_param("i", $emprestimo_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao inativar o empréstimo: " . $stmt->error);
            }
            
            // Confirma a transação
            $conn->commit();
            header("Location: index.php?sucesso=1&msg=" . urlencode("Empréstimo inativado com sucesso!"));
            exit;
        } else {
            // Exclui as parcelas primeiro
            $stmt = $conn->prepare("DELETE FROM parcelas WHERE emprestimo_id = ?");
            $stmt->bind_param("i", $emprestimo_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao excluir as parcelas: " . $stmt->error);
            }
            
            // Depois exclui o empréstimo
            $stmt = $conn->prepare("DELETE FROM emprestimos WHERE id = ?");
            $stmt->bind_param("i", $emprestimo_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao excluir o empréstimo: " . $stmt->error);
            }
            
            // Confirma a transação
            $conn->commit();
            header("Location: index.php?sucesso=1&msg=" . urlencode("Empréstimo excluído com sucesso!"));
            exit;
        }
    } catch (Exception $e) {
        // Em caso de erro, reverte a transação
        $conn->rollback();
        header("Location: index.php?erro=1&msg=" . urlencode("Erro ao excluir: " . $e->getMessage()));
        exit;
    }
}

// Inclui o header HTML
require_once __DIR__ . '/../includes/head.php';
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmação de Exclusão</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h5 class="alert-heading"><i class="bi bi-exclamation-circle-fill me-2"></i>Atenção!</h5>
                <?php if ($tem_parcelas_pagas): ?>
                    <p>Este empréstimo possui parcelas já pagas, portanto ele será apenas <strong>INATIVADO</strong> e não excluído permanentemente.</p>
                    <p>Empréstimos inativados não aparecerão nas listagens, mas seus dados serão mantidos no sistema para referência futura.</p>
                <?php else: ?>
                    <p>Você está prestes a <strong>EXCLUIR PERMANENTEMENTE</strong> este empréstimo e todas as suas parcelas.</p>
                    <p>Esta ação não poderá ser desfeita!</p>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <h6>Informações do Empréstimo:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Cliente:</span>
                                <strong><?= htmlspecialchars($emprestimo['cliente_nome']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Valor:</span>
                                <strong>R$ <?= number_format($emprestimo['valor_emprestado'], 2, ',', '.') ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Parcelas:</span>
                                <strong><?= $emprestimo['parcelas'] ?>x de R$ <?= number_format($emprestimo['valor_parcela'], 2, ',', '.') ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Data de Início:</span>
                                <strong><?= date('d/m/Y', strtotime($emprestimo['data_inicio'])) ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Cancelar
                </a>
                <a href="excluir.php?id=<?= $emprestimo_id ?>&confirmar=1" class="btn btn-danger">
                    <?php if ($tem_parcelas_pagas): ?>
                        <i class="bi bi-x-circle me-2"></i>Inativar Empréstimo
                    <?php else: ?>
                        <i class="bi bi-trash me-2"></i>Excluir Permanentemente
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 