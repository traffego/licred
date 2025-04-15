<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/autenticacao.php';
require_once __DIR__ . '/../../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$emprestimo_id = filter_input(INPUT_POST, 'emprestimo_id', FILTER_VALIDATE_INT);
$parcela_numero = filter_input(INPUT_POST, 'parcela_numero', FILTER_VALIDATE_INT);
$valor_pago = filter_input(INPUT_POST, 'valor_pago', FILTER_VALIDATE_FLOAT);
$data_pagamento = filter_input(INPUT_POST, 'data_pagamento', FILTER_SANITIZE_STRING);
$forma_pagamento = filter_input(INPUT_POST, 'forma_pagamento', FILTER_SANITIZE_STRING);
$modo_distribuicao = filter_input(INPUT_POST, 'modo_distribuicao', FILTER_SANITIZE_STRING);

if (!$emprestimo_id || !$parcela_numero || !$valor_pago || !$data_pagamento || !$forma_pagamento || !$modo_distribuicao) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

$conn->begin_transaction();

try {
    // Busca o empréstimo e o JSON de parcelas
    $stmt = $conn->prepare("SELECT json_parcelas, valor_parcela FROM emprestimos WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $emprestimo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $emprestimo = $result->fetch_assoc();

    if (!$emprestimo) {
        echo json_encode(['status' => 'error', 'message' => 'Empréstimo não encontrado.']);
        exit;
    }

    $parcelas = json_decode($emprestimo['json_parcelas'], true);
    $valor_pago_total_recebido = floatval($valor_pago);
    $valor_parcela_original = floatval($emprestimo['valor_parcela']);
    
    // Lê a ação de distribuição do POST
    $acao_diferenca_aplicada = $modo_distribuicao;

    // Encontra o índice da parcela selecionada
    $parcela_atual_index = -1;
    foreach ($parcelas as $index => $p) {
        if ($p['numero'] == $parcela_numero) {
            $parcela_atual_index = $index;
            break;
        }
    }

    if ($parcela_atual_index === -1) {
        echo json_encode(['status' => 'error', 'message' => 'Parcela não encontrada.']);
        exit;
    }

    // --- Lógica de Pagamento Principal ---
    $parcela_atual =& $parcelas[$parcela_atual_index];
    
    // Calcula quanto falta pagar na parcela atual
    $valor_ja_pago = $parcela_atual['valor_pago'] ?? 0;
    $valor_faltante_atual = $parcela_atual['valor'] - $valor_ja_pago;
    
    // Calcula quanto do valor pago vai para a parcela atual
    $valor_aplicado_parcela_atual = min($valor_pago_total_recebido, $valor_faltante_atual);
    
    // Atualiza a parcela atual
    $parcela_atual['valor_pago'] = $valor_ja_pago + $valor_aplicado_parcela_atual;
    $parcela_atual['data_pagamento'] = $data_pagamento;
    $parcela_atual['forma_pagamento'] = $forma_pagamento;
    $parcela_atual['status'] = ($valor_ja_pago + $valor_aplicado_parcela_atual >= $parcela_atual['valor']) ? 'pago' : 'parcial';

    // Calcula a diferença (valor que sobrou após pagar a parcela atual)
    $diferenca = $valor_pago_total_recebido - $valor_aplicado_parcela_atual;
    $parcela_atual['diferenca_transacao'] = $diferenca;
    $parcela_atual['acao_diferenca'] = $acao_diferenca_aplicada;

    // Se houver diferença positiva e a ação for desconto_proximas
    if ($diferenca > 0 && $acao_diferenca_aplicada === 'desconto_proximas') {
        $valor_restante = $diferenca;
        
        // Percorre as próximas parcelas
        for ($i = $parcela_atual_index + 1; $i < count($parcelas) && $valor_restante > 0; $i++) {
            $proxima_parcela =& $parcelas[$i];
            
            // Se a parcela já estiver paga, pula
            if ($proxima_parcela['status'] === 'pago') {
                continue;
            }
            
            // Calcula quanto falta pagar nesta parcela
            $valor_ja_pago_proxima = $proxima_parcela['valor_pago'] ?? 0;
            $valor_faltante = $proxima_parcela['valor'] - $valor_ja_pago_proxima;
            
            // Calcula quanto será aplicado nesta parcela
            $valor_a_aplicar = min($valor_restante, $valor_faltante);
            
            // Atualiza a próxima parcela
            $proxima_parcela['valor_pago'] = $valor_ja_pago_proxima + $valor_a_aplicar;
            $proxima_parcela['data_pagamento'] = $data_pagamento;
            $proxima_parcela['forma_pagamento'] = $forma_pagamento;
            $proxima_parcela['status'] = ($valor_ja_pago_proxima + $valor_a_aplicar >= $proxima_parcela['valor']) ? 'pago' : 'parcial';
            $proxima_parcela['diferenca_transacao'] = $valor_a_aplicar;
            $proxima_parcela['acao_diferenca'] = $acao_diferenca_aplicada;
            
            // Atualiza o valor restante
            $valor_restante -= $valor_a_aplicar;
        }
    }

    // Se houver diferença positiva e a ação for desconto_ultimas
    if ($diferenca > 0 && $acao_diferenca_aplicada === 'desconto_ultimas') {
        $valor_restante = $diferenca;
        
        // Percorre as parcelas de trás para frente
        for ($i = count($parcelas) - 1; $i > $parcela_atual_index && $valor_restante > 0; $i--) {
            $ultima_parcela =& $parcelas[$i];
            
            // Se a parcela já estiver paga, pula
            if ($ultima_parcela['status'] === 'pago') {
                continue;
            }
            
            // Calcula quanto falta pagar nesta parcela
            $valor_ja_pago_ultima = $ultima_parcela['valor_pago'] ?? 0;
            $valor_faltante = $ultima_parcela['valor'] - $valor_ja_pago_ultima;
            
            // Calcula quanto será aplicado nesta parcela
            $valor_a_aplicar = min($valor_restante, $valor_faltante);
            
            // Atualiza a última parcela
            $ultima_parcela['valor_pago'] = $valor_ja_pago_ultima + $valor_a_aplicar;
            $ultima_parcela['data_pagamento'] = $data_pagamento;
            $ultima_parcela['forma_pagamento'] = $forma_pagamento;
            $ultima_parcela['status'] = ($valor_ja_pago_ultima + $valor_a_aplicar >= $ultima_parcela['valor']) ? 'pago' : 'parcial';
            $ultima_parcela['diferenca_transacao'] = $valor_a_aplicar;
            $ultima_parcela['acao_diferenca'] = $acao_diferenca_aplicada;
            
            // Atualiza o valor restante
            $valor_restante -= $valor_a_aplicar;
        }
    }

    // Se houver diferença negativa e a ação for proxima_parcela
    if ($diferenca < 0 && $acao_diferenca_aplicada === 'proxima_parcela') {
        $valor_faltante = abs($diferenca);
        $proxima_parcela_index = $parcela_atual_index + 1;
        
        if ($proxima_parcela_index < count($parcelas)) {
            $proxima_parcela =& $parcelas[$proxima_parcela_index];
            $proxima_parcela['valor'] = $valor_parcela_original + $valor_faltante;
            $proxima_parcela['diferenca_transacao'] = -$valor_faltante;
            $proxima_parcela['acao_diferenca'] = $acao_diferenca_aplicada;
        }
    }

    // Atualiza o JSON no banco de dados
    $json_parcelas = json_encode($parcelas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("UPDATE emprestimos SET json_parcelas = ? WHERE id = ?");
    $stmt->bind_param("si", $json_parcelas, $emprestimo_id);

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pagamento registrado com sucesso!']);
    } else {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar o empréstimo: ' . $conn->error]);
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Erro na transação: ' . $e->getMessage()]);
}

$conn->close();