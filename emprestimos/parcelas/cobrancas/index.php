<?php
// Instruções de saída de buffer
ob_start();

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/autenticacao.php';
require_once __DIR__ . '/../../../includes/conexao.php';
require_once __DIR__ . '/../../../includes/head.php';

// Filtros
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$filtro_cliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';

// Prepara a query base
$sql = "
    SELECT 
        p.id as parcela_id,
        p.numero as parcela_numero,
        p.valor,
        p.valor_pago,
        p.vencimento,
        p.status,
        p.data_pagamento,
        p.forma_pagamento,
        e.id as emprestimo_id,
        e.valor_emprestado,
        e.parcelas,
        e.valor_parcela,
        e.tipo_de_cobranca,
        c.id as cliente_id,
        c.nome as cliente_nome,
        c.telefone
    FROM 
        parcelas p
    INNER JOIN 
        emprestimos e ON p.emprestimo_id = e.id
    INNER JOIN 
        clientes c ON e.cliente_id = c.id
    WHERE 
        1=1
";

// Adiciona filtros à query se fornecidos
$parametros = [];
$tipos = '';

if (!empty($filtro_status)) {
    $sql .= " AND p.status = ?";
    $parametros[] = $filtro_status;
    $tipos .= 's';
}

if (!empty($filtro_data_inicio)) {
    $sql .= " AND p.vencimento >= ?";
    $parametros[] = $filtro_data_inicio;
    $tipos .= 's';
}

if (!empty($filtro_data_fim)) {
    $sql .= " AND p.vencimento <= ?";
    $parametros[] = $filtro_data_fim;
    $tipos .= 's';
}

if (!empty($filtro_cliente)) {
    $sql .= " AND c.nome LIKE ?";
    $parametros[] = "%$filtro_cliente%";
    $tipos .= 's';
}

// Adiciona ordem padrão
$sql .= " ORDER BY 
    CASE 
        WHEN p.status = 'atrasado' THEN 1
        WHEN p.status = 'pendente' AND p.vencimento < CURDATE() THEN 2
        WHEN p.status = 'pendente' THEN 3
        WHEN p.status = 'parcial' THEN 4
        WHEN p.status = 'pago' THEN 5
        ELSE 6
    END,
    p.vencimento ASC, 
    c.nome ASC";

// Prepara e executa a query
$stmt = $conn->prepare($sql);

if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$result = $stmt->get_result();
$parcelas = $result->fetch_all(MYSQLI_ASSOC);

