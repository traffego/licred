<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';

// Valida cliente_id
$cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
if (!$cliente_id) {
    die("Cliente inválido.");
}

// Valida tipo
$tipo = $_POST['tipo'] ?? '';
if (!in_array($tipo, ['gota', 'quitacao'])) {
    die("Tipo de empréstimo inválido.");
}

// Valida valor_emprestado
$valor_emprestado = filter_input(INPUT_POST, 'valor_emprestado', FILTER_VALIDATE_FLOAT);
if (!$valor_emprestado || $valor_emprestado <= 0) {
    die("Valor emprestado inválido.");
}

// Define campos conforme o tipo
$prazo_dias = null;
$valor_parcela = null;
$juros_percentual = null;
$json_parcelas = null;
$periodo_dias = null; // reservado se for usar depois

if ($tipo === 'gota') {
    $prazo_dias = filter_input(INPUT_POST, 'prazo_dias', FILTER_VALIDATE_INT);
    $valor_parcela = filter_input(INPUT_POST, 'valor_parcela', FILTER_VALIDATE_FLOAT);
    $json_parcelas = $_POST['json_parcelas'] ?? '';

    if (!$prazo_dias || !$valor_parcela) {
        die("Dados de prazo ou parcela inválidos para gota.");
    }

    if (!json_decode($json_parcelas)) {
        die("JSON de parcelas inválido.");
    }
}

if ($tipo === 'quitacao') {
    $juros_percentual = filter_input(INPUT_POST, 'juros_percentual', FILTER_VALIDATE_FLOAT);
    if (!$juros_percentual) {
        die("Taxa de juros inválida para quitação.");
    }
}

$data_inicio = date('Y-m-d');
$status = 'ativo';

$sql = "INSERT INTO emprestimos (
  cliente_id, tipo, valor_emprestado, prazo_dias, valor_parcela,
  juros_percentual, periodo_dias, data_inicio, status, json_parcelas
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssdddddsss",
    $cliente_id,
    $tipo,
    $valor_emprestado,
    $prazo_dias,
    $valor_parcela,
    $juros_percentual,
    $periodo_dias,
    $data_inicio,
    $status,
    $json_parcelas
);

if ($stmt->execute()) {
    header("Location: index.php?sucesso=1");
    exit;
} else {
    echo "Erro ao salvar: " . $stmt->error;
}
