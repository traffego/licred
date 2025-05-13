<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/autenticacao.php';

/**
 * Script para processar as comissões dos investidores com base nas parcelas pagas
 * Este script pode ser executado manualmente pelo administrador ou agendado via cron
 */

// Registra log com data e hora de execução
$log_file = __DIR__ . '/../logs/comissoes.log';
$hora_execucao = date('Y-m-d H:i:s');
file_put_contents($log_file, "[{$hora_execucao}] Iniciando processamento de comissões\n", FILE_APPEND);

// Função para registrar log
function registrarLog($mensagem) {
    global $log_file;
    $hora = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$hora}] {$mensagem}\n", FILE_APPEND);
}

// Verifica se a tabela de controle de comissões existe
$table_exists = $conn->query("SHOW TABLES LIKE 'controle_comissoes'");
if ($table_exists->num_rows == 0) {
    // Criar a tabela se não existir
    $sql_create_table = "CREATE TABLE IF NOT EXISTS controle_comissoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parcela_id INT NOT NULL,
        usuario_id INT NOT NULL,
        conta_id INT NOT NULL,
        emprestimo_id INT NOT NULL,
        valor_parcela DECIMAL(10,2) NOT NULL,
        percentual_comissao DECIMAL(5,2) NOT NULL,
        valor_comissao DECIMAL(10,2) NOT NULL,
        data_processamento DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('processado', 'erro') DEFAULT 'processado',
        observacao TEXT NULL,
        UNIQUE KEY (parcela_id),
        FOREIGN KEY (parcela_id) REFERENCES parcelas(id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        FOREIGN KEY (conta_id) REFERENCES contas(id),
        FOREIGN KEY (emprestimo_id) REFERENCES emprestimos(id)
    )";
    
    if (!$conn->query($sql_create_table)) {
        registrarLog("ERRO: Não foi possível criar a tabela controle_comissoes: " . $conn->error);
        die("Erro ao criar tabela de comissões");
    }
    
    registrarLog("Tabela controle_comissoes criada com sucesso");
}

// Inicia a transação
$conn->begin_transaction();

try {
    // Buscar parcelas pagas que ainda não foram processadas para comissão
    $sql = "SELECT 
                p.id as parcela_id,
                p.emprestimo_id,
                p.numero,
                p.valor,
                p.valor_pago,
                p.data_pagamento,
                e.investidor_id,
                e.valor_parcela as valor_parcela_padrao,
                u.nome as investidor_nome,
                c.id as conta_id,
                c.comissao as percentual_comissao
            FROM 
                parcelas p
            INNER JOIN 
                emprestimos e ON p.emprestimo_id = e.id
            INNER JOIN 
                usuarios u ON e.investidor_id = u.id
            INNER JOIN 
                contas c ON u.id = c.usuario_id
            LEFT JOIN 
                controle_comissoes cc ON p.id = cc.parcela_id
            WHERE 
                p.status = 'pago' 
                AND cc.id IS NULL
                AND c.status = 'ativo'
                AND c.comissao > 0
            ORDER BY 
                p.data_pagamento";
                
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Erro ao consultar parcelas: " . $conn->error);
    }
    
    $total_processado = 0;
    $total_comissoes = 0;
    
    while ($parcela = $result->fetch_assoc()) {
        // Calcula o valor da comissão
        $percentual_comissao = floatval($parcela['percentual_comissao']);
        $valor_parcela = floatval($parcela['valor_pago']);
        $valor_comissao = $valor_parcela * ($percentual_comissao / 100);
        
        // Registra a comissão na tabela de controle
        $stmt = $conn->prepare("INSERT INTO controle_comissoes 
                                (parcela_id, usuario_id, conta_id, emprestimo_id, valor_parcela, 
                                 percentual_comissao, valor_comissao) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
                                
        $stmt->bind_param("iiidddd", 
                          $parcela['parcela_id'], 
                          $parcela['investidor_id'],
                          $parcela['conta_id'],
                          $parcela['emprestimo_id'],
                          $valor_parcela,
                          $percentual_comissao,
                          $valor_comissao);
                          
        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar comissão: " . $stmt->error);
        }
        
        // Adiciona o valor da comissão na conta do investidor
        $descricao = "Comissão - Parcela #{$parcela['numero']} do empréstimo #{$parcela['emprestimo_id']} ({$percentual_comissao}%)";
        
        $stmt = $conn->prepare("INSERT INTO movimentacoes_contas 
                                (conta_id, tipo, valor, descricao, data_movimentacao) 
                                VALUES (?, 'entrada', ?, ?, NOW())");
                                
        $stmt->bind_param("ids", 
                          $parcela['conta_id'],
                          $valor_comissao,
                          $descricao);
                          
        if (!$stmt->execute()) {
            throw new Exception("Erro ao adicionar comissão na conta: " . $stmt->error);
        }
        
        $total_processado++;
        $total_comissoes += $valor_comissao;
        
        registrarLog("Processada comissão da parcela #{$parcela['numero']} do empréstimo #{$parcela['emprestimo_id']} para {$parcela['investidor_nome']} - Valor: R$ " . number_format($valor_comissao, 2, ',', '.'));
    }
    
    // Confirma a transação
    $conn->commit();
    
    registrarLog("Processamento concluído: {$total_processado} parcelas processadas. Total de comissões: R$ " . number_format($total_comissoes, 2, ',', '.'));
    
    echo json_encode([
        'status' => 'success',
        'message' => "Processamento concluído com sucesso. {$total_processado} parcelas processadas.",
        'total_processado' => $total_processado,
        'total_comissoes' => $total_comissoes
    ]);
    
} catch (Exception $e) {
    // Em caso de erro, desfaz as alterações
    $conn->rollback();
    
    registrarLog("ERRO: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => "Erro ao processar comissões: " . $e->getMessage()
    ]);
}

// Fecha a conexão
$conn->close(); 