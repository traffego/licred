<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/autenticacao.php';
require_once __DIR__ . '/../../includes/conexao.php';

// Validação dos dados recebidos
$emprestimo_id = filter_input(INPUT_GET, 'emprestimo_id', FILTER_VALIDATE_INT);
$parcela_numero = filter_input(INPUT_GET, 'parcela_numero', FILTER_VALIDATE_INT);

if (!$emprestimo_id || !$parcela_numero) {
    die("Dados inválidos para gerar recibo");
}

// Busca o empréstimo com dados do cliente
$stmt = $conn->prepare("
    SELECT e.*, c.nome AS cliente_nome, c.cpf 
    FROM emprestimos e 
    JOIN clientes c ON e.cliente_id = c.id 
    WHERE e.id = ?
");
$stmt->bind_param("i", $emprestimo_id);
$stmt->execute();
$resultado = $stmt->get_result();
$emprestimo = $resultado->fetch_assoc();

if (!$emprestimo) {
    die("Empréstimo não encontrado");
}

// Decodifica o JSON das parcelas
$parcelas = json_decode($emprestimo['json_parcelas'], true);

// Encontra a parcela
$parcela = null;
foreach ($parcelas as $p) {
    if ($p['numero'] == $parcela_numero) {
        $parcela = $p;
        break;
    }
}

if (!$parcela) {
    die("Parcela não encontrada");
}

// Formata o CPF
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// Função para escrever valor por extenso
function valorPorExtenso($valor) {
    $singular = ["centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão"];
    $plural = ["centavos", "reais", "mil", "milhões", "bilhões", "trilhões", "quatrilhões"];

    $c = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];
    $d = ["", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
    $d10 = ["dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove"];
    $u = ["", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove"];

    $z = 0;
    $valor = number_format($valor, 2, ".", ".");
    $inteiro = explode(".", $valor);
    $cont = count($inteiro);
    
    for ($i = 0; $i < $cont; $i++)
        for ($ii = strlen($inteiro[$i]); $ii < 3; $ii++)
            $inteiro[$i] = "0" . $inteiro[$i];

    $fim = $cont - ($inteiro[$cont - 1] > 0 ? 1 : 2);
    $rt = '';
    
    for ($i = 0; $i < $cont; $i++) {
        $valor = $inteiro[$i];
        $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
        $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
        $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

        $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
        $t = $cont - 1 - $i;
        $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";
        if ($valor == "000")
            $z++;
        elseif ($z > 0)
            $z--;
            
        if (($t == 1) && ($z > 0) && ($inteiro[0] > 0))
            $r .= ( ($z > 1) ? " de " : "") . $plural[$t];
            
        if ($r)
            $rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ") . $r;
    }

    return($rt ? $rt : "zero");
}

// Gera o HTML do recibo
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pagamento - Parcela <?= $parcela_numero ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .recibo {
            border: 2px solid #000;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        .recibo::after {
            content: "Via do Cliente";
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 12px;
            color: #666;
        }
        .recibo + .recibo::after {
            content: "Via da Empresa";
        }
        .titulo {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .valor {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
        }
        .assinatura {
            margin-top: 50px;
            text-align: center;
        }
        .linha {
            border-top: 1px solid #000;
            width: 200px;
            margin: 10px auto;
        }
        @media print {
            .no-print {
                display: none;
            }
            .recibo {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="no-print" style="margin-bottom: 20px;">Imprimir Recibo</button>

    <?php for ($i = 0; $i < 2; $i++): ?>
    <div class="recibo">
        <div class="titulo">Recibo de Pagamento</div>
        
        <p>
            Recebi de <strong><?= htmlspecialchars($emprestimo['cliente_nome']) ?></strong>, 
            CPF: <?= formatarCPF($emprestimo['cpf']) ?>, 
            a importância de <strong>R$ <?= number_format($parcela['valor_pago'], 2, ',', '.') ?></strong> 
            (<?= valorPorExtenso($parcela['valor_pago']) ?>), 
            referente à parcela <?= $parcela['numero'] ?> do empréstimo #<?= $emprestimo_id ?>, 
            com vencimento em <?= date('d/m/Y', strtotime($parcela['vencimento'])) ?>.
        </p>

        <p>
            Forma de pagamento: <?= ucfirst($parcela['forma_pagamento']) ?><br>
            Data do pagamento: <?= date('d/m/Y', strtotime($parcela['data_pagamento'])) ?>
        </p>

        <div class="assinatura">
            <div class="linha"></div>
            <p>Assinatura do Responsável</p>
        </div>
    </div>
    <?php endfor; ?>
</body>
</html> 