<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/autenticacao.php';
require_once __DIR__ . '/includes/head.php';
?>

  <div class="container py-1">

      <h2 class="mb-4 text-uppercase ">🚀 Painel Financeiro</h2>

      <div class="row row-cols-1 row-cols-md-3 g-3 my-slider">

        <div class="cardBox bg-red icon-bg-bi" data-icon="bi-exclamation-triangle">
            <span class="title">Parcelas atrasadas</span>
            <span class="subtitle py-2">R$25.900,23</span>

          </div>


<div class="cardBox bg-green icon-bg-bi" data-icon="bi-cash-coin">
  <span class="title">Saldo Disponível</span>
  <span class="subtitle py-2">R$25.900,23</span>

</div>

<div class="cardBox bg-dark-sapphire icon-bg-bi" data-icon="bi-receipt">
  <span class="title">Emprestimos</span>
  <span class="subtitle py-2">5 contratos</span>

</div>

  </div>

<?php require_once __DIR__ . '/includes/footer.php' ?>
