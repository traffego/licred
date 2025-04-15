<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';

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

// Valida JSON das parcelas
$json_parcelas = $_POST['json_parcelas'] ?? '';
$parcelas_array = json_decode($json_parcelas, true);
if (!$parcelas_array || !is_array($parcelas_array)) {
    die("JSON de parcelas inválido.");
}

// Adiciona campos necessários em cada parcela
foreach ($parcelas_array as &$parcela) {
    $parcela['valor_pago'] = 0;
    $parcela['data_pagamento'] = null;
    $parcela['forma_pagamento'] = null;
    $parcela['status'] = 'pendente';
    $parcela['diferenca_transacao'] = 0;
    $parcela['acao_diferenca'] = null;
    $parcela['valor_original'] = $parcela['valor']; // Mantém o valor original da parcela
}
unset($parcela);

$json_parcelas = json_encode($parcelas_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

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

// Prepara a query de inserção
$sql = "INSERT INTO emprestimos (
    cliente_id,
    tipo_de_cobranca,
    valor_emprestado,
    parcelas,
    valor_parcela,
    juros_percentual,
    data_inicio,
    json_parcelas,
    configuracao
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro ao preparar a query: " . $conn->error);
}

$configuracao_json = json_encode($configuracao);

$stmt->bind_param(
    "issiidsss",
    $cliente_id,
    $tipo_cobranca,
    $valor_emprestado,
    $parcelas,
    $valor_parcela,
    $juros_percentual,
    $data_inicio,
    $json_parcelas,
    $configuracao_json
);

if ($stmt->execute()) {
    $emprestimo_id = $conn->insert_id;
    header("Location: index.php?sucesso=1&id=" . $emprestimo_id . "&msg=" . urlencode("Empréstimo cadastrado com sucesso!"));
    exit;
} else {
    header("Location: index.php?erro=1&msg=" . urlencode("Erro ao salvar: " . $stmt->error));
    exit;
}
