<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../feriados/queries_feriados.php';

// Valida cliente_id
$cliente_id = filter_input(INPUT_POST, 'cliente', FILTER_VALIDATE_INT);
if (!$cliente_id) {
    die("Cliente inválido.");
}

// Valida tipo de cobrança
$tipo_cobranca = $_POST['tipo_cobranca'] ?? '';
if (!in_array($tipo_cobranca, ['parcelada_comum', 'reparcelada_com_juros'])) {
    die("Tipo de cobrança inválido.");
}

// Valida capital (valor emprestado)
$valor_emprestado = filter_input(INPUT_POST, 'capital', FILTER_VALIDATE_FLOAT);
if (!$valor_emprestado || $valor_emprestado <= 0) {
    die("Valor do capital inválido.");
}

// Valida número de parcelas
$parcelas = filter_input(INPUT_POST, 'parcelas', FILTER_VALIDATE_INT);
if (!$parcelas || $parcelas <= 0) {
    die("Número de parcelas inválido.");
}

// Valida modo de cálculo
$modo_calculo = $_POST['modo_calculo'] ?? '';
if (!in_array($modo_calculo, ['parcela', 'taxa'])) {
    die("Modo de cálculo inválido.");
}

// Inicializa as variáveis
$valor_parcela = 0;
$juros_percentual = 0;

// Processa os valores conforme o modo de cálculo
if ($modo_calculo === 'parcela') {
    $valor_parcela = filter_input(INPUT_POST, 'valor_parcela', FILTER_VALIDATE_FLOAT);
    if ($valor_parcela === false || $valor_parcela <= 0) {
        die("Valor da parcela inválido.");
    }
    
    // Calcula a taxa de juros com base no valor da parcela
    $valor_total = $valor_parcela * $parcelas;
    $valor_juros = $valor_total - $valor_emprestado;
    $juros_percentual = ($valor_juros / $valor_emprestado) * 100;
    
} elseif ($modo_calculo === 'taxa') {
    $juros_percentual = filter_input(INPUT_POST, 'juros', FILTER_VALIDATE_FLOAT);
    if ($juros_percentual === false || $juros_percentual <= 0) {
        die("Taxa de juros inválida.");
    }
    // Quando for por taxa, calcula o valor da parcela
    $valor_total = $valor_emprestado * (1 + ($juros_percentual/100));
    $valor_parcela = $valor_total / $parcelas;
}

// Valida data inicial
$data_inicio = $_POST['data'] ?? '';
if (!strtotime($data_inicio)) {
    die("Data inicial inválida.");
}
$data_inicio = date('Y-m-d', strtotime($data_inicio));

// Valida período de pagamento
$periodo_pagamento = $_POST['periodo_pagamento'] ?? '';
if (!in_array($periodo_pagamento, ['diario', 'semanal', 'quinzenal', 'trimestral', 'mensal'])) {
    die("Período de pagamento inválido.");
}

// Valida dias da semana
$dias_semana = $_POST['dias_semana'] ?? [];
if (!is_array($dias_semana)) {
    die("Dias da semana inválidos.");
}

// Valida TLC
$usar_tlc = filter_input(INPUT_POST, 'usar_tlc', FILTER_VALIDATE_INT) ?? 0;
$tlc_valor = 0.00;
if ($usar_tlc) {
    $tlc_valor = filter_input(INPUT_POST, 'tlc_valor', FILTER_VALIDATE_FLOAT);
    if ($tlc_valor === false || $tlc_valor <= 0) {
        die("Valor da TLC inválido.");
    }
}

// Prepara o JSON de configuração
$configuracao = [
    'usar_tlc' => (bool)$usar_tlc,
    'tlc_valor' => (float)$tlc_valor,
    'modo_calculo' => $modo_calculo,
    'periodo_pagamento' => $periodo_pagamento,
    'dias_semana' => $dias_semana,
    'considerar_feriados' => in_array('feriados', $dias_semana),
    'valor_parcela_padrao' => $valor_parcela // Adiciona o valor padrão da parcela na configuração
];

// Prepara a query de inserção do empréstimo
$sql = "INSERT INTO emprestimos (
    cliente_id,
    tipo_de_cobranca,
    valor_emprestado,
    parcelas,
    valor_parcela,
    juros_percentual,
    data_inicio,
    configuracao
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro ao preparar a query: " . $conn->error);
}

$configuracao_json = json_encode($configuracao);

$stmt->bind_param(
    "issiidss",
    $cliente_id,
    $tipo_cobranca,
    $valor_emprestado,
    $parcelas,
    $valor_parcela,
    $juros_percentual,
    $data_inicio,
    $configuracao_json
);