// Busca todos os clientes para o filtro
$clientes = [];
$stmt_clientes = $conn->query("SELECT id, nome FROM clientes ORDER BY nome");
while ($cliente = $stmt_clientes->fetch_assoc()) {
    $clientes[] = $cliente;
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Parcelas para Cobrança</h3>
        <a href="<?= BASE_URL ?>emprestimos/index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar para Empréstimos
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendente" <?= $filtro_status == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="parcial" <?= $filtro_status == 'parcial' ? 'selected' : '' ?>>Parcial</option>
                        <option value="pago" <?= $filtro_status == 'pago' ? 'selected' : '' ?>>Pago</option>
                        <option value="atrasado" <?= $filtro_status == 'atrasado' ? 'selected' : '' ?>>Atrasado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Vencimento Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= $filtro_data_inicio ?>">
                </div>
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Vencimento Fim</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= $filtro_data_fim ?>">
                </div>
                <div class="col-md-3">
                    <label for="cliente" class="form-label">Cliente</label>
                    <input type="text" name="cliente" id="cliente" class="form-control" placeholder="Nome do cliente" value="<?= htmlspecialchars($filtro_cliente) ?>">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">Limpar</a>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estatísticas Rápidas -->
    <?php
    $total_parcelas = count($parcelas);
    $valor_total = array_sum(array_map(function($p) { return $p['valor']; }, $parcelas));
    $parcelas_pendentes = array_filter($parcelas, function($p) { return $p['status'] == 'pendente'; });
    $parcelas_atrasadas = array_filter($parcelas, function($p) { 
        return $p['status'] == 'atrasado' || ($p['status'] == 'pendente' && strtotime($p['vencimento']) < time());
    });
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Total de Parcelas</h6>
                    <h4 class="mb-0"><?= $total_parcelas ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Valor Total</h6>
                    <h4 class="mb-0">R$ <?= number_format($valor_total, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h6 class="card-title">Parcelas Pendentes</h6>
                    <h4 class="mb-0"><?= count($parcelas_pendentes) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Parcelas Atrasadas</h6>
                    <h4 class="mb-0"><?= count($parcelas_atrasadas) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Parcelas -->
    <?php if (empty($parcelas)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            Nenhuma parcela encontrada com os filtros aplicados.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tabela-parcelas">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Telefone</th>
                                <th>Empréstimo</th>
                                <th>Parcela</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parcelas as $parcela): 
                                // Define classes para status
                                $status_class = '';
                                switch ($parcela['status']) {
                                    case 'pago':
                                        $status_class = 'bg-success text-white';
                                        break;
                                    case 'parcial':
                                        $status_class = 'bg-info text-white';
                                        break;
                                    case 'atrasado':
                                        $status_class = 'bg-danger text-white';
                                        break;
                                    case 'pendente':
                                        // Verifica se está atrasada mas ainda marcada como pendente
                                        if (strtotime($parcela['vencimento']) < time()) {
                                            $status_class = 'bg-danger text-white';
                                        } else {
                                            $status_class = 'bg-warning text-dark';
                                        }
                                        break;
                                }
                            ?>
                            <tr class="<?= ($parcela['status'] === 'atrasado' || ($parcela['status'] === 'pendente' && strtotime($parcela['vencimento']) < time())) ? 'table-danger' : '' ?>">
                                <td><?= htmlspecialchars($parcela['cliente_nome']) ?></td>
                                <td><?= formatarTelefone($parcela['telefone']) ?></td>
                                <td>
                                    <span class="badge bg-secondary">#<?= $parcela['emprestimo_id'] ?></span>
                                    <small class="d-block text-muted"><?= ucfirst(str_replace('_', ' ', $parcela['tipo_de_cobranca'])) ?></small>
                                </td>
                                <td><?= $parcela['parcela_numero'] ?> de <?= $parcela['parcelas'] ?></td>
                                <td>
                                    <strong>R$ <?= number_format($parcela['valor'], 2, ',', '.') ?></strong>
                                    <?php if (!empty($parcela['valor_pago'])): ?>
                                        <small class="d-block text-success">Pago: R$ <?= number_format($parcela['valor_pago'], 2, ',', '.') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($parcela['vencimento'])) ?></td>
                                <td>
                                    <span class="badge <?= $status_class ?>"><?= ucfirst($parcela['status']) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL ?>emprestimos/visualizar.php?id=<?= $parcela['emprestimo_id'] ?>#pagamento" class="btn btn-sm btn-outline-success" title="Registrar Pagamento">
                                            <i class="bi bi-cash-coin"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Enviar Cobrança" onclick="enviarCobranca(<?= $parcela['cliente_id'] ?>, <?= $parcela['emprestimo_id'] ?>, <?= $parcela['parcela_id'] ?>)">
                                            <i class="bi bi-whatsapp"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>emprestimos/visualizar.php?id=<?= $parcela['emprestimo_id'] ?>" class="btn btn-sm btn-outline-info" title="Ver Empréstimo">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Adiciona suporte para ordenação de datas em formato brasileiro (dd/mm/yyyy)
$.extend($.fn.dataTableExt.oSort, {
    "date-br-pre": function(a) {
        if (a == null || a === "") {
            return 0;
        }
        
        // Remove tags HTML se houver
        let strippedString = a.replace(/<.*?>/g, "");
        
        // Formato dd/mm/yyyy
        let dateParts = strippedString.trim().split('/');
        if (dateParts.length < 3) return 0;
        
        let day = dateParts[0];
        let month = dateParts[1];
        let year = dateParts[2];
        
        return (year + month + day) * 1;
    },
    "date-br-asc": function(a, b) {
        return a - b;
    },
    "date-br-desc": function(a, b) {
        return b - a;
    }
});

// Função para formatar a mensagem de cobrança
function enviarCobranca(clienteId, emprestimoId, parcelaId) {
    // Busca a parcela com AJAX
    fetch(`${BASE_URL}emprestimos/parcelas/api_parcela.php?emprestimo_id=${emprestimoId}&parcela_id=${parcelaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const parcela = data.parcela;
                const cliente = data.cliente;
                
                // Formata a mensagem de cobrança
                const dataFormatada = new Date(parcela.vencimento).toLocaleDateString('pt-BR');
                const valorFormatado = parseFloat(parcela.valor).toLocaleString('pt-BR', { 
                    style: 'currency', 
                    currency: 'BRL' 
                });
                
                let mensagem = `Olá ${cliente.nome}, tudo bem? 😊\n\n`;
                mensagem += `Gostaríamos de lembrar sobre a parcela ${parcela.numero}/${parcela.total_parcelas} `;
                mensagem += `do seu empréstimo com vencimento em ${dataFormatada}, `;
                mensagem += `no valor de ${valorFormatado}.\n\n`;
                mensagem += `Caso já tenha efetuado o pagamento, por favor desconsidere esta mensagem.`;
                
                // Codifica a mensagem para URL
                const mensagemCodificada = encodeURIComponent(mensagem);
                
                // Abre o WhatsApp com a mensagem pré-preenchida
                window.open(`https://wa.me/${formatarNumeroWhatsApp(cliente.telefone)}?text=${mensagemCodificada}`, '_blank');
            } else {
                alert('Erro ao buscar dados da parcela: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar a requisição. Veja o console para mais detalhes.');
        });
}

// Função para formatar telefone para padrão WhatsApp
function formatarNumeroWhatsApp(telefone) {
    // Remove todos os caracteres não numéricos
    const numero = telefone.replace(/\D/g, '');
    
    // Verifica se começa com 0 e remove
    if (numero.startsWith('0')) {
        return '55' + numero.substring(1);
    }
    
    // Se não tem o código do país, adiciona
    if (!numero.startsWith('55')) {
        return '55' + numero;
    }
    
    return numero;
}

// Inicializa DataTable
$(document).ready(function() {
    $('#tabela-parcelas').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        pageLength: 25,
        responsive: true,
        order: [[6, 'asc'], [5, 'asc']], // Ordena por status (col 6) e depois por vencimento (col 5)
        columnDefs: [
            { "type": "date-br", "targets": 5 } // Define a coluna de vencimento como formato de data BR
        ]
    });
});
</script>

<?php
// Função para formatar telefone
function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    
    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } 
    elseif (strlen($telefone) === 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    }
    
    return $telefone;
}

require_once __DIR__ . '/../../../includes/footer.php';
?> 