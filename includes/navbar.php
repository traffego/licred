<?php
$pagina_atual = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../config.php';
?>
<?php if ($pagina_atual !== 'login.php'): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
    <div class="container-fluid px-4">

        <!-- LOGO -->
        <a class="navbar-brand" href="<?= BASE_URL ?>dashboard.php">
            <img src="<?= BASE_URL ?>assets/img/logo.png" class="bg-light p-2 rounded-5" height="40" alt="Logo">
        </a>

        <!-- HAMBURGER -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- LINKS -->
        <div class="collapse navbar-collapse bg-dark navbar-collapse" id="navbarNav">
            <ul class="navbar-nav fw-semibold">

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_contains($pagina_atual, 'clientes') ? 'active' : '' ?>" href="#" id="menuClientes"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Clientes
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark shadow-sm" aria-labelledby="menuClientes">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>clientes/index.php">Listar Clientes</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>clientes/novo.php">Novo Cliente</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (str_contains($pagina_atual, 'emprestimos') || str_contains($pagina_atual, 'parcela')) ? 'active' : '' ?>"
                        href="#" id="menuEmprestimos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Empréstimos
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark shadow-sm" aria-labelledby="menuEmprestimos">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>emprestimos/index.php">Listar Empréstimos</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>parcela/visualizar.php">Parcelas</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= str_contains($pagina_atual, 'relatorios') ? 'active' : '' ?>" href="<?= BASE_URL ?>relatorios/emprestimos.php">
                        Relatórios
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_contains($pagina_atual, 'configuracoes') ? 'active' : '' ?>" href="#" id="menuConfiguracoes"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        🛠️ Configurações
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark shadow-sm" aria-labelledby="menuConfiguracoes">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracoes/feriados.php">Feriados</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracoes/integracoes.php">Integrações</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracoes/preferencias.php">Preferências</a></li>
                    </ul>
                </li>

                <!-- MANTIDO: BOTÃO DE SAIR ORIGINAL -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>logout.php" class="nav-link">
    Sair
</a>
                </li>

            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
