<?php
/**
 * Função para verificar e atualizar parcelas vencidas
 * Executa no máximo a cada 10 minutos para não sobrecarregar o sistema
 */

// Verificando se já existe uma conexão ativa com o banco de dados
if (!isset($conn) || !$conn instanceof mysqli) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/conexao.php';
}

/**
 * Verifica se é necessário executar a verificação de parcelas
 * baseado no tempo da última verificação
 * @return bool Verdadeiro se deve verificar, falso caso contrário
 */
function deveVerificarParcelas() {
    // Se foi solicitada verificação forçada via GET, sempre retorna true
    if (isset($_GET['forcar'])) {
        return true;
    }
    
    // Arquivo que armazena timestamp da última verificação
    $arquivo_cache = __DIR__ . '/../cache/ultima_verificacao_parcelas.txt';
    
    // Intervalo mínimo entre verificações (10 minutos = 600 segundos)
    $intervalo_minimo = 600;
    
    // Se o diretório cache não existir, cria
    if (!file_exists(__DIR__ . '/../cache')) {
        mkdir(__DIR__ . '/../cache', 0755, true);
    }
    
    // Se o arquivo não existir, devemos verificar
    if (!file_exists($arquivo_cache)) {
        return true;
    }
    
    // Lê o timestamp da última verificação
    $ultima_verificacao = (int)file_get_contents($arquivo_cache);
    $agora = time();
    
    // Se passou o tempo mínimo, devemos verificar
    return ($agora - $ultima_verificacao) >= $intervalo_minimo;
}

/**
 * Atualiza o timestamp da última verificação
 */
function registrarVerificacao() {
    $arquivo_cache = __DIR__ . '/../cache/ultima_verificacao_parcelas.txt';
    file_put_contents($arquivo_cache, time());
}

/**
 * Verifica e atualiza o status das parcelas vencidas
 * @return array Estatísticas das atualizações realizadas
 */
function verificarAtualizarParcelasVencidas() {
    global $conn;
    
    // Se não deve verificar, retorna sem fazer nada
    if (!deveVerificarParcelas()) {
        return [
            'verificado' => false,
            'mensagem' => 'Verificação ignorada: muito recente'
        ];
    }
    
    // Inicializa estatísticas
    $stats = [
        'verificado' => true,
        'emprestimos_verificados' => 0,
        'parcelas_atualizadas' => 0,
        'erros' => 0,
        'data_hora' => date('Y-m-d H:i:s'),
        'log_erros' => [] // Array para armazenar detalhes dos erros
    ];

    try {
        // Data atual para considerar parcelas atrasadas (sem o -1 day)
        $hoje = new DateTime();
        $hoje_formatado = $hoje->format('Y-m-d');
        
        // Busca empréstimos ativos baseado na tabela de parcelas
        $sql = "SELECT DISTINCT p.emprestimo_id 
                FROM parcelas p 
                INNER JOIN emprestimos e ON p.emprestimo_id = e.id 
                WHERE e.status = 'ativo'";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            $erro_mensagem = "Erro ao executar a consulta: " . $conn->error;
            $stats['log_erros'][] = $erro_mensagem;
            throw new Exception($erro_mensagem);
        }
        
        // Prepara stmt para buscar parcelas não pagas
        $stmt_parcelas = $conn->prepare("
            SELECT id, emprestimo_id, numero, vencimento, status
            FROM parcelas
            WHERE emprestimo_id = ? AND status != 'pago'
        ");
        
        if (!$stmt_parcelas) {
            $erro_mensagem = "Erro ao preparar consulta de parcelas: " . $conn->error;
            $stats['log_erros'][] = $erro_mensagem;
            throw new Exception($erro_mensagem);
        }
        
        // Prepara stmt para atualizar parcelas vencidas
        $stmt_update = $conn->prepare("
            UPDATE parcelas 
            SET status = 'atrasado' 
            WHERE id = ?
        ");
        
        if (!$stmt_update) {
            $erro_mensagem = "Erro ao preparar atualização de parcelas: " . $conn->error;
            $stats['log_erros'][] = $erro_mensagem;
            throw new Exception($erro_mensagem);
        }
        
        // Processa cada empréstimo
        while ($row = $result->fetch_assoc()) {
            $emprestimo_id = $row['emprestimo_id'];
            $stats['emprestimos_verificados']++;
            
            // Busca parcelas não pagas deste empréstimo
            $stmt_parcelas->bind_param("i", $emprestimo_id);
            
            if (!$stmt_parcelas->execute()) {
                $stats['erros']++;
                $stats['log_erros'][] = "Erro ao buscar parcelas do empréstimo ID #{$emprestimo_id}: " . $stmt_parcelas->error;
                continue;
            }
            
            $parcelas_result = $stmt_parcelas->get_result();
            
            // Atualiza cada parcela vencida
            while ($parcela = $parcelas_result->fetch_assoc()) {
                // Verifica se a parcela já venceu (se a data de vencimento é anterior à data atual)
                if ($parcela['vencimento'] < $hoje_formatado && $parcela['status'] !== 'atrasado') {
                    // Atualiza o status para 'atrasado'
                    $parcela_id = $parcela['id'];
                    $stmt_update->bind_param("i", $parcela_id);
                    
                    if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
                        $stats['parcelas_atualizadas']++;
                    } else if ($stmt_update->error) {
                        $stats['erros']++;
                        $stats['log_erros'][] = "Erro ao atualizar parcela ID #{$parcela_id} do empréstimo #{$emprestimo_id}: " . $stmt_update->error;
                    }
                }
            }
        }
        
        $stmt_parcelas->close();
        $stmt_update->close();
        
        // Registra que a verificação foi realizada
        registrarVerificacao();
        
        // Se alguma parcela foi atualizada, registra no log do sistema
        if ($stats['parcelas_atualizadas'] > 0) {
            error_log("[PARCELAS] {$stats['parcelas_atualizadas']} parcelas atualizadas para status 'atrasado'");
        }
        
    } catch (Exception $e) {
        error_log("Erro ao atualizar parcelas: " . $e->getMessage());
        $stats['erros']++;
        $stats['log_erros'][] = "Erro geral: " . $e->getMessage();
    }
    
    return $stats;
}

