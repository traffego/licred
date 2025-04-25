<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/queries.php';

// Verifica se veio ID do cliente (pode vir por POST ou GET)
$cliente_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$cliente_id) {
    $cliente_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

// Busca clientes para o select
$clientes = buscarTodosClientes($conn);

// Se tiver um cliente_id, busca seus dados
$cliente_selecionado = null;
if ($cliente_id) {
    $stmt = $conn->prepare("SELECT c.*, u.nome as investidor_nome FROM clientes c 
                            LEFT JOIN usuarios u ON c.indicacao = u.id 
                            WHERE c.id = ?");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $cliente_selecionado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!-- Incluir Select2 para estilização do select -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* Apenas ajustes mínimos para melhor integração com Bootstrap */
.select2-container .select2-selection--single {
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4dd;
    border-radius: 0.25rem;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.75rem);
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
    color: #212529;
}
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Novo Empréstimo</h4>
                </div>
                <div class="card-body">
                    <form id="formEmprestimo" action="salvar.php" method="POST">
                        <!-- Cliente -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-person-fill"></i> Cliente
                            </h5>
                            <?php if ($cliente_selecionado): ?>
                                <div class="alert alert-info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Cliente Selecionado:</strong> <?= htmlspecialchars($cliente_selecionado['nome']) ?>
                                            <?php if (!empty($cliente_selecionado['investidor_nome'])): ?>
                                                - <?= htmlspecialchars($cliente_selecionado['investidor_nome']) ?>
                                            <?php endif; ?>
                                            <?php if (!empty($cliente_selecionado['cpf_cnpj'])): ?>
                                            <br>
                                            <small class="text-muted">CPF: <?= formatarCPF($cliente_selecionado['cpf_cnpj']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="novo.php" class="btn btn-sm btn-outline-secondary">Alterar Cliente</a>
                                    </div>
                                </div>
                                <input type="hidden" name="cliente" value="<?= $cliente_selecionado['id'] ?>">
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="cliente" class="form-label">Selecione o Cliente:</label>
                                    <select class="form-select" id="cliente" name="cliente" required>
                                        <option value="">Selecione um cliente</option>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <option value="<?= $cliente['id'] ?>" data-investidor="<?= htmlspecialchars($cliente['investidor_nome'] ?? '') ?>">
                                                <?= htmlspecialchars($cliente['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Configurações -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-gear-fill"></i> Configurações
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tipo_cobranca" class="form-label">Tipo de Cobrança:</label>
                                        <select class="form-select" id="tipo_cobranca" name="tipo_cobranca" required>
                                            <option value="">Selecione</option>
                                            <option value="parcelada_comum">Parcelada Comum</option>
                                            <option value="reparcelada_com_juros">Reparcelada com Juros</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="usar_tlc" class="form-label">Taxa de Liberação de Crédito (TLC):</label>
                                        <select class="form-select" id="usar_tlc" name="usar_tlc" required>
                                            <option value="0">Não</option>
                                            <option value="1">Sim</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="tlc_valor_container" style="display: none;">
                                    <div class="mb-3">
                                        <label for="tlc_valor" class="form-label">Valor da TLC (R$):</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                            <input type="text" class="form-control" id="tlc_valor" name="tlc_valor">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Valores -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-cash"></i> Valores
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="capital" class="form-label">Capital (R$):</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-cash"></i></span>
                                            <input type="text" class="form-control" id="capital" name="capital" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="parcelas" class="form-label">Número de Parcelas:</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-123"></i></span>
                                            <input type="number" class="form-control" id="parcelas" name="parcelas" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="modo_calculo" class="form-label">Modo de Cálculo:</label>
                                        <select class="form-select" id="modo_calculo" name="modo_calculo" required>
                                            <option value="">Selecione</option>
                                            <option value="parcela">Informar Valor da Parcela</option>
                                            <option value="taxa">Informar Taxa de Juros</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="taxa_juros_container">
                                    <div class="mb-3">
                                        <label for="juros" class="form-label">Taxa de Juros (%):</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-percent"></i></span>
                                            <input type="text" class="form-control" id="juros" name="juros" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6" id="valor_parcela_container" style="display: none;">
                                    <div class="mb-3">
                                        <label for="valor_parcela" class="form-label">Valor da Parcela (R$):</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                            <input type="text" class="form-control" id="valor_parcela" name="valor_parcela">
                                            <button type="button" class="btn btn-outline-secondary" id="btn_arredondar" title="Arredondar para o próximo valor inteiro">
                                                <i class="bi bi-arrow-up-circle"></i> Arredondar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="periodo_pagamento" class="form-label">Período de Pagamento:</label>
                                        <select class="form-select" id="periodo_pagamento" name="periodo_pagamento" required>
                                            <option value="">Selecione</option>
                                            <option value="diario">Diário</option>
                                            <option value="semanal">Semanal</option>
                                            <option value="quinzenal">Quinzenal</option>
                                            <option value="trimestral">Trimestral</option>
                                            <option value="mensal">Mensal</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumo do Cálculo -->
                        <div class="row mt-3" id="resumo_calculo_container" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-info mb-3">
                                    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Resumo do Cálculo</h6>
                                    <div id="resumo_calculo"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Data e Dias -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-calendar-event"></i> Data e Dias
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="data" class="form-label">Data Inicial:</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
                                            <input type="date" class="form-control" id="data" name="data" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Dias para Não Gerar Parcelas:</label>
                                        <div class="d-flex gap-2 mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selecionarTodosDias()">
                                                <i class="bi bi-check-all"></i> Todos
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limparSelecaoDias()">
                                                <i class="bi bi-x-lg"></i> Limpar
                                            </button>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" id="domingo" class="form-check-input" value="0" name="dias_semana[]">
                                                    <label for="domingo" class="form-check-label">Domingo</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" id="segunda" class="form-check-input" value="1" name="dias_semana[]">
                                                    <label for="segunda" class="form-check-label">Segunda</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" id="terca" class="form-check-input" value="2" name="dias_semana[]">
                                                    <label for="terca" class="form-check-label">Terça</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" id="quarta" class="form-check-input" value="3" name="dias_semana[]">
                                                    <label for="quarta" class="form-check-label">Quarta</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" id="quinta" class="form-check-input" value="4" name="dias_semana[]">
                                                    <label for="quinta" class="form-check-label">Quinta</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" id="sexta" class="form-check-input" value="5" name="dias_semana[]">
                                                    <label for="sexta" class="form-check-label">Sexta</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" id="sabado" class="form-check-input" value="6" name="dias_semana[]">
                                                    <label for="sabado" class="form-check-label">Sábado</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" id="feriados" class="form-check-input" value="feriados" name="dias_semana[]">
                                                    <label for="feriados" class="form-check-label">Feriados</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botão de Submit -->
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php'">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Salvar Empréstimo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Função para formatar moeda
function formatarMoeda(input) {
    // Remove tudo que não é número
    let valor = input.value.replace(/[^\d]/g, '');
    // Converte para número com 2 casas decimais
    valor = (parseFloat(valor) / 100).toFixed(2);
    // Formata com vírgula
    input.value = valor.replace('.', ',');
}

// Função para formatar porcentagem
function formatarPorcentagem(input) {
    // Remove tudo que não é número ou vírgula
    let valor = input.value.replace(/[^\d,]/g, '');
    if (valor) {
        // Se tiver mais de uma vírgula, mantém só a primeira
        valor = valor.replace(/,/g, function(match, offset, string) {
            return offset === string.indexOf(',') ? match : '';
        });
        // Converte vírgula para ponto, formata com 2 casas e volta para vírgula
        valor = parseFloat(valor.replace(',', '.')).toFixed(2).replace('.', ',');
        input.value = valor;
    }
}

// Função para selecionar todos os dias
function selecionarTodosDias() {
    document.querySelectorAll('input[name="dias_semana[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

// Função para limpar seleção de dias
function limparSelecaoDias() {
    document.querySelectorAll('input[name="dias_semana[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Adiciona formatação aos campos de moeda
document.getElementById('capital').addEventListener('input', function() {
    formatarMoeda(this);
});
document.getElementById('valor_parcela').addEventListener('input', function() {
    formatarMoeda(this);
});
document.getElementById('tlc_valor').addEventListener('input', function() {
    formatarMoeda(this);
});

// Adiciona formatação ao campo de juros
document.getElementById('juros').addEventListener('input', function() {
    formatarPorcentagem(this);
});

// Função para verificar se todos os campos obrigatórios estão preenchidos
function verificarFormulario() {
    const form = document.getElementById('formEmprestimo');
    const btnGerar = document.getElementById('btnGerar');
    
    // Lista de campos obrigatórios
    const camposObrigatorios = [
        'cliente',
        'tipo_cobranca',
        'capital',
        'parcelas',
        'modo_calculo',
        'periodo_pagamento'
    ];

    // Verifica se os campos básicos estão preenchidos
    const camposBasicosPreenchidos = camposObrigatorios.every(campo => {
        const elemento = form.querySelector(`[name="${campo}"]`);
        return elemento && elemento.value.trim() !== '';
    });

    // Verifica o modo de cálculo e seus campos relacionados
    const modoCalculo = form.querySelector('[name="modo_calculo"]').value;
    let campoCalculoPreenchido = false;

    if (modoCalculo === 'parcela') {
        campoCalculoPreenchido = form.querySelector('[name="valor_parcela"]').value.trim() !== '';
    } else if (modoCalculo === 'taxa') {
        campoCalculoPreenchido = form.querySelector('[name="juros"]').value.trim() !== '';
    }

    // Habilita ou desabilita o botão com base nas validações
    btnGerar.disabled = !(camposBasicosPreenchidos && campoCalculoPreenchido);
}

// Adiciona listeners para todos os campos relevantes
document.querySelectorAll('#formEmprestimo [name]').forEach(element => {
    element.addEventListener('change', verificarFormulario);
    element.addEventListener('input', verificarFormulario);
});

// Verifica o formulário inicialmente
document.addEventListener('DOMContentLoaded', verificarFormulario);

// Adiciona listener específico para o modo de cálculo
document.getElementById('modo_calculo').addEventListener('change', function() {
    const valorParcelaContainer = document.getElementById('valor_parcela_container');
    const taxaJurosContainer = document.getElementById('taxa_juros_container');
    const valorParcelaInput = document.getElementById('valor_parcela');
    const jurosInput = document.getElementById('juros');
    
    if (this.value === 'parcela') {
        valorParcelaContainer.style.display = 'block';
        taxaJurosContainer.style.display = 'none';
        jurosInput.value = '';
        jurosInput.removeAttribute('required');
        valorParcelaInput.setAttribute('required', '');
    } else if (this.value === 'taxa') {
        valorParcelaContainer.style.display = 'none';
        taxaJurosContainer.style.display = 'block';
        valorParcelaInput.value = '';
        valorParcelaInput.removeAttribute('required');
        jurosInput.setAttribute('required', '');
    }
    
    verificarFormulario();
});

// Adiciona o evento de submit ao formulário
document.getElementById('formEmprestimo').addEventListener('submit', prepararEnvio);

// Função para converter valores antes do envio
function prepararEnvio(e) {
    e.preventDefault();
    
    // Converte campos de moeda
    const camposMoeda = ['capital', 'valor_parcela', 'tlc_valor'];
    camposMoeda.forEach(campo => {
        const input = document.getElementById(campo);
        if (input && input.value) {
            // Remove qualquer caractere que não seja número ou vírgula
            let valor = input.value.replace(/[^\d,]/g, '').replace(',', '.');
            // Garante que é um número válido
            if (!isNaN(valor)) {
                input.value = valor;
            }
        }
    });

    // Converte campo de juros
    const campoJuros = document.getElementById('juros');
    if (campoJuros && campoJuros.value) {
        let valor = campoJuros.value.replace(/[^\d,]/g, '').replace(',', '.');
        if (!isNaN(valor)) {
            campoJuros.value = valor;
        }
    }

    // Envia o formulário
    document.getElementById('formEmprestimo').submit();
}

// Inicializa o Select2 de forma mais simples
$(document).ready(function() {
    $('#cliente').select2({
        placeholder: 'Selecione um cliente',
        width: '100%',
        templateResult: function(data) {
            if (!data.id) return data.text;
            
            const investidor = $(data.element).data('investidor');
            if (!investidor) return data.text;
            
            return `${data.text} - ${investidor}`;
        },
        templateSelection: function(data) {
            if (!data.id) return data.text;
            
            const investidor = $(data.element).data('investidor');
            if (!investidor) return data.text;
            
            return `${data.text} - ${investidor}`;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

<?php
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}
?>
