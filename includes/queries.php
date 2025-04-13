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
    $stmt = $conn->query("SELECT e.*, c.nome AS cliente_nome FROM emprestimos e JOIN clientes c ON e.cliente_id = c.id ORDER BY e.id DESC");
    $lista = [];

    while ($e = $stmt->fetch_assoc()) {
        $valor_emprestado = (float) $e['valor_emprestado'];
        $json = $e['json_parcelas'];
        $parcelas = json_decode($json, true);

        $total_previsto = 0;
        $total_pago = 0;
        $total_parcelas = 0;
        $parcelas_pagas = 0;

        if (is_array($parcelas)) {
            foreach ($parcelas as $p) {
                $valor = (float) str_replace(',', '.', $p['valor']);
                $total_previsto += $valor;
                $total_parcelas++;

                if (!empty($p['paga']) && !empty($p['valor_pago'])) {
                    $total_pago += (float) str_replace(',', '.', $p['valor_pago']);
                    $parcelas_pagas++;
                }
            }
        }

        $status = 'ativo';
        if ($parcelas_pagas === $total_parcelas && $total_parcelas > 0) {
            $status = 'quitado';
        } elseif (is_array($parcelas)) {
            foreach ($parcelas as $p) {
                if (
                    empty($p['paga']) &&
                    !empty($p['data']) &&
                    DateTime::createFromFormat('d/m/Y', $p['data']) < new DateTime()
                ) {
                    $status = 'atrasado';
                    break;
                }
            }
        }

        $e['total_previsto'] = $total_previsto;
        $e['total_pago'] = $total_pago;
        $e['lucro_previsto'] = $total_previsto - $valor_emprestado;
        $e['lucro_real'] = $total_pago - $valor_emprestado;
        $e['total_parcelas'] = $total_parcelas;
        $e['parcelas_pagas'] = $parcelas_pagas;
        $e['status'] = $status;

        $lista[] = $e;
    }

    return $lista;
}

function calcularTotalParcelasAtrasadas($conn) {
    $stmt = $conn->query("SELECT json_parcelas FROM emprestimos WHERE status != 'quitado'");
    $total_atrasado = 0;

    while ($row = $stmt->fetch_assoc()) {
        $parcelas = json_decode($row['json_parcelas'], true);
        
        if (is_array($parcelas)) {
            foreach ($parcelas as $p) {
                // Verifica se a parcela está atrasada (não paga e data vencida)
                if (empty($p['paga']) && !empty($p['data'])) {
                    $data_vencimento = DateTime::createFromFormat('d/m/Y', $p['data']);
                    if ($data_vencimento < new DateTime()) {
                        $valor = (float) str_replace(',', '.', $p['valor']);
                        $total_atrasado += $valor;
                    }
                }
            }
        }
    }

    return $total_atrasado;
}

function contarEmprestimosAtivos($conn) {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM emprestimos WHERE status != 'quitado'");
    $resultado = $stmt->fetch_assoc();
    return $resultado['total'] ?? 0;
}

function buscarTodosClientes(mysqli $conn): array {
    $stmt = $conn->query("SELECT id, nome FROM clientes WHERE status = 'Ativo' ORDER BY nome ASC");
    return $stmt->fetch_all(MYSQLI_ASSOC);
}