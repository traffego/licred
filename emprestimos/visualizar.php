<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/queries.php';

// Aceita ID tanto por GET quanto por POST
$emprestimo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$emprestimo_id) {
    $emprestimo_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$emprestimo_id) {
    echo '<div class="container py-4"><div class="alert alert-danger">ID do empréstimo não recebido.</div></div>';
    exit;
}

// Buscar informações básicas do empréstimo
$stmt = $conn->prepare("SELECT e.*, c.nome AS cliente_nome, c.cpf_cnpj as cpf, c.telefone FROM emprestimos e JOIN clientes c ON e.cliente_id = c.id WHERE e.id = ?");
$stmt->bind_param("i", $emprestimo_id);
$stmt->execute();
$emprestimo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$emprestimo) {
    echo '<div class="container py-4"><div class="alert alert-danger">Empréstimo não encontrado.</div></div>';
    exit;
}

// Decodifica as configurações e parcelas
$configuracao = json_decode($emprestimo['configuracao'], true);
$parcelas = json_decode($emprestimo['json_parcelas'], true);

// Verifica e atualiza o status das parcelas
$hoje = new DateTime();
$json_atualizado = false;

// Primeiro, vamos verificar todas as parcelas e seus status
foreach ($parcelas as $index => &$parcela) {
    $data_vencimento = new DateTime($parcela['vencimento']);
    
    // Verifica se a parcela foi paga com excedente
    if (isset($parcela['observacao']) && strpos($parcela['observacao'], 'Desconto de R$') !== false) {
        // Encontra a parcela anterior para pegar os detalhes do pagamento
        $numero_parcela_anterior = $parcela['numero'] - 1;
        $parcela_anterior = null;
        foreach ($parcelas as $p) {
            if ($p['numero'] == $numero_parcela_anterior) {
                $parcela_anterior = $p;
                break;
            }
        }
        
        if ($parcela_anterior) {
            // Extrai o valor do desconto da observação
            preg_match('/Desconto de R\$\s*([0-9,.]+)/', $parcela['observacao'], $matches);
            $valor_desconto = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
            
            // Verifica se o valor da parcela está correto (deve ser o original menos o desconto)
            $valor_original = floatval($emprestimo['valor_parcela']);
            $valor_atual = floatval($parcela['valor']);
            
            if (abs($valor_atual - ($valor_original - $valor_desconto)) > 0.01) {
                $parcela['valor'] = $valor_original - $valor_desconto;
                $json_atualizado = true;
            }
            
            // Atualiza os dados de pagamento
            $parcela['data_pagamento'] = $parcela_anterior['data_pagamento'];
            $parcela['forma_pagamento'] = 'SOBRA DA PARCELA ANTERIOR';
            
            // Verifica o status baseado no valor pago
            if (isset($parcela['valor_pago']) && $parcela['valor_pago'] >= $parcela['valor']) {
                $parcela['status'] = 'pago';
            } else {
                $parcela['status'] = 'parcial';
            }
            $json_atualizado = true;
        }
    }
    
    // Verifica parcelas vencidas
    if ($parcela['status'] === 'pendente' && $data_vencimento < $hoje) {
        $parcela['status'] = 'atrasado';
        $json_atualizado = true;
    }
    
    // Verifica parcelas pagas parcialmente
    if (isset($parcela['valor_pago']) && $parcela['valor_pago'] > 0) {
        if ($parcela['valor_pago'] < $parcela['valor']) {
            if ($parcela['status'] !== 'parcial') {
                $parcela['status'] = 'parcial';
                $json_atualizado = true;
            }
        } elseif ($parcela['valor_pago'] >= $parcela['valor'] && $parcela['status'] !== 'pago') {
            $parcela['status'] = 'pago';
            $json_atualizado = true;
        }
    }
    
    // Verifica se o valor da parcela está correto
    if (!isset($parcela['observacao']) || strpos($parcela['observacao'], 'Desconto de R$') === false) {
        $valor_original = floatval($emprestimo['valor_parcela']);
        $valor_atual = floatval($parcela['valor']);
        
        if (abs($valor_atual - $valor_original) > 0.01) {
            $parcela['valor'] = $valor_original;
            $json_atualizado = true;
        }
    }
}
unset($parcela);