// Inicia a transação para garantir que o empréstimo e as parcelas sejam inseridos
$conn->begin_transaction();

try {
    // Insere o empréstimo
    if (!$stmt->execute()) {
        throw new Exception("Erro ao inserir empréstimo: " . $stmt->error);
    }
    
    $emprestimo_id = $conn->insert_id;
    
    // Gerar as parcelas no backend
    $parcelas_array = gerarParcelas(
        $parcelas, 
        $data_inicio, 
        $periodo_pagamento, 
        $valor_parcela, 
        $dias_semana, 
        $configuracao['considerar_feriados']
    );
    
    // Prepara a inserção de parcelas
    $stmt_parcela = $conn->prepare("INSERT INTO parcelas (
        emprestimo_id, 
        numero, 
        valor, 
        vencimento, 
        status, 
        valor_pago, 
        data_pagamento, 
        forma_pagamento, 
        observacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt_parcela) {
        throw new Exception("Erro ao preparar a query de parcelas: " . $conn->error);
    }
    
    // Insere cada parcela na nova tabela
    foreach ($parcelas_array as $parcela) {
        $numero = $parcela['numero'];
        $valor = $parcela['valor'];
        $vencimento = $parcela['vencimento'];
        $status = 'pendente';
        $valor_pago = null;
        $data_pagamento = null;
        $forma_pagamento = null;
        $observacao = "valor_original: {$valor}, diferenca_transacao: 0";
        
        $stmt_parcela->bind_param(
            "iidssdsss",
            $emprestimo_id,
            $numero,
            $valor,
            $vencimento,
            $status,
            $valor_pago,
            $data_pagamento,
            $forma_pagamento,
            $observacao
        );
        
        if (!$stmt_parcela->execute()) {
            throw new Exception("Erro ao inserir parcela: " . $stmt_parcela->error);
        }
    }
    
    // Confirma a transação
    $conn->commit();
    
    header("Location: index.php?sucesso=1&id=" . $emprestimo_id . "&msg=" . urlencode("Empréstimo cadastrado com sucesso!"));
    exit;
} catch (Exception $e) {
    // Reverte a transação em caso de erro
    $conn->rollback();
    header("Location: index.php?erro=1&msg=" . urlencode("Erro ao salvar: " . $e->getMessage()));
    exit;
}

/**
 * Função para gerar as parcelas
 */
function gerarParcelas(int $numero_parcelas, string $data_inicial, string $periodo, float $valor_parcela, array $dias_semana, bool $considerar_feriados): array {
    global $conn;
    
    $parcelas = [];
    $data_atual = new DateTime($data_inicial);
    
    // Gera as parcelas
    for ($i = 1; $i <= $numero_parcelas; $i++) {
        // Na primeira parcela, não altera a data
        if ($i > 1) {
            $data_atual = calcularProximaData($data_atual, $periodo, $dias_semana, $considerar_feriados);
        }
        
        $parcelas[] = [
            'numero' => $i,
            'valor' => number_format($valor_parcela, 2, '.', ''),
            'vencimento' => $data_atual->format('Y-m-d'),
            'status' => 'pendente'
        ];
    }
    
    return $parcelas;
}

/**
 * Função para calcular a próxima data
 */
function calcularProximaData(DateTime $data_base, string $periodo, array $dias_semana, bool $considerar_feriados): DateTime {
    global $conn;
    
    $data = clone $data_base;
    
    // Adiciona dias conforme o período
    switch($periodo) {
        case 'diario':
            $data->modify('+1 day');
            break;
        case 'semanal':
            $data->modify('+7 days');
            break;
        case 'quinzenal':
            $data->modify('+15 days');
            break;
        case 'mensal':
            $data->modify('+1 month');
            break;
        case 'trimestral':
            $data->modify('+3 months');
            break;
    }
    
    // Verifica se a data cai em um dia a ser evitado
    while (diaASerEvitado($data, $dias_semana, $considerar_feriados)) {
        $data->modify('+1 day');
    }
    
    return $data;
}

/**
 * Verifica se um dia deve ser evitado
 */
function diaASerEvitado(DateTime $data, array $dias_semana, bool $considerar_feriados): bool {
    global $conn;
    
    // Verifica se o dia da semana está na lista de dias a evitar
    $dia_semana = $data->format('w');
    if (in_array($dia_semana, $dias_semana)) {
        return true;
    }
    
    // Verifica se é feriado e deve ser evitado
    if ($considerar_feriados) {
        $data_str = $data->format('Y-m-d');
        $feriado = verificarSeDataEFeriado($conn, $data_str);
        if ($feriado && $feriado['evitar'] === 'sim_evitar') {
            return true;
        }
    }
    
    return false;
}
