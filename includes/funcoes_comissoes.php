<?php
/**
 * Funções para cálculo e processamento de comissões
 */

/**
 * Calcula a previsão de comissões para um empréstimo
 * Retorna informações sobre valores previstos e realizados
 * 
 * @param mysqli $conn Conexão com o banco de dados
 * @param int $emprestimo_id ID do empréstimo
 * @return array|null Array com informações ou null se empréstimo não encontrado
 */
function calcularPrevisaoComissoes($conn, $emprestimo_id) {
    // Busca informações do empréstimo e investidor
    $sql = "
        SELECT 
            e.id,
            e.valor_emprestado,
            e.investidor_id,
            c.id as conta_id,
            c.comissao as percentual_comissao,
            (SELECT SUM(valor) FROM parcelas WHERE emprestimo_id = e.id) as valor_total_parcelas,
            (SELECT SUM(valor_pago) FROM parcelas WHERE emprestimo_id = e.id AND status = 'pago') as valor_ja_pago,
            (SELECT COUNT(*) FROM parcelas WHERE emprestimo_id = e.id AND status = 'pago') as parcelas_pagas,
            (SELECT COUNT(*) FROM parcelas WHERE emprestimo_id = e.id) as total_parcelas,
            COALESCE(
                (SELECT SUM(valor_comissao) 
                 FROM controle_comissoes 
                 WHERE emprestimo_id = e.id AND usuario_id = e.investidor_id AND processado = 1
                ), 0
            ) as comissao_ja_processada
        FROM 
            emprestimos e
        JOIN 
            usuarios u ON e.investidor_id = u.id
        JOIN 
            contas c ON u.id = c.usuario_id
        WHERE 
            e.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $emprestimo_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        return null;
    }

    // Calcula valores
    $valor_emprestado = floatval($result['valor_emprestado']);
    $valor_total_parcelas = floatval($result['valor_total_parcelas']);
    $valor_ja_pago = floatval($result['valor_ja_pago'] ?? 0);
    $percentual_comissao = floatval($result['percentual_comissao']);
    
    // Calcula lucros
    $lucro_total = $valor_total_parcelas - $valor_emprestado;
    $lucro_ja_realizado = max(0, $valor_ja_pago - $valor_emprestado);

    // Calcula comissões
    $comissao_total_prevista = $lucro_total * ($percentual_comissao / 100);
    $comissao_ja_realizada = $lucro_ja_realizado * ($percentual_comissao / 100);
    $comissao_ja_processada = floatval($result['comissao_ja_processada']);

    // Calcula parte do administrador
    $comissao_admin_prevista = $lucro_total - $comissao_total_prevista;
    $comissao_admin_realizada = $lucro_ja_realizado - $comissao_ja_realizada;

    return [
        'emprestimo' => [
            'id' => $result['id'],
            'valor_emprestado' => $valor_emprestado,
            'valor_total_parcelas' => $valor_total_parcelas,
            'valor_ja_pago' => $valor_ja_pago,
            'parcelas_pagas' => $result['parcelas_pagas'],
            'total_parcelas' => $result['total_parcelas']
        ],
        'investidor' => [
            'id' => $result['investidor_id'],
            'conta_id' => $result['conta_id'],
            'percentual_comissao' => $percentual_comissao,
            'comissao_prevista' => $comissao_total_prevista,
            'comissao_realizada' => $comissao_ja_realizada,
            'comissao_processada' => $comissao_ja_processada,
            'comissao_a_processar' => $comissao_ja_realizada - $comissao_ja_processada
        ],
        'administrador' => [
            'comissao_prevista' => $comissao_admin_prevista,
            'comissao_realizada' => $comissao_admin_realizada
        ],
        'lucro' => [
            'total_previsto' => $lucro_total,
            'ja_realizado' => $lucro_ja_realizado
        ],
        'status' => [
            'todas_parcelas_pagas' => ($result['parcelas_pagas'] == $result['total_parcelas']),
            'tem_comissao_pendente' => ($comissao_ja_realizada > $comissao_ja_processada)
        ]
    ];
}

