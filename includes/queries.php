<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/conexao.php';

// TRATAR EMPRESTIMOS

function buscarResumoEmprestimoId(mysqli $conn, int $id): array|null {
    $stmt = $conn->prepare("SELECT id, tipo, valor_emprestado FROM emprestimos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($linha = $resultado->fetch_assoc()) {
        $valor_emprestado = (float) $linha['valor_emprestado'];
        
        // Busca as parcelas na tabela parcelas em vez do JSON
        $stmt_parcelas = $conn->prepare("SELECT 
            numero, valor, status, valor_pago, data_pagamento, forma_pagamento 
            FROM parcelas WHERE emprestimo_id = ? ORDER BY numero");
        $stmt_parcelas->bind_param("i", $id);
        $stmt_parcelas->execute();
        $result_parcelas = $stmt_parcelas->get_result();
        
        $parcelas = [];
        $total_previsto = 0;
        $total_pago = 0;
        
        while ($p = $result_parcelas->fetch_assoc()) {
            $parcelas[] = $p;
            $valor = (float) $p['valor'];
            $total_previsto += $valor;
            
            if ($p['status'] === 'pago' || $p['status'] === 'parcial') {
                $total_pago += (float) ($p['valor_pago'] ?? $p['valor']);
            }
        }

        $lucro_previsto = $total_previsto - $valor_emprestado;
        $falta = $total_previsto - $total_pago;

        return [
            'id' => $linha['id'],
            'valor_emprestado' => $valor_emprestado,
            'total_previsto' => $total_previsto,
            'total_pago' => $total_pago,
            'lucro_previsto' => $lucro_previsto,
            'falta' => $falta,
            'parcelas' => $parcelas
        ];
    }

    return null;
}

function buscarTodosEmprestimosComCliente(mysqli $conn): array {
    $sql = "SELECT 
                e.id,
                e.cliente_id,
                e.tipo_de_cobranca,
                e.valor_emprestado,
                e.parcelas,
                e.valor_parcela,
                e.juros_percentual,
                e.data_inicio,
                e.configuracao,
                e.data_criacao,
                e.data_atualizacao,
                e.status,
                c.nome AS cliente_nome 
            FROM emprestimos e 
            JOIN clientes c ON e.cliente_id = c.id
            WHERE e.status != 'inativo' OR e.status IS NULL
            ORDER BY e.id DESC";
            
    $stmt = $conn->query($sql);
    $lista = [];

    while ($e = $stmt->fetch_assoc()) {
        // Adiciona os dados básicos
        $emprestimo = $e;
        
        // Busca as parcelas na tabela parcelas
        $stmt_parcelas = $conn->prepare("SELECT 
            status, valor, valor_pago
            FROM parcelas 
            WHERE emprestimo_id = ?");
        $stmt_parcelas->bind_param("i", $e['id']);
        $stmt_parcelas->execute();
        $result_parcelas = $stmt_parcelas->get_result();
        
        $total_previsto = 0;
        $total_pago = 0;
        $parcelas_pagas = 0;
        
        while ($p = $result_parcelas->fetch_assoc()) {
            $valor = (float) $p['valor'];
            $total_previsto += $valor;
            
            if ($p['status'] === 'pago') {
                $total_pago += $valor;
                $parcelas_pagas++;
            } elseif ($p['status'] === 'parcial' && isset($p['valor_pago'])) {
                $total_pago += (float) $p['valor_pago'];
            }
        }
        
        // Adiciona os totais calculados
        $emprestimo['total_previsto'] = $total_previsto;
        $emprestimo['total_pago'] = $total_pago;
        $emprestimo['parcelas_pagas'] = $parcelas_pagas;

        $lista[] = $emprestimo;
    }

    return $lista;
}

function calcularTotalParcelasAtrasadas(mysqli $conn) {
    $total_atrasado = 0;
    
    // Usar a data de ontem para considerar atrasadas apenas as parcelas que venceram há pelo menos 1 dia
    $ontem = date('Y-m-d', strtotime('-1 day'));
    
    $sql = "SELECT 
                SUM(valor) as total 
            FROM 
                parcelas 
            WHERE 
                status = 'pendente' 
                AND vencimento < ?";
                
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ontem);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($linha = $resultado->fetch_assoc()) {
        $total_atrasado = (float) $linha['total'];
    }

    return $total_atrasado;
}

function contarEmprestimosAtivos(mysqli $conn) {
    $sql = "SELECT 
                COUNT(DISTINCT emprestimo_id) as total
            FROM 
                parcelas
            WHERE 
                status IN ('pendente', 'parcial', 'atrasado')";
                
    $resultado = $conn->query($sql);
    $linha = $resultado->fetch_assoc();
    
    return (int) $linha['total'];
}

function buscarTodosClientes(mysqli $conn): array {
    $sql = "SELECT c.id, c.nome, c.indicacao, u.nome as investidor_nome 
           FROM clientes c 
           LEFT JOIN usuarios u ON c.indicacao = u.id 
           ORDER BY c.nome ASC";
    
    $stmt = $conn->query($sql);
    return $stmt->fetch_all(MYSQLI_ASSOC);
}

function buscarEmprestimoPorId(mysqli $conn, int $id) {
    $sql = "SELECT 
                e.*,
                c.nome as cliente_nome 
            FROM emprestimos e 
            INNER JOIN clientes c ON e.cliente_id = c.id 
            WHERE e.id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        return null;
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return null;
    }
    
    $emprestimo = $result->fetch_assoc();
    
    // Adiciona informação se é inativo
    if ($emprestimo['status'] === 'inativo') {
        $emprestimo['esta_inativo'] = true;
    } else {
        $emprestimo['esta_inativo'] = false;
    }
    
    return $emprestimo;
}