// Se este arquivo for executado diretamente (via URL ou CLI)
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    // Força a verificação quando o arquivo é acessado diretamente
    $_GET['forcar'] = true;
    
    $resultado = verificarAtualizarParcelasVencidas();
    
    // Verifica se estamos em ambiente web ou CLI
    if (php_sapi_name() !== 'cli') {
        // Interface web
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <title>Verificação de Parcelas - Sistema de Empréstimos</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container py-5">
                <div class="card shadow-sm mx-auto" style="max-width: 600px;">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-clock-history"></i> Verificação de Parcelas</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!$resultado['verificado']): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Verificação ignorada:</strong> A última verificação foi realizada há menos de 10 minutos.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Verificação concluída com sucesso!</strong>
                            </div>
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Empréstimos verificados
                                    <span class="badge bg-primary rounded-pill"><?= $resultado['emprestimos_verificados'] ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Parcelas atualizadas
                                    <span class="badge bg-success rounded-pill"><?= $resultado['parcelas_atualizadas'] ?></span>
                                </li>
                                <?php if ($resultado['erros'] > 0): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Erros encontrados
                                    <span class="badge bg-danger rounded-pill"><?= $resultado['erros'] ?></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                            
                            <?php if (!empty($resultado['log_erros'])): ?>
                            <div class="mt-3">
                                <h5 class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Detalhes dos Erros</h5>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($resultado['log_erros'] as $erro): ?>
                                            <li class="list-group-item list-group-item-danger">
                                                <?= htmlspecialchars($erro) ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between">
                            <p class="mb-0"><small class="text-muted">Data/hora: <?= date('d/m/Y H:i:s') ?></small></p>
                            <?php 
                            $arquivo_cache = __DIR__ . '/../cache/ultima_verificacao_parcelas.txt';
                            if (file_exists($arquivo_cache)) {
                                $ultima = date('d/m/Y H:i:s', file_get_contents($arquivo_cache));
                                echo '<p class="mb-0"><small class="text-muted">Última verificação: ' . $ultima . '</small></p>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="<?= BASE_URL ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-house-fill"></i> Voltar para o Dashboard
                        </a>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="window.location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Verificar Novamente
                        </button>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    } else {
        // Interface CLI
        echo "Verificação de parcelas concluída: " . date('Y-m-d H:i:s') . "\n";
        if (!$resultado['verificado']) {
            echo "Verificação ignorada: muito recente\n";
        } else {
            echo "- Empréstimos verificados: " . $resultado['emprestimos_verificados'] . "\n";
            echo "- Parcelas atualizadas: " . $resultado['parcelas_atualizadas'] . "\n";
            echo "- Erros: " . $resultado['erros'] . "\n";
        }
    }
} 