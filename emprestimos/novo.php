<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/queries.php';

// Busca clientes para o select
$clientes = buscarTodosClientes($conn);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Novo Empréstimo</h4>
                </div>
                <div class="card-body">
                    <form id="formEmprestimo">
                        <!-- Cliente -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-person-fill"></i> Cliente
                            </h5>
      <div class="mb-3">
                                <label for="cliente" class="form-label">Selecione o Cliente:</label>
                                <select class="form-select" id="cliente" name="cliente" required>
          <option value="">Selecione um cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
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
                                            <input type="text" class="form-control" id="valor_parcela" name="valor_parcela" step="0.01">
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
      
      <!-- Resumo do Cálculo -->
      <div class="row mt-3" id="resumo_calculo_container" style="display: none;">
        <div class="col-12">
          <div class="alert alert-info mb-3">
            <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Resumo do Cálculo</h6>
            <div id="resumo_calculo"></div>
          </div>
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

                        <!-- Botões -->
                        <div class="text-end">
                            <button type="button" class="btn btn-primary" onclick="calcularEmprestimo()">
                                <i class="bi bi-calculator"></i> Simular
                            </button>
                            <button type="button" class="btn btn-success" id="btnGerar" style="display:none">
                                <i class="bi bi-check-circle"></i> Gerar Empréstimo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
