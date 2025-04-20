<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/conexao.php';

// TRATAR EMPRESTIMOS

function buscarResumoEmprestimoId(mysqli $conn, int $id): array|null {
    $stmt = $conn->prepare("SELECT id, tipo, valor_emprestado, json_parcelas FROM emprestimos WHERE id = ? AND json_parcelas IS NOT NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($linha = $resultado->fetch_assoc()) {
        $valor_emprestado = (float) $linha['valor_emprestado'];
        $json = $linha['json_parcelas'];
        $parcelas = json_decode($json, true);

        $total_previsto = 0;
        $total_pago = 0;

        if (is_array($parcelas)) {
            foreach ($parcelas as $p) {
                $valor = (float) str_replace(',', '.', $p['valor']);
                $total_previsto += $valor;

                if (!empty($p['paga']) && !empty($p['valor_pago'])) {
                    $total_pago += (float) str_replace(',', '.', $p['valor_pago']);
                }
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
                e.json_parcelas,
                e.configuracao,
                e.data_criacao,
                e.data_atualizacao,
                c.nome AS cliente_nome 
            FROM emprestimos e 
            JOIN clientes c ON e.cliente_id = c.id 
            ORDER BY e.id DESC";
            
    $stmt = $conn->query($sql);
    $lista = [];

    while ($e = $stmt->fetch_assoc()) {
        // Adiciona os dados básicos
        $emprestimo = $e;
        
        // Processa as parcelas se existirem
        if (!empty($e['json_parcelas'])) {
            $parcelas = json_decode($e['json_parcelas'], true);
            $total_previsto = 0;
            $total_pago = 0;
            $parcelas_pagas = 0;
            
            if (is_array($parcelas)) {
                foreach ($parcelas as $p) {
                    $valor = (float) str_replace(',', '.', $p['valor']);
                    $total_previsto += $valor;

                    if (!empty($p['paga']) && !empty($p['valor_pago'])) {
                        $total_pago += (float) str_replace(',', '.', $p['valor_pago']);
                        $parcelas_pagas++;
                    }
                }
            }
            
            // Adiciona os totais calculados
            $emprestimo['total_previsto'] = $total_previsto;
            $emprestimo['total_pago'] = $total_pago;
            $emprestimo['parcelas_pagas'] = $parcelas_pagas;
        }

        $lista[] = $emprestimo;
    }

    return $lista;
}

function calcularTotalParcelasAtrasadas(mysqli $conn) {
    $total_atrasado = 0;
    $sql = "SELECT json_parcelas FROM emprestimos";
    $stmt = $conn->query($sql);

    while ($row = $stmt->fetch_assoc()) {
        if (!empty($row['json_parcelas'])) {
            $parcelas = json_decode($row['json_parcelas'], true);
            
            if (is_array($parcelas)) {
                foreach ($parcelas as $p) {
                    // Verifica se a parcela está atrasada (não paga e data vencida)
                    if (empty($p['paga']) && !empty($p['data'])) {
                        $data_vencimento = DateTime::createFromFormat('d/m/Y', $p['data']);
                        if ($data_vencimento && $data_vencimento < new DateTime()) {
                            $valor = (float) str_replace(',', '.', $p['valor']);
                            $total_atrasado += $valor;
                        }
                    }
                }
            }
        }
    }

    return $total_atrasado;
}

function contarEmprestimosAtivos(mysqli $conn) {
    $total = 0;
    $sql = "SELECT json_parcelas FROM emprestimos";
    $stmt = $conn->query($sql);

    while ($row = $stmt->fetch_assoc()) {
        if (!empty($row['json_parcelas'])) {
            $parcelas = json_decode($row['json_parcelas'], true);
            $todas_pagas = true;
            
            if (is_array($parcelas)) {
                foreach ($parcelas as $p) {
                    if (empty($p['paga'])) {
                        $todas_pagas = false;
                        break;
                    }
                }
            }
            
            if (!$todas_pagas) {
                $total++;
            }
        }
    }

    return $total;
}

function buscarTodosClientes(mysqli $conn): array {
    $stmt = $conn->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
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
    
    return $result->fetch_assoc();
}