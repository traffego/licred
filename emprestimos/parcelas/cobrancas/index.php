<?php
// Instruções de saída de buffer
ob_start();

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/autenticacao.php';
require_once __DIR__ . '/../../../includes/conexao.php';
require_once __DIR__ . '/../../../includes/head.php';

// Função para atualizar parcelas atrasadas
function atualizarParcelasAtrasadas($conn) {
    $hoje = date('Y-m-d');
    
    // Prepara a query que atualiza todas as parcelas pendentes com vencimento anterior a hoje
    $sql = "UPDATE parcelas SET status = 'atrasado' 
            WHERE status = 'pendente' AND vencimento < ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoje);
    $stmt->execute();
    
    // Retorna o número de linhas afetadas
    return $stmt->affected_rows;
}

// Executar atualização de parcelas atrasadas antes de consultar
$parcelas_atualizadas = atualizarParcelasAtrasadas($conn);

// Se parcelas foram atualizadas, recarregar a página para refletir as mudanças
if ($parcelas_atualizadas > 0) {
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Filtros
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$filtro_cliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';

// Verificar se há mensagem de sucesso na URL
$mensagem_sucesso = '';
$parcela_paga = '';
$parcela_total = '';
$submensagem = '';
$cliente_nome = '';
$emprestimo_id = '';
$valor_emprestimo = '';
$mostrar_modal = false;

if (isset($_GET['sucesso']) && $_GET['sucesso'] == '1') {
    $mostrar_modal = true;
    
    if (isset($_GET['parcela'])) {
        $parcela_paga = $_GET['parcela'];
        
        // Obter informações adicionais se disponíveis
        if (isset($_GET['cliente'])) {
            $cliente_nome = urldecode($_GET['cliente']);
        }
        
        if (isset($_GET['emprestimo'])) {
            $emprestimo_id = $_GET['emprestimo'];
        }
        
        if (isset($_GET['parcela_total'])) {
            $parcela_total = $_GET['parcela_total'];
        }
        
        if (isset($_GET['valor_emprestimo'])) {
            $valor_emprestimo = $_GET['valor_emprestimo'];
        }
        
        // Criar mensagem personalizada
        if (!empty($cliente_nome)) {
            if (!empty($parcela_paga) && !empty($parcela_total) && !empty($valor_emprestimo)) {
                $valor_formatado = number_format($valor_emprestimo, 2, ',', '.');
                $mensagem_completa = "Obrigado $cliente_nome, você pagou a parcela $parcela_paga de $parcela_total do seu empréstimo de R$$valor_formatado reais.";
            } else if (!empty($parcela_paga) && !empty($parcela_total)) {
                $mensagem_completa = "Obrigado $cliente_nome, você pagou a parcela $parcela_paga de $parcela_total do seu empréstimo.";
            } else {
                $mensagem_completa = "Obrigado $cliente_nome, seu pagamento foi registrado com sucesso!";
            }
        } else {
            $mensagem_completa = "Pagamento registrado com sucesso!";
        }
    } else {
        $mensagem_sucesso = 'Operação realizada com sucesso!';
        $submensagem = '';
    }
}

// Verificar se há mensagem de sucesso de envio de mensagem
if (isset($_GET['mensagem_enviada']) && $_GET['mensagem_enviada'] == '1') {
    $mostrar_modal = true;
    
    if (isset($_GET['parcela'])) {
        $parcela_numero = $_GET['parcela'];
        $mensagem_sucesso = "Mensagem enviada com sucesso para a parcela $parcela_numero!";
    } else {
        $mensagem_sucesso = "Mensagem enviada com sucesso!";
    }
    $submensagem = '';
}

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
        WHEN p.status = 'pendente' AND p.vencimento < CURDATE() THEN 1 -- Mesma prioridade que atrasado
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
    $parcelas_atrasadas = array_filter($parcelas, function($p) { return $p['status'] == 'atrasado'; });
    $parcelas_parciais = array_filter($parcelas, function($p) { return $p['status'] == 'parcial'; });
    $parcelas_pagas = array_filter($parcelas, function($p) { return $p['status'] == 'pago'; });
    
    // Calcular totais por status
    $valor_pendente = array_sum(array_map(function($p) { return $p['valor']; }, $parcelas_pendentes));
    $valor_atrasado = array_sum(array_map(function($p) { return $p['valor']; }, $parcelas_atrasadas));
    $valor_parcial = array_sum(array_map(function($p) { 
        return isset($p['valor_pago']) ? ($p['valor'] - $p['valor_pago']) : $p['valor']; 
    }, $parcelas_parciais));
    $valor_pago = array_sum(array_map(function($p) { return $p['valor']; }, $parcelas_pagas));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Total de Parcelas</h6>
                    <h4 class="mb-0"><?= $total_parcelas ?></h4>
                    <div class="small">R$ <?= number_format($valor_total, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h6 class="card-title">Parcelas Pendentes</h6>
                    <h4 class="mb-0"><?= count($parcelas_pendentes) ?></h4>
                    <div class="small">R$ <?= number_format($valor_pendente, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Parcelas Atrasadas</h6>
                    <h4 class="mb-0"><?= count($parcelas_atrasadas) ?></h4>
                    <div class="small">R$ <?= number_format($valor_atrasado, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Parcelas Parciais</h6>
                    <h4 class="mb-0"><?= count($parcelas_parciais) ?></h4>
                    <div class="small">R$ <?= number_format($valor_parcial, 2, ',', '.') ?></div>
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
        <!-- Versão Desktop (Tabela) -->
        <div class="card d-none d-md-block">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tabela-parcelas">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Telefone</th>
                                <th>Empréstimo</th>
                                <th>Parcela</th>
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
                                        $status_class = 'bg-warning text-dark';
                                        break;
                                }
                            ?>
                            <tr class="<?= $parcela['status'] === 'atrasado' ? 'table-danger' : '' ?>">
                                <td><?= htmlspecialchars($parcela['cliente_nome']) ?></td>
                                <td><?= formatarTelefone($parcela['telefone']) ?></td>
                                <td>
                                    <span class="badge bg-secondary">#<?= $parcela['emprestimo_id'] ?></span>
                                    <small class="d-block text-muted"><?= ucfirst(str_replace('_', ' ', $parcela['tipo_de_cobranca'])) ?></small>
                                </td>
                                <td>
                                    <div><strong><?= $parcela['parcela_numero'] ?> de <?= $parcela['parcelas'] ?></strong></div>
                                    <div>R$ <?= number_format($parcela['valor'], 2, ',', '.') ?></div>
                                    <div class="small text-muted"><?= date('d/m/Y', strtotime($parcela['vencimento'])) ?></div>
                                    <?php if ($parcela['status'] === 'parcial' && isset($parcela['valor_pago'])): ?>
                                        <div class="small">
                                            <span class="text-success">Pago: R$ <?= number_format($parcela['valor_pago'], 2, ',', '.') ?></span><br>
                                            <span class="text-danger">Falta: R$ <?= number_format($parcela['valor'] - $parcela['valor_pago'], 2, ',', '.') ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $status_class ?>"><?= ucfirst($parcela['status']) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL ?>emprestimos/visualizar.php?id=<?= $parcela['emprestimo_id'] ?>&parcela=<?= $parcela['parcela_numero'] ?>&origem=cobrancas#pagamento" class="btn btn-sm btn-success" title="Registrar Pagamento">
                                            <i class="bi bi-cash-coin"></i> Pagar
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary" title="Enviar Cobrança" onclick="enviarCobranca(<?= $parcela['cliente_id'] ?>, <?= $parcela['emprestimo_id'] ?>, <?= $parcela['parcela_id'] ?>)">
                                            <i class="bi bi-whatsapp"></i> Cobrar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Versão Mobile (Cards) -->
        <div class="d-md-none">
            <?php foreach ($parcelas as $parcela): 
                // Define classes para status
                $status_class = '';
                $status_bg_class = '';
                switch ($parcela['status']) {
                    case 'pago':
                        $status_class = 'bg-success text-white';
                        $status_bg_class = 'border-success';
                        break;
                    case 'parcial':
                        $status_class = 'bg-info text-white';
                        $status_bg_class = 'border-info';
                        break;
                    case 'atrasado':
                        $status_class = 'bg-danger text-white';
                        $status_bg_class = 'border-danger';
                        break;
                    case 'pendente':
                        $status_class = 'bg-warning text-dark';
                        $status_bg_class = 'border-warning';
                        break;
                }
            ?>
            <div class="card mb-3 <?= $parcela['status'] === 'atrasado' ? 'border-danger' : $status_bg_class ?>" style="border-left-width: 5px;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?= htmlspecialchars($parcela['cliente_nome']) ?></h5>
                        <small><?= formatarTelefone($parcela['telefone']) ?></small>
                    </div>
                    <span class="badge <?= $status_class ?>"><?= ucfirst($parcela['status']) ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="fw-bold mb-1">Empréstimo:</div>
                            <div>
                                <span class="badge bg-secondary">#<?= $parcela['emprestimo_id'] ?></span>
                                <small class="d-block text-muted"><?= ucfirst(str_replace('_', ' ', $parcela['tipo_de_cobranca'])) ?></small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold mb-1">Parcela:</div>
                            <div><strong><?= $parcela['parcela_numero'] ?> de <?= $parcela['parcelas'] ?></strong></div>
                            <div>R$ <?= number_format($parcela['valor'], 2, ',', '.') ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="fw-bold mb-1">Vencimento:</div>
                        <div><?= date('d/m/Y', strtotime($parcela['vencimento'])) ?></div>
                    </div>
                    
                    <?php if ($parcela['status'] === 'parcial' && isset($parcela['valor_pago'])): ?>
                    <div class="mb-3">
                        <div class="fw-bold mb-1">Pagamento Parcial:</div>
                        <div class="d-flex justify-content-between">
                            <span class="text-success">Pago: R$ <?= number_format($parcela['valor_pago'], 2, ',', '.') ?></span>
                            <span class="text-danger">Falta: R$ <?= number_format($parcela['valor'] - $parcela['valor_pago'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= BASE_URL ?>emprestimos/visualizar.php?id=<?= $parcela['emprestimo_id'] ?>&parcela=<?= $parcela['parcela_numero'] ?>&origem=cobrancas#pagamento" class="btn btn-success flex-grow-1" title="Registrar Pagamento">
                            <i class="bi bi-cash-coin"></i> Pagar
                        </a>
                        <button type="button" class="btn btn-primary flex-grow-1" title="Enviar Cobrança" onclick="enviarCobranca(<?= $parcela['cliente_id'] ?>, <?= $parcela['emprestimo_id'] ?>, <?= $parcela['parcela_id'] ?>)">
                            <i class="bi bi-whatsapp"></i> Cobrar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Sucesso -->
<div class="modal fade" id="modalSucesso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                </div>
                <h4 class="mb-3" id="mensagem-completa">Pagamento Registrado!</h4>
                <?php if (!empty($emprestimo_id)): ?>
                <div id="info-emprestimo" class="mb-2">
                    <strong>Empréstimo:</strong> #<?= $emprestimo_id ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer justify-content-center border-0 pb-4">
                <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
                    <i class="bi bi-check-lg me-2"></i>OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializa variáveis e funções assim que o documento estiver carregado
$(document).ready(function() {
    // Mostrar modal de sucesso se necessário
    <?php if ($mostrar_modal): ?>
    try {
        console.log('Tentando mostrar modal de sucesso');
        
        // Definir mensagem completa
        $('#mensagem-completa').text('<?= $mensagem_completa ?>');
        
        // Mostrar o modal
        $('#modalSucesso').modal('show');
        
        // Remover parâmetros da URL sem recarregar a página
        const url = new URL(window.location.href);
        url.searchParams.delete('sucesso');
        url.searchParams.delete('parcela');
        url.searchParams.delete('parcela_total');
        url.searchParams.delete('cliente');
        url.searchParams.delete('emprestimo');
        url.searchParams.delete('valor');
        url.searchParams.delete('valor_emprestimo');
        url.searchParams.delete('mensagem_enviada');
        window.history.replaceState({}, document.title, url);
        
        console.log('Modal mostrado com sucesso');
    } catch (error) {
        console.error('Erro ao mostrar modal:', error);
        alert('<?= $mensagem_completa ?>');
    }
    <?php endif; ?>
    
    // Inicializa DataTable
    $('#tabela-parcelas').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        pageLength: 25,
        responsive: true,
        order: [[4, 'asc'], [3, 'asc']], // Ordena por status (col 4) e depois por parcela (col 3)
        columnDefs: [
            { responsivePriority: 1, targets: [0, 3, 4, 5] } // Colunas prioritárias em visualização responsiva
        ]
    });
});

// Adiciona suporte para ordenação de datas em formato brasileiro (dd/mm/yyyy)
$.extend($.fn.dataTable.ext.oSort, {
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
</script>

<?php
// Função para formatar telefone
function formatarTelefone($telefone) {
    // Remove caracteres não numéricos
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    
    // Verifica se é celular (11 dígitos) ou telefone fixo (10 dígitos)
    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) === 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    } else {
        return $telefone; // Retorna sem formatação se não for um formato reconhecido
    }
}

require_once __DIR__ . '/../../../includes/footer.php';
?> 