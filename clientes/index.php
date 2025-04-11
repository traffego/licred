<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/head.php';

$mensagem = '';
$ultimo_id = $_SESSION['ultimo_id'] ?? null;
$id_editado = $_SESSION['id_editado'] ?? null;

if (!empty($_SESSION['sucesso'])) {
    $mensagem = 'Cliente cadastrado com sucesso!';
    unset($_SESSION['sucesso']);
}
if (!empty($_SESSION['sucesso_edicao'])) {
    $mensagem = 'Cliente atualizado com sucesso!';
    unset($_SESSION['sucesso_edicao']);
}

$resultado = $conn->query("SELECT id, nome, cpf_cnpj, telefone, status FROM clientes ORDER BY id DESC");
$clientes = $resultado->fetch_all(MYSQLI_ASSOC);
?>

<body>
<div class="container py-4">

    <?php if ($mensagem): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $mensagem ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Clientes</h3>
        <a href="novo.php" class="btn btn-primary">+ Novo Cliente</a>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" id="filtro-nome" class="form-control" placeholder="Filtrar por nome">
        </div>
        <div class="col-md-3">
            <input type="text" id="filtro-cpf" class="form-control" placeholder="Filtrar por CPF/CNPJ">
        </div>
        <div class="col-md-3">
            <select id="filtro-status" class="form-select">
                <option value="">Todos os status</option>
                <option value="Ativo">Ativo</option>
                <option value="Inativo">Inativo</option>
                <option value="Alerta">Alerta</option>
                <option value="Atenção">Atenção</option>
            </select>
        </div>
        <div class="col-md-3 text-end">
            <button id="btnExcluirSelecionados" class="btn btn-danger" disabled>Excluir Selecionados</button>
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
        <table class="table table-bordered table-hover" id="tabela-clientes">
            <thead class="table-light">
                <tr>
                    <th><input type="checkbox" id="check-todos"></th>
                    <th>Nome</th>
                    <th>CPF / CNPJ</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $c): ?>
                    <?php
                        $classe_linha = '';
                        if ($c['id'] == $ultimo_id) {
                            $classe_linha = 'table-success fw-bold';
                        } elseif ($c['id'] == $id_editado) {
                            $classe_linha = 'table-warning fw-bold';
                        }
                    ?>
                    <tr class="<?= $classe_linha ?>">
                        <td><input type="checkbox" class="check-item" value="<?= $c['id'] ?>"></td>
                        <td class="col-nome"><?= htmlspecialchars($c['nome']) ?></td>
                        <td class="col-cpf"><?= htmlspecialchars($c['cpf_cnpj']) ?></td>
                        <td><?= htmlspecialchars($c['telefone']) ?></td>
                        <td class="col-status"><?= htmlspecialchars($c['status']) ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Ações
                                </button>
                                <ul class="dropdown-menu">
                                    <li>

                                        <form action="visualizar.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="dropdown-item">🕵️ Detalhes</button>
                                        </form


                                    </li>
                                    <li>
                                        <form action="editar.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="dropdown-item">✏️ Editar</button>
                                        </form>
                                    </li>
                                    <li><button class="dropdown-item btn-excluir" data-id="<?= $c['id'] ?>">❌ Excluir</button></li>

                                    <li style="border-top: solid 1px #333;">

                                        <form action="../emprestimos/novo.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="dropdown-item">💵 Liberar Empréstimos</button>
                                            </form>

                                        </li>

                                </ul> 
                            </div>
                        </td>
                    </tr>
                <?php endforeach; unset($_SESSION['ultimo_id'], $_SESSION['id_editado']); ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de confirmação -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-labelledby="modalConfirmarExclusaoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="excluir.php">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar Exclusão</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <p>Tem certeza que deseja excluir o(s) cliente(s) selecionado(s)? Esta ação não pode ser desfeita.</p>
          <div id="inputs-exclusao"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Sim, Excluir</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>