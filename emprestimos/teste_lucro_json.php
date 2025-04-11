<?php
require_once __DIR__ . '/../includes/queries.php';

$id = 1;
$resumo = buscarResumoEmprestimoId($conn, $id);

if ($resumo) {
    echo "<h3>Empréstimo ID: {$resumo['id']}</h3><hr>";
    echo "<strong>Total das parcelas previstas:</strong> R$ " . number_format($resumo['total_previsto'], 2, ',', '.') . "<br>";
    echo "<strong>Total já pago:</strong> R$ " . number_format($resumo['total_pago'], 2, ',', '.') . "<br><br>";
    echo "<strong>Valor emprestado:</strong> R$ " . number_format($resumo['valor_emprestado'], 2, ',', '.') . "<br>";
    echo "<strong>Lucro previsto:</strong> R$ " . number_format($resumo['lucro_previsto'], 2, ',', '.') . "<br>";
    echo "<strong>Ainda falta:</strong> R$ " . number_format($resumo['falta'], 2, ',', '.') . "<hr>";

    echo "<h4>JSON das parcelas:</h4>";
    echo '<pre>' . json_encode($resumo['parcelas'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
} else {
    echo "<p>Empréstimo não encontrado ou sem JSON de parcelas.</p>";
}
?>
