<?php
// Desativa a exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/autenticacao.php';
require_once __DIR__ . '/../../includes/conexao.php';

// Função para retornar resposta JSON e encerrar
function jsonResponse($status, $message, $httpCode = 200) {
    ob_clean();
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Método não permitido', 405);
}

$emprestimo_id = filter_input(INPUT_POST, 'emprestimo_id', FILTER_VALIDATE_INT);
$valor_quitacao = filter_input(INPUT_POST, 'valor_quitacao', FILTER_VALIDATE_FLOAT);
$data_quitacao = filter_input(INPUT_POST, 'data_quitacao', FILTER_SANITIZE_STRING);
$forma_pagamento = filter_input(INPUT_POST, 'forma_pagamento', FILTER_SANITIZE_STRING);

// Validação
$erros = [];
if (!$emprestimo_id) $erros[] = 'ID do empréstimo inválido';
if (!$valor_quitacao || $valor_quitacao <= 0) $erros[] = 'Valor de quitação inválido';
if (!$data_quitacao) $erros[] = 'Data de quitação inválida';
if (!$forma_pagamento) $erros[] = 'Forma de pagamento inválida';

if (!empty($erros)) {
    jsonResponse('error', implode(', ', $erros), 400);
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
        jsonResponse('error', 'Empréstimo não encontrado.', 404);
    }

    $parcelas = json_decode($emprestimo['json_parcelas'], true);
    if ($parcelas === null) {
        jsonResponse('error', 'Erro ao decodificar as parcelas do empréstimo.', 500);
    }

    // Calcula o total já pago
    $total_pago = 0;
    foreach ($parcelas as $p) {
        if ($p['status'] === 'pago') {
            $total_pago += floatval($p['valor']);
        } elseif ($p['status'] === 'parcial') {
            $total_pago += floatval($p['valor_pago'] ?? 0);
        }
    }

    // Atualiza todas as parcelas pendentes ou parciais
    foreach ($parcelas as &$parcela) {
        if ($parcela['status'] !== 'pago') {
            $parcela['status'] = 'pago';
            $parcela['valor_pago'] = $parcela['valor'];
            $parcela['data_pagamento'] = $data_quitacao;
            $parcela['forma_pagamento'] = $forma_pagamento;
            $parcela['observacao'] = 'Quitação do empréstimo';
        }
    }
    unset($parcela);

    // Atualiza o JSON no banco de dados
    $json_parcelas = json_encode($parcelas, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("UPDATE emprestimos SET json_parcelas = ? WHERE id = ?");
    $stmt->bind_param("si", $json_parcelas, $emprestimo_id);
    
    if ($stmt->execute()) {
        $conn->commit();
        $conn->close();
        jsonResponse('success', 'Empréstimo quitado com sucesso!');
    } else {
        $conn->rollback();
        $conn->close();
        jsonResponse('error', 'Erro ao atualizar o banco de dados');
    }
} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    jsonResponse('error', 'Erro ao processar a quitação: ' . $e->getMessage());
} 