// Se houve alterações, atualiza o JSON no banco
if ($json_atualizado) {
    $json_parcelas = json_encode($parcelas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("UPDATE emprestimos SET json_parcelas = ? WHERE id = ?");
    $stmt->bind_param("si", $json_parcelas, $emprestimo_id);
    $stmt->execute();
}

// Calcula os totais a partir do JSON atualizado
$pagas = 0;
$parciais = 0;
$pendentes = 0;
$vencidas = 0;
$total_pago = 0;

foreach ($parcelas as $p) {
    if ($p['status'] === 'pago') {
        $pagas++;
        $total_pago += floatval($p['valor']);
    } elseif ($p['status'] === 'parcial') {
        $parciais++;
        $total_pago += floatval($p['valor_pago'] ?? 0);
    } else {
        $data_vencimento = new DateTime($p['vencimento']);
        if ($data_vencimento < $hoje) {
            $vencidas++;
        } else {
            $pendentes++;
        }
    }
}

$total_previsto = $emprestimo['valor_parcela'] * $emprestimo['parcelas'];

?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes do Empréstimo #<?= $emprestimo['id'] ?></h2>
        <div>
            <a href="index.php" class="btn btn-outline-secondary me-2">← Voltar</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPagarMassa">
                <i class="bi bi-cash-stack"></i> Pagamento em Massa
            </button>
        </div>
    </div>

    <div class="row g-4">
        <!-- Card Cliente -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header cliente-header">
                    <h5 class="card-title mb-0">Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-person-circle me-3" style="font-size: 2.5rem; color: #2c7744;"></i>
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($emprestimo['cliente_nome']) ?></h6>
                            <span class="text-muted small"><?= formatarCPF($emprestimo['cpf'] ?? '') ?></span>
                        </div>
                    </div>
                    <div class="border-top pt-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-telephone me-2" style="color: #2c7744;"></i>
                            <div>
                                <div class="text-muted small">Telefone:</div>
                                <strong><?= formatarTelefone($emprestimo['telefone']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Valores -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header valores-header">
                    <h5 class="card-title mb-0">Valores</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Capital:</label>
                        <h6 class="mb-0">R$ <?= number_format($emprestimo['valor_emprestado'], 2, ',', '.') ?></h6>
                    </div>
                    <div class="border-top pt-3">
                        <?php if ($emprestimo['juros_percentual'] > 0): ?>
                        <div class="mb-3">
                            <label class="text-muted small">Juros:</label>
                            <h6 class="mb-0"><?= number_format($emprestimo['juros_percentual'], 2, ',', '') ?>%</h6>
                        </div>
                        <?php endif; ?>
                        <?php if ($configuracao['usar_tlc']): ?>
                        <div class="mb-3">
                            <label class="text-muted small">TLC:</label>
                            <h6 class="mb-0">R$ <?= number_format($configuracao['tlc_valor'], 2, ',', '.') ?></h6>
                        </div>
                        <?php endif; ?>
                        <div>
                            <label class="text-muted small">Total:</label>
                            <h6 class="mb-0">R$ <?= number_format($total_previsto, 2, ',', '.') ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Status das Parcelas -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header status-header">
                    <h5 class="card-title mb-0">Status das Parcelas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="text-muted small d-block">Pagas:</label>
                                <h6 class="mb-0"><?= $pagas ?></h6>
                            </div>
                            <div>
                                <label class="text-muted small d-block">Pendentes:</label>
                                <h6 class="mb-0"><?= $pendentes ?></h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="text-muted small d-block">Vencidas:</label>
                                <h6 class="mb-0"><?= $vencidas ?></h6>
                            </div>
                            <div>
                                <label class="text-muted small d-block">Total:</label>
                                <h6 class="mb-0"><?= $emprestimo['parcelas'] ?></h6>
                            </div>
                        </div>
                    </div>
                    <div class="border-top pt-3 mt-3">
                        <?php
                            $percentual_pago = ($pagas / $emprestimo['parcelas']) * 100;
                            
                            // Define a cor baseada no percentual
                            if ($percentual_pago < 25) {
                                $cor = '#dc3545'; // Vermelho
                            } elseif ($percentual_pago < 50) {
                                $cor = '#ffc107'; // Amarelo
                            } elseif ($percentual_pago < 75) {
                                $cor = '#28a745'; // Verde
                            } else {
                                $cor = '#198754'; // Verde escuro
                            }
                        ?>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?= $percentual_pago ?>%; background-color: <?= $cor ?>;" 
                                 aria-valuenow="<?= $percentual_pago ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Configurações -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header resumo-header">
                    <h5 class="card-title mb-0">Configurações</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Tipo de Cobrança:</label>
                        <h6 class="mb-0">
                            <span class="badge text-bg-info">
                                <?= ucfirst(str_replace('_', ' ', $emprestimo['tipo_de_cobranca'])) ?>
                            </span>
                        </h6>
                    </div>
                    <div class="border-top pt-3">
                        <div class="mb-3">
                            <label class="text-muted small">Período:</label>
                            <h6 class="mb-0">
                                <span class="badge text-bg-secondary">
                                    <?= ucfirst($configuracao['periodo_pagamento']) ?>
                                </span>
                            </h6>
                        </div>
                        <div>
                            <label class="text-muted small">Modo de Cálculo:</label>
                            <h6 class="mb-0">
                                <span class="badge text-bg-secondary">
                                    <?= $configuracao['modo_calculo'] === 'parcela' ? 'Por Parcela' : 'Por Taxa' ?>
                                </span>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Parcelas -->
    <div class="card mt-4">
        <div class="card-header parcelas-header">
            <h5 class="card-title mb-0">Parcelas</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Pagamento</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcelas as $p): ?>
                            <tr data-status="<?= $p['status'] ?>">
                                <td><?= $p['numero'] ?></td>
                                <td><?= date('d/m/Y', strtotime($p['vencimento'])) ?></td>
                                <td>R$ <?= number_format($p['valor'], 2, ',', '.') ?></td>
                                <td>
                                    <?php
                                        $status_class = match($p['status']) {
                                            'pago' => 'text-bg-success',
                                            'pendente' => 'text-bg-warning',
                                            'atrasado' => 'text-bg-danger',
                                            'parcial' => 'text-bg-info',
                                            default => 'text-bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $status_class ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['status'] === 'pago' || $p['status'] === 'parcial'): ?>
                                        <div class="text-muted">
                                            R$ <?= number_format($p['valor_pago'] ?? $p['valor'], 2, ',', '.') ?>
                                            <br>
                                            <small>
                                                <?php 
                                                if (isset($p['data_pagamento']) && strtotime($p['data_pagamento']) > 0): 
                                                    echo date('d/m/Y', strtotime($p['data_pagamento']));
                                                    if (isset($p['forma_pagamento']) && !empty($p['forma_pagamento'])) {
                                                        echo ' via ' . ($p['forma_pagamento'] === 'SOBRA DA PARCELA ANTERIOR' ? $p['forma_pagamento'] : ucfirst($p['forma_pagamento']));
                                                    }
                                                endif; 
                                                ?>
                                                <?php if (isset($p['observacao']) && !empty($p['observacao'])): ?>
                                                    <br><i class="bi bi-info-circle"></i> <?= htmlspecialchars($p['observacao']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    // Encontra o número da próxima parcela pendente
                                    $proxima_parcela = null;
                                    $parcela_seguinte = null;
                                    foreach ($parcelas as $parcela) {
                                        if ($parcela['status'] === 'pendente' || $parcela['status'] === 'parcial') {
                                            if ($proxima_parcela === null) {
                                                $proxima_parcela = $parcela['numero'];
                                            } else if ($parcela_seguinte === null) {
                                                $parcela_seguinte = $parcela['numero'];
                                                break;
                                            }
                                        }
                                    }

                                    if (($p['status'] === 'pendente' || $p['status'] === 'parcial') && $p['valor'] > 0): 
                                        $botoes_habilitados = ($p['numero'] === $proxima_parcela || $p['numero'] === $parcela_seguinte);
                                    ?>
                                        <button type="button" 
                                                class="btn btn-sm <?= $botoes_habilitados ? 'btn-success' : 'btn-secondary' ?> btn-pagar" 
                                                data-parcela='<?= json_encode($p) ?>'
                                                <?= !$botoes_habilitados ? 'disabled' : '' ?>
                                                title="<?= $botoes_habilitados ? 'Registrar Pagamento' : 'Pagamento disponível apenas para próximas parcelas' ?>">
                                            <i class="bi bi-cash-coin"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm <?= $botoes_habilitados ? 'btn-info' : 'btn-secondary' ?>" 
                                                onclick="enviarCobranca(<?= $emprestimo['id'] ?>, <?= $p['numero'] ?>)"
                                                <?= !$botoes_habilitados ? 'disabled' : '' ?>
                                                title="<?= $botoes_habilitados ? 'Enviar Cobrança' : 'Cobrança disponível apenas para próximas parcelas' ?>">
                                            <i class="bi bi-whatsapp"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="parcelas/recibo.php?emprestimo_id=<?= $emprestimo['id'] ?>&parcela_numero=<?= $p['numero'] ?>" 
                                           class="btn btn-sm btn-secondary" 
                                           target="_blank"
                                           title="Imprimir Recibo">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Parcela <span id="numero_parcela_display"></span>/<?= $emprestimo['parcelas'] ?></h6>
                    <span id="status_parcela_display" class="badge"></span>
                </div>

                <div class="alert alert-info mb-3">
                    <h6 class="mb-2">Cálculo do Valor a Receber:</h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Valor Total da Parcela:</span>
                        <span id="valor_total_display"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Valor Já Pago:</span>
                        <span id="valor_pago_display"></span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Valor a Receber:</span>
                        <span id="valor_a_receber_display"></span>
                    </div>
                </div>

                <form id="formPagamento">
                    <input type="hidden" id="emprestimo_id" name="emprestimo_id">
                    <input type="hidden" id="parcela_numero" name="parcela_numero">
                    <input type="hidden" id="valor_parcela" name="valor_parcela">
                    
                    <div class="mb-3">
                        <label for="valor_pago" class="form-label">Valor a Receber</label>
                        <input type="number" step="0.01" class="form-control" id="valor_pago" name="valor_pago" required>
                    </div>

                    <div class="mb-3">
                        <label for="data_pagamento" class="form-label">Data do Pagamento</label>
                        <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" required>
                    </div>

                    <div class="mb-3">
                        <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                        <select class="form-select" id="forma_pagamento" name="forma_pagamento" required>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="cartao">Cartão</option>
                            <option value="transferencia">Transferência</option>
                        </select>
                    </div>

                    <div id="opcoes_distribuicao" class="mb-3 d-none">
                        <label class="form-label">Opções de Distribuição</label>
                        <div class="alert alert-info" id="info_diferenca"></div>
                        
                        <!-- Opções para valor menor -->
                        <div id="opcoes_menor" class="d-none">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="modo_distribuicao" value="proxima_parcela" checked>
                                <label class="form-check-label">
                                    Adicionar diferença na próxima parcela
                                </label>
                            </div>
                        </div>

                        <!-- Opções para valor maior -->
                        <div id="opcoes_maior" class="d-none">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="modo_distribuicao" value="desconto_proximas" checked>
                                <label class="form-check-label">
                                    Descontar valor excedente das próximas parcelas
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="modo_distribuicao" value="desconto_ultimas">
                                <label class="form-check-label">
                                    Descontar valor excedente das últimas parcelas
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="registrarPagamento()">Registrar Pagamento</button>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializa o modal
const modalPagamento = new bootstrap.Modal(document.getElementById('modalPagamento'));

// Função para abrir o modal de pagamento
function abrirModalPagamento(emprestimo_id, parcela_numero, valor, valor_pago, status) {
    document.getElementById('emprestimo_id').value = emprestimo_id;
    document.getElementById('parcela_numero').value = parcela_numero;
    document.getElementById('valor_parcela').value = <?= $emprestimo['valor_parcela'] ?>;
    
    // Exibe o número da parcela e status
    document.getElementById('numero_parcela_display').textContent = parcela_numero;
    
    // Define a classe do badge baseado no status
    const statusBadge = document.getElementById('status_parcela_display');
    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    statusBadge.className = 'badge ' + 
        (status === 'pago' ? 'bg-success' : 
         status === 'pendente' ? 'bg-warning' : 
         status === 'atrasado' ? 'bg-danger' : 
         status === 'parcial' ? 'bg-info' : 'bg-secondary');
    
    // Calcula e exibe os valores formatados
    const valorTotal = valor;
    const valorPago = valor_pago || 0;
    const valorAReceber = valorTotal - valorPago;
    
    document.getElementById('valor_total_display').textContent = 'R$ ' + valorTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('valor_pago_display').textContent = 'R$ ' + valorPago.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('valor_a_receber_display').textContent = 'R$ ' + valorAReceber.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    
    document.getElementById('valor_pago').value = valorAReceber.toFixed(2);
    document.getElementById('data_pagamento').value = new Date().toISOString().split('T')[0];
    
    // Esconde as opções de distribuição inicialmente
    document.getElementById('opcoes_distribuicao').classList.add('d-none');
    document.getElementById('opcoes_menor').classList.add('d-none');
    document.getElementById('opcoes_maior').classList.add('d-none');
    
    const modal = new bootstrap.Modal(document.getElementById('modalPagamento'));
    modal.show();
}

// Monitora mudanças no valor pago
document.getElementById('valor_pago').addEventListener('input', function() {
    const valorOriginal = parseFloat(document.getElementById('valor_parcela').value);
    const valorPago = parseFloat(this.value);
    
    if (isNaN(valorPago)) return;
    
    const diferenca = valorPago - valorOriginal;
    const opcoesDistribuicao = document.getElementById('opcoes_distribuicao');
    const opcoesMenor = document.getElementById('opcoes_menor');
    const opcoesMaior = document.getElementById('opcoes_maior');
    const infoDiv = document.getElementById('info_diferenca');
    
    if (diferenca !== 0) {
        opcoesDistribuicao.classList.remove('d-none');
        
        if (diferenca < 0) {
            infoDiv.textContent = `Valor menor que o original. Diferença: R$ ${Math.abs(diferenca).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
            opcoesMenor.classList.remove('d-none');
            opcoesMaior.classList.add('d-none');
            // Garante que a opção padrão 'proxima_parcela' esteja selecionada
             if (!document.querySelector('input[name="modo_distribuicao"][value="proxima_parcela"]')) {
                 // Se o elemento não existir, pode ser um erro, mas por segurança checa se a div existe
                 if(opcoesMenor.querySelector('input[type="radio"]')) {
                    opcoesMenor.querySelector('input[type="radio"]').checked = true;
                 }
             } else {
                document.querySelector('input[name="modo_distribuicao"][value="proxima_parcela"]').checked = true;
             }
        } else {
            infoDiv.textContent = `Valor maior que o original. Excedente: R$ ${diferenca.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
            opcoesMaior.classList.remove('d-none');
            opcoesMenor.classList.add('d-none');
             // Garante que a opção padrão 'desconto_proximas' esteja selecionada
             if (!document.querySelector('input[name="modo_distribuicao"][value="desconto_proximas"]')) {
                 if(opcoesMaior.querySelector('input[type="radio"]')) {
                    opcoesMaior.querySelector('input[type="radio"]').checked = true;
                 }
             } else {
                 document.querySelector('input[name="modo_distribuicao"][value="desconto_proximas"]').checked = true;
             }
        }
    } else {
        opcoesDistribuicao.classList.add('d-none');
    }
});

// Função para registrar o pagamento
function registrarPagamento() {
    const parcela_numero = document.getElementById('parcela_numero').value;
    const valor_pago = parseFloat(document.getElementById('valor_pago').value);
    const data_pagamento = document.getElementById('data_pagamento').value;
    const forma_pagamento = document.getElementById('forma_pagamento').value;
    const modo_distribuicao = document.querySelector('input[name="modo_distribuicao"]:checked')?.value;
    
    // Envia os dados para o servidor
    fetch('parcelas/pagar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `emprestimo_id=<?php echo $emprestimo_id; ?>&parcela_numero=${parcela_numero}&valor_pago=${valor_pago}&data_pagamento=${data_pagamento}&forma_pagamento=${forma_pagamento}&modo_distribuicao=${modo_distribuicao}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.sucesso) {
            alert('Pagamento registrado com sucesso!');
            location.reload();
        } else {
            alert('Erro ao registrar pagamento: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao registrar pagamento. Por favor, tente novamente.');
    });
}

// Função para enviar cobrança via WhatsApp
function enviarCobranca(emprestimo_id, parcela_numero) {
    fetch(`parcelas/cobranca.php?emprestimo_id=${emprestimo_id}&parcela_numero=${parcela_numero}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'sucesso') {
                window.open(data.link, '_blank');
            } else {
                alert('Erro ao gerar link de cobrança: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar cobrança');
        });
}

// Adiciona evento de clique ao botão de pagamento
document.querySelectorAll('.btn-pagar').forEach(button => {
    button.addEventListener('click', function() {
        const parcela = JSON.parse(this.dataset.parcela);
        abrirModalPagamento(
            <?php echo $emprestimo_id; ?>,
            parcela.numero,
            parcela.valor,
            parcela.valor_pago || 0,
            parcela.status
        );
    });
});
</script>

<?php
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    $tamanho = strlen($telefone);
    if ($tamanho == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }
    return $telefone;
}
?>

<style>
/* Estilos para os cards */
.card {
    border: 1px solid rgba(0,0,0,.125);
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
}

.card-header {
    padding: 1rem;
    color: white;
}

.card-header.cliente-header {
    background: linear-gradient(45deg, #2c7744, #1a4e2c);
}

.card-header.valores-header {
    background: linear-gradient(45deg, #277553, #1b503a);
}

.card-header.status-header {
    background: linear-gradient(45deg, #246e62, #194d45);
}

.card-header.resumo-header {
    background: linear-gradient(45deg, #1f6470, #17454d);
}

.card-header.parcelas-header {
    background: linear-gradient(45deg, #1b5962, #13404a);
}

.card-title {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
}

.card-body h6 {
    font-size: 1.5rem;
    font-weight: 600;
}

.card-body .text-muted {
    font-size: 0.9rem;
}

.progress {
    height: 10px;
    margin-top: 1rem;
    background-color: #f8f9fa;
    border-radius: 10px;
}

.progress-bar {
    transition: width .6s ease, background-color .6s ease;
    border-radius: 10px;
}

/* Estilos para badges */
.badge {
    font-weight: 500;
    font-size: 0.85rem;
    padding: 0.5em 0.8em;
}

/* Estilos para a tabela */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: white;
    font-size: 0.75rem;
    text-transform: uppercase;
    padding: 0.75rem;
}

.table td {
    font-size: 0.875rem;
    color: #344767;
    vertical-align: middle;
    padding: 0.75rem;
}

/* Background das linhas da tabela por status */
.table tbody tr[data-status="pago"] {
    background-color: rgba(40, 167, 69, 0.05);
}

.table tbody tr[data-status="pendente"] {
    background-color: rgba(255, 193, 7, 0.05);
}

.table tbody tr[data-status="atrasado"] {
    background-color: rgba(220, 53, 69, 0.05);
}

.table tbody tr[data-status="parcial"] {
    background-color: rgba(23, 162, 184, 0.05);
}

/* Hover nas linhas */
.table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Ajuste para manter o hover mesmo com o background do status */
.table tbody tr[data-status="pago"]:hover {
    background-color: rgba(40, 167, 69, 0.08);
}

.table tbody tr[data-status="pendente"]:hover {
    background-color: rgba(255, 193, 7, 0.08);
}

.table tbody tr[data-status="atrasado"]:hover {
    background-color: rgba(220, 53, 69, 0.08);
}

.table tbody tr[data-status="parcial"]:hover {
    background-color: rgba(23, 162, 184, 0.08);
}

/* Estilos para botões */
.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.btn-group .btn i {
    font-size: 1rem;
}

.btn-whatsapp {
    background-color: #25D366;
    border-color: #25D366;
    color: white;
}

.btn-whatsapp:hover {
    background-color: #128C7E;
    border-color: #128C7E;
    color: white;
}

/* Estilos para textos */
.text-muted {
    color: #6c757d !important;
}

.small {
    font-size: 0.875rem;
}

/* Estilos para ícones */
.bi {
    font-size: 1.1rem;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 