/**
 * Verifica se já existe comissão processada para um empréstimo
 * 
 * @param mysqli $conn Conexão com o banco de dados
 * @param int $emprestimo_id ID do empréstimo
 * @param int|null $parcela_id ID da parcela (opcional)
 * @return bool|array false se não existir, ou array com dados do processamento se existir
 */
function verificarComissaoProcessada($conn, $emprestimo_id, $parcela_id = null) {
    $sql = "SELECT 
            cc.*,
            u.nome as usuario_nome,
            e.valor_emprestado,
            cl.nome as cliente_nome
        FROM 
            controle_comissoes cc
        JOIN 
            usuarios u ON cc.usuario_id = u.id
        JOIN 
            emprestimos e ON cc.emprestimo_id = e.id
        JOIN 
            clientes cl ON e.cliente_id = cl.id
        WHERE 
            cc.emprestimo_id = ?";
    
    // Se uma parcela específica foi informada, verifica apenas ela
    $params = array($emprestimo_id);
    $types = "i";
    
    if ($parcela_id !== null) {
        $sql .= " AND cc.parcela_id = ?";
        $params[] = $parcela_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY cc.data_processamento DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $processamentos = [];
    while ($row = $result->fetch_assoc()) {
        $processamentos[] = $row;
    }
    
    return $processamentos;
}

/**
 * Registra o processamento de uma comissão
 * 
 * @param mysqli $conn Conexão com o banco de dados
 * @param array $dados Dados do processamento
 * @return bool|string true se sucesso, string com erro se falhar
 */
function registrarProcessamentoComissao($conn, $dados) {
    try {
        // Verifica se já existe processamento para esta parcela específica
        $processamentos = verificarComissaoProcessada($conn, $dados['emprestimo_id'], $dados['parcela_id']);
        if ($processamentos) {
            return "Comissão já processada para esta parcela em " . $processamentos[0]['data_processamento'];
        }

        // Registra o processamento
        $stmt = $conn->prepare("
            INSERT INTO controle_comissoes (
                parcela_id,
                usuario_id,
                conta_id,
                emprestimo_id,
                valor_comissao,
                processado
            ) VALUES (?, ?, ?, ?, ?, 1)
        ");

        $stmt->bind_param(
            "iiidd",
            $dados['parcela_id'],
            $dados['usuario_id'],
            $dados['conta_id'],
            $dados['emprestimo_id'],
            $dados['valor_comissao']
        );

        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar processamento: " . $stmt->error);
        }

        return true;

    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Verifica se há uma transação ativa na conexão
 * @param mysqli $conn Conexão com o banco de dados
 * @return bool True se houver uma transação ativa, false caso contrário
 */
function hasActiveTransaction($conn) {
    try {
        // Tenta iniciar uma transação. Se já houver uma, retornará false
        $result = $conn->begin_transaction();
        if ($result) {
            // Se conseguiu iniciar, faz rollback e retorna false (não havia transação)
            $conn->rollback();
            return false;
        }
        // Se não conseguiu iniciar, é porque já existe uma transação
        return true;
    } catch (Exception $e) {
        // Se der erro, assume que não há transação
        return false;
    }
}

/**
 * Processa o retorno do capital e as comissões quando todas as parcelas estão pagas
 * 
 * @param mysqli $conn Conexão com o banco de dados
 * @param int $emprestimo_id ID do empréstimo
 * @return array Array com status do processamento
 */
function processarComissoesERetornos($conn, $emprestimo_id) {
    try {
        $start_transaction = !hasActiveTransaction($conn);
        
        if ($start_transaction) {
            $conn->begin_transaction();
        }

        // Buscar informações do empréstimo e do administrador
        $sql = "SELECT 
                e.*,
                c.nome as cliente_nome,
                u.id as investidor_id,
                ct.id as conta_id,
                ct.comissao as percentual_comissao,
                (SELECT SUM(valor_pago) FROM parcelas WHERE emprestimo_id = e.id AND status = 'pago') as total_pago,
                (SELECT COUNT(*) FROM parcelas WHERE emprestimo_id = e.id AND status = 'pago') as parcelas_pagas,
                (SELECT COUNT(*) FROM parcelas WHERE emprestimo_id = e.id) as total_parcelas,
                (SELECT ct_admin.id 
                 FROM usuarios u_admin 
                 JOIN contas ct_admin ON u_admin.id = ct_admin.usuario_id 
                 WHERE u_admin.nivel_autoridade IN ('administrador', 'superadmin') 
                 AND ct_admin.status = 'ativo' 
                 LIMIT 1) as conta_admin_id,
                (SELECT COUNT(*) FROM movimentacoes_contas 
                 WHERE conta_id = ct.id 
                 AND tipo = 'entrada'
                 AND descricao LIKE CONCAT('Retorno de capital - Empréstimo #', e.id, '%')) as retorno_capital_processado
            FROM 
                emprestimos e
            JOIN 
                clientes c ON e.cliente_id = c.id
            JOIN 
                usuarios u ON e.investidor_id = u.id
            JOIN 
                contas ct ON u.id = ct.usuario_id
            WHERE 
                e.id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $emprestimo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $emprestimo = $result->fetch_assoc();

        if (!$emprestimo) {
            throw new Exception("Empréstimo não encontrado");
        }

        if (!$emprestimo['conta_admin_id']) {
            throw new Exception("Conta do administrador não encontrada");
        }

        // Verificar se todas as parcelas estão pagas
        if ($emprestimo['parcelas_pagas'] < $emprestimo['total_parcelas']) {
            return array('success' => false, 'message' => 'Nem todas as parcelas estão pagas');
        }

        // Processar retorno do capital se ainda não foi feito
        if ($emprestimo['retorno_capital_processado'] == 0) {
            $valor_capital = floatval($emprestimo['valor_emprestado']);
            $descricao_capital = sprintf(
                "Retorno de capital - Empréstimo #%d para %s - Valor: R$ %s",
                $emprestimo_id,
                $emprestimo['cliente_nome'],
                number_format($valor_capital, 2, ',', '.')
            );

            $stmt = $conn->prepare("
                INSERT INTO movimentacoes_contas (conta_id, tipo, valor, descricao, data_movimentacao) 
                VALUES (?, 'entrada', ?, ?, NOW())
            ");
            $stmt->bind_param("ids", 
                $emprestimo['conta_id'],
                $valor_capital,
                $descricao_capital
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao registrar retorno de capital: " . $stmt->error);
            }
        }

        // Calcular valores
        $valor_emprestado = floatval($emprestimo['valor_emprestado']);
        $total_pago = floatval($emprestimo['total_pago']);
        $lucro_total = $total_pago - $valor_emprestado;
        $percentual_comissao = floatval($emprestimo['percentual_comissao']);
        $valor_comissao_investidor = $lucro_total * ($percentual_comissao / 100);
        $valor_comissao_admin = $lucro_total - $valor_comissao_investidor;

        // Buscar todas as parcelas pagas que ainda não têm comissão processada
        $sql_parcelas = "
            SELECT p.* 
            FROM parcelas p
            LEFT JOIN controle_comissoes cc ON p.id = cc.parcela_id
            WHERE p.emprestimo_id = ? 
            AND p.status = 'pago' 
            AND cc.id IS NULL
            ORDER BY p.numero";
            
        $stmt = $conn->prepare($sql_parcelas);
        $stmt->bind_param("i", $emprestimo_id);
        $stmt->execute();
        $result_parcelas = $stmt->get_result();

        $parcelas_processadas = 0;
        
        // Registrar comissão para cada parcela
        while ($parcela = $result_parcelas->fetch_assoc()) {
            // Calcular valor principal e lucro da parcela
            $valor_principal_parcela = $valor_emprestado / $emprestimo['total_parcelas'];
            $valor_pago = floatval($parcela['valor_pago']);
            $lucro_parcela = max(0, $valor_pago - $valor_principal_parcela);
            
            // Calcular comissão do investidor para esta parcela
            $valor_comissao_parcela_investidor = $lucro_parcela * ($percentual_comissao / 100);
            
            // Calcular comissão do admin para esta parcela
            $valor_comissao_parcela_admin = $lucro_parcela - $valor_comissao_parcela_investidor;

            // Registrar comissão do investidor
            $dados_comissao = array(
                'parcela_id' => $parcela['id'],
                'usuario_id' => $emprestimo['investidor_id'],
                'conta_id' => $emprestimo['conta_id'],
                'emprestimo_id' => $emprestimo_id,
                'valor_comissao' => $valor_comissao_parcela_investidor
            );

            $resultado = registrarProcessamentoComissao($conn, $dados_comissao);
            if ($resultado !== true) {
                // Se a comissão já foi processada, apenas continua
                if (strpos($resultado, 'Comissão já processada') !== false) {
                    continue;
                }
                throw new Exception($resultado);
            }

            // Registrar a movimentação do investidor
            $descricao_comissao = sprintf(
                "Comissão (%.1f%%) - Parcela #%d do empréstimo #%d para %s",
                $percentual_comissao,
                $parcela['numero'],
                $emprestimo_id,
                $emprestimo['cliente_nome']
            );

            $stmt = $conn->prepare("
                INSERT INTO movimentacoes_contas (conta_id, tipo, valor, descricao, data_movimentacao) 
                VALUES (?, 'entrada', ?, ?, NOW())
            ");
            $stmt->bind_param("ids", 
                $emprestimo['conta_id'], 
                $valor_comissao_parcela_investidor, 
                $descricao_comissao
            );
            $stmt->execute();

            // Registrar comissão do administrador
            if ($valor_comissao_parcela_admin > 0) {
                $dados_comissao_admin = array(
                    'parcela_id' => $parcela['id'],
                    'usuario_id' => $emprestimo['investidor_id'], // mantemos o mesmo investidor para referência
                    'conta_id' => $emprestimo['conta_admin_id'],
                    'emprestimo_id' => $emprestimo_id,
                    'valor_comissao' => $valor_comissao_parcela_admin
                );

                $resultado = registrarProcessamentoComissao($conn, $dados_comissao_admin);
                if ($resultado !== true && strpos($resultado, 'Comissão já processada') === false) {
                    throw new Exception($resultado);
                }

                // Registrar a movimentação do admin
                $descricao_comissao_admin = sprintf(
                    "Comissão administrativa (%.1f%%) - Parcela #%d do empréstimo #%d para %s",
                    (100 - $percentual_comissao),
                    $parcela['numero'],
                    $emprestimo_id,
                    $emprestimo['cliente_nome']
                );

                $stmt = $conn->prepare("
                    INSERT INTO movimentacoes_contas (conta_id, tipo, valor, descricao, data_movimentacao) 
                    VALUES (?, 'entrada', ?, ?, NOW())
                ");
                $stmt->bind_param("ids", 
                    $emprestimo['conta_admin_id'], 
                    $valor_comissao_parcela_admin, 
                    $descricao_comissao_admin
                );
                $stmt->execute();
            }
            
            $parcelas_processadas++;
        }

        if ($start_transaction) {
            $conn->commit();
        }
        
        if ($parcelas_processadas === 0 && $emprestimo['retorno_capital_processado'] > 0) {
            return array(
                'success' => true,
                'message' => 'Todas as comissões e retorno de capital já foram processados anteriormente'
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Comissões e retorno de capital processados com sucesso'
        );
        
    } catch (Exception $e) {
        if ($start_transaction) {
            $conn->rollback();
        }
        return array(
            'success' => false,
            'message' => 'Erro ao processar comissões: ' . $e->getMessage()
        );
    }
}

/**
 * Busca o status de todas as comissões de um investidor
 * Retorna informações sobre comissões previstas, realizadas e processadas
 * 
 * @param mysqli $conn Conexão com o banco de dados
 * @param int $usuario_id ID do usuário/investidor
 * @return array Array com status de todas as comissões
 */
function buscarStatusComissoes($conn, $usuario_id) {
    // Busca todos os empréstimos ativos do investidor
    $sql = "
        SELECT 
            e.id as emprestimo_id,
            e.valor_emprestado,
            e.cliente_id,
            cl.nome as cliente_nome,
            c.comissao as percentual_comissao,
            c.id as conta_id,
            (SELECT SUM(valor) FROM parcelas WHERE emprestimo_id = e.id) as valor_total_parcelas,
            (SELECT SUM(valor_pago) FROM parcelas WHERE emprestimo_id = e.id AND status = 'pago') as valor_ja_pago,
            (SELECT COUNT(*) FROM parcelas WHERE emprestimo_id = e.id AND status = 'pago') as parcelas_pagas,
            (SELECT COUNT(*) FROM parcelas WHERE emprestimo_id = e.id) as total_parcelas,
            COALESCE(
                (SELECT SUM(valor_comissao) 
                 FROM controle_comissoes 
                 WHERE emprestimo_id = e.id AND usuario_id = e.investidor_id AND processado = 1
                ), 0
            ) as comissao_ja_processada
        FROM 
            emprestimos e
        JOIN 
            clientes cl ON e.cliente_id = cl.id
        JOIN 
            usuarios u ON e.investidor_id = u.id
        JOIN 
            contas c ON u.id = c.usuario_id
        WHERE 
            e.investidor_id = ? 
            AND e.status = 'ativo'
        ORDER BY 
            e.data_inicio DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $resumo = [
        'total_emprestimos' => 0,
        'total_valor_emprestado' => 0,
        'total_comissao_prevista' => 0,
        'total_comissao_realizada' => 0,
        'total_comissao_processada' => 0,
        'total_comissao_pendente' => 0
    ];

    $emprestimos = [];
    while ($row = $result->fetch_assoc()) {
        // Calcula valores para este empréstimo
        $valor_emprestado = floatval($row['valor_emprestado']);
        $valor_total = floatval($row['valor_total_parcelas']);
        $valor_pago = floatval($row['valor_ja_pago'] ?? 0);
        $percentual = floatval($row['percentual_comissao']);
        
        // Calcula lucros
        $lucro_total = $valor_total - $valor_emprestado;
        $lucro_realizado = max(0, $valor_pago - $valor_emprestado);
        
        // Calcula comissões
        $comissao_prevista = $lucro_total * ($percentual / 100);
        $comissao_realizada = $lucro_realizado * ($percentual / 100);
        $comissao_processada = floatval($row['comissao_ja_processada']);
        $comissao_pendente = max(0, $comissao_realizada - $comissao_processada);

        // Atualiza totais
        $resumo['total_emprestimos']++;
        $resumo['total_valor_emprestado'] += $valor_emprestado;
        $resumo['total_comissao_prevista'] += $comissao_prevista;
        $resumo['total_comissao_realizada'] += $comissao_realizada;
        $resumo['total_comissao_processada'] += $comissao_processada;
        $resumo['total_comissao_pendente'] += $comissao_pendente;

        // Adiciona informações deste empréstimo
        $emprestimos[] = [
            'emprestimo' => [
                'id' => $row['emprestimo_id'],
                'cliente_nome' => $row['cliente_nome'],
                'valor_emprestado' => $valor_emprestado,
                'valor_total' => $valor_total,
                'valor_pago' => $valor_pago,
                'parcelas_pagas' => $row['parcelas_pagas'],
                'total_parcelas' => $row['total_parcelas']
            ],
            'comissoes' => [
                'percentual' => $percentual,
                'prevista' => $comissao_prevista,
                'realizada' => $comissao_realizada,
                'processada' => $comissao_processada,
                'pendente' => $comissao_pendente
            ],
            'lucro' => [
                'total_previsto' => $lucro_total,
                'realizado' => $lucro_realizado
            ],
            'status' => [
                'progresso' => ($row['parcelas_pagas'] * 100) / $row['total_parcelas'],
                'finalizado' => ($row['parcelas_pagas'] == $row['total_parcelas'])
            ]
        ];
    }

    return [
        'resumo' => $resumo,
        'emprestimos' => $emprestimos
    ];
} 