<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/head.php';

$cliente = null;
$cliente_id = null;
$clientes = [];

if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $cliente_id = (int) $_POST['id'];
    $stmt = $conn->prepare("SELECT id, nome FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cliente = $resultado->fetch_assoc();
    $stmt->close();
} else {
    $resultado = $conn->query("SELECT id, nome FROM clientes WHERE status = 'Ativo' ORDER BY nome ASC");
    $clientes = $resultado->fetch_all(MYSQLI_ASSOC);
}
?>

<body>
<div class="container py-4">
  <h3 class="mb-4">Simular Empréstimo<?= $cliente ? ' para ' . htmlspecialchars($cliente['nome']) : '' ?></h3>

  <form id="simuladorForm" method="POST" action="salvar.php">
    <?php if ($cliente): ?>
      <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
    <?php else: ?>
      <div class="mb-3">
        <label class="form-label">Cliente</label>
        <select name="cliente_id" class="form-select" required>
          <option value="">Selecione um cliente</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label">Tipo de Empréstimo</label>
      <select id="tipoEmprestimo" name="tipo" class="form-select">
        <option value="gota">Gota a Gota</option>
        <option value="quitacao">Com Quitação</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Valor Emprestado (R$)</label>
      <input type="number" id="valorEmprestado" name="valor_emprestado" min="0" step="0.01" class="form-control">
    </div>

    <div id="gotaGroup">
      <div class="mb-3">
        <label class="form-label">Prazo (em dias)</label>
        <input type="number" id="prazoDias" name="prazo_dias" min="1" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Valor da Parcela Diária (R$)</label>
        <input type="number" id="valorParcela" name="valor_parcela" min="0" step="0.01" class="form-control">
      </div>
    </div>

    <div id="quitacaoGroup" class="d-none">
      <div class="mb-3">
        <label class="form-label">Taxa de Juros Mensal (%)</label>
        <input type="number" id="taxaMensal" name="juros_percentual" min="0" step="0.01" value="30" class="form-control">
      </div>
    </div>

    <input type="hidden" name="json_parcelas" id="json_parcelas">

    <div class="mt-4">
      <button type="submit" id="liberarBtn" class="btn btn-primary" disabled>🔓 Liberar Empréstimo</button>
    </div>
  </form>

  <div id="resultado"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const tipoEmprestimo = document.getElementById("tipoEmprestimo");
  const valorEmprestadoInput = document.getElementById("valorEmprestado");
  const prazoDiasInput = document.getElementById("prazoDias");
  const valorParcelaInput = document.getElementById("valorParcela");
  const taxaMensalInput = document.getElementById("taxaMensal");
  const resultado = document.getElementById("resultado");
  const gotaGroup = document.getElementById("gotaGroup");
  const quitacaoGroup = document.getElementById("quitacaoGroup");
  const liberarBtn = document.getElementById("liberarBtn");

  let preenchendoAutomaticamente = false;
  let parcelasJson = [];

  function calcularParcela() {
    const valorEmprestado = parseFloat(valorEmprestadoInput.value);
    const prazo = parseInt(prazoDiasInput.value);
    if (!isNaN(valorEmprestado) && !isNaN(prazo) && prazo > 0) {
      const parcelaSugerida = valorEmprestado * (1 + (prazo / 100)) / prazo;
      preenchendoAutomaticamente = true;
      valorParcelaInput.value = parcelaSugerida.toFixed(2);
      preenchendoAutomaticamente = false;
      atualizarSimulacao();
    }
  }

  function atualizarSimulacao() {
    const tipo = tipoEmprestimo.value;
    const valorEmprestado = parseFloat(valorEmprestadoInput.value);

    if (isNaN(valorEmprestado) || valorEmprestado <= 0) {
      resultado.innerHTML = "";
      return;
    }

    if (tipo === "gota") {
      const prazo = parseInt(prazoDiasInput.value);
      const valorParcela = parseFloat(valorParcelaInput.value);
      if (isNaN(prazo) || prazo <= 0 || isNaN(valorParcela)) {
        resultado.innerHTML = "";
        return;
      }
      const totalComJuros = valorParcela * prazo;
      const jurosTotal = totalComJuros - valorEmprestado;
      const jurosPercentual = (jurosTotal / valorEmprestado) * 100;

      parcelasJson = [];
      let parcelasTable = `<table class="table table-bordered mt-3"><thead><tr><th>Parcela</th><th>Data</th><th>Valor (R$)</th></tr></thead><tbody>`;
      const hoje = new Date();
      for (let i = 0; i < prazo; i++) {
        const data = new Date(hoje);
        data.setDate(data.getDate() + i);
        const dataFormatada = data.toLocaleDateString('pt-BR');
        parcelasJson.push({ parcela: i + 1, data: dataFormatada, valor: valorParcela.toFixed(2) });
        parcelasTable += `<tr><td>${i + 1}</td><td>${dataFormatada}</td><td>${valorParcela.toFixed(2)}</td></tr>`;
      }
      parcelasTable += `</tbody></table>`;

      resultado.innerHTML = `
        <h4>Resultado - Gota a Gota</h4>
        <p><strong>Total a receber:</strong> R$ ${totalComJuros.toFixed(2)}</p>
        <p><strong>Juros estimado:</strong> ${jurosPercentual.toFixed(2)}%</p>
        ${parcelasTable}
      `;

      document.getElementById("json_parcelas").value = JSON.stringify(parcelasJson);
    }

    if (tipo === "quitacao") {
      const taxaMensal = parseFloat(taxaMensalInput.value) / 100;
      if (isNaN(taxaMensal)) {
        resultado.innerHTML = "";
        return;
      }
      const jurosMensal = valorEmprestado * taxaMensal;
      resultado.innerHTML = `
        <h4>Resultado - Com Quitação</h4>
        <p><strong>Juros Mensal:</strong> R$ ${jurosMensal.toFixed(2)}</p>
        <p>O cliente deve pagar os juros mensais de R$ ${jurosMensal.toFixed(2)} enquanto a dívida não for quitada. Para quitar, ele deve pagar o valor original de R$ ${valorEmprestado.toFixed(2)} mais os juros acumulados até o momento do pagamento.</p>
      `;
    }

    liberarBtn.disabled = !validarFormulario();
  }

  function validarFormulario() {
    const tipo = tipoEmprestimo.value;
    const valor = parseFloat(valorEmprestadoInput.value);

    if (isNaN(valor) || valor <= 0) return false;

    if (tipo === "gota") {
      const prazo = parseInt(prazoDiasInput.value);
      const parcela = parseFloat(valorParcelaInput.value);
      return !isNaN(prazo) && prazo > 0 && !isNaN(parcela) && parcela > 0;
    }

    if (tipo === "quitacao") {
      const taxa = parseFloat(taxaMensalInput.value);
      return !isNaN(taxa) && taxa > 0;
    }

    return false;
  }

  tipoEmprestimo.addEventListener("change", () => {
    if (tipoEmprestimo.value === "gota") {
      gotaGroup.classList.remove("d-none");
      quitacaoGroup.classList.add("d-none");
      calcularParcela();
    } else {
      gotaGroup.classList.add("d-none");
      quitacaoGroup.classList.remove("d-none");
      atualizarSimulacao();
    }
  });

  valorEmprestadoInput.addEventListener("input", () => {
    if (tipoEmprestimo.value === "gota") {
      calcularParcela();
    } else {
      atualizarSimulacao();
    }
  });

  prazoDiasInput.addEventListener("input", () => {
    if (tipoEmprestimo.value === "gota") calcularParcela();
  });

  valorParcelaInput.addEventListener("input", () => {
    if (!preenchendoAutomaticamente && tipoEmprestimo.value === "gota") atualizarSimulacao();
  });

  taxaMensalInput.addEventListener("input", () => {
    if (tipoEmprestimo.value === "quitacao") atualizarSimulacao();
  });

  calcularParcela();
});
</script>
</body>
</html>
