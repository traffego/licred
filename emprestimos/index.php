<?php
require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/queries.php';

$emprestimos = buscarTodosEmprestimosComCliente($conn);
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Empréstimos</h3>
    <a href="novo.php" class="btn btn-primary">+ Novo Empréstimo</a>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-md-4">
      <input type="text" id="filtro-cliente" class="form-control" placeholder="Filtrar por cliente">
    </div>
    <div class="col-md-3">
      <select id="filtro-tipo" class="form-select">
        <option value="">Todos os tipos</option>
        <option value="Gota a Gota">Dia a Dia</option>
        <option value="Quitacao">Quitacao</option>
      </select>
    </div>
    <div class="col-md-3">
      <select id="filtro-status" class="form-select">
        <option value="">Todos os status</option>
        <option value="Ativo">Ativo</option>
        <option value="Atrasado">Atrasado</option>
        <option value="Quitado">Quitado</option>
      </select>
    </div>
  </div>

  <div class="mb-2 text-end">
    <label class="form-label me-2">Mostrar:</label>
    <select id="linhasPorPagina" class="form-select d-inline-block w-auto">
      <option value="10">10</option>
      <option value="25">25</option>
      <option value="50">50</option>
      <option value="-1">Todos</option>
    </select>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover" id="tabela-emprestimos">
      <thead class="table-light">
        <tr>
          <th class="text-center"><input type="checkbox" id="check-todos"></th>
          <th>Cliente</th>
          <th>Tipo</th>
          <th>Valor (Lucro)</th>
          <th>Status</th>
          <th>Prazo</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($emprestimos as $e): ?>
          <tr class=" align-middle hm-70">
            <td class="text-center">
              
              <input type="checkbox" class="check-item" value="<?= $e['id'] ?>">
            </td>
            <td class="col-cliente text-left align-middle"><?= htmlspecialchars($e['cliente_nome']) ?></td>
            <td class="col-tipo text-center align-middle">
              <?php
                $tipos = ['gota' => 'Dia a Dia', 'quitacao' => 'Quitação'];
                echo $tipos[$e['tipo'] ?? ''];
              ?>
            </td>
            <td>R$ <?= number_format($e['valor_emprestado'], 2, ',', '.') ?><br><small class="text-muted">Total pago: R$ <?= number_format($e['total_pago'], 2, ',', '.') ?></small></td>
            <td class="col-status col d-flex justify-content-center align-items-center hm-70">
              <?php
                $statusTexto = '';
                $statusClasse = '';

                switch (strtolower($e['status'])) {
                  case 'ativo':
                    $statusTexto = 'Ativo';
                    $statusClasse = 'text-primary bg-light border border-primary text-center align-middle';
                    break;
                  case 'atrasado':
                    $statusTexto = 'Atrasado';
                    $statusClasse = 'text-danger bg-light border border-danger text-center align-middle';
                    break;
                  case 'quitado':
                  case 'finalizado':
                    $statusTexto = 'Quitado';
                    $statusClasse = 'text-success bg-light border border-success text-center align-middle';
                    break;
                  default:
                    $statusTexto = ucfirst($e['status']);
                    $statusClasse = 'text-secondary bg-light border text-center align-middle';
                }
              ?>
              <span class="badge rounded-pill fw-semibold <?= $statusClasse ?>">
                <?= $statusTexto ?>
              </span>
            </td>
            <td><?= $e['parcelas_pagas'] ?>/<?= $e['total_parcelas'] ?></td>
            <td>
              <div class="dropdown">
                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  Ações
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <form action="visualizar.php" method="POST" style="display:inline;">
                      <input type="hidden" name="id" value="<?= $e['id'] ?>">
                      <button type="submit" class="dropdown-item">🕵️ Detalhes</button>
                    </form>
                  </li>
                  <li>
                    <form action="editar.php" method="POST" style="display:inline;">
                      <input type="hidden" name="id" value="<?= $e['id'] ?>">
                      <button type="submit" class="dropdown-item">✏️ Editar</button>
                    </form>
                  </li>
                  <li>
                    <button class="dropdown-item btn-excluir" data-id="<?= $e['id'] ?>">❌ Excluir</button>
                  </li>
                </ul>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Resto do JS permanece inalterado -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?>