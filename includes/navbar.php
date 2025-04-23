<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/autenticacao.php';

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-blue-dark">
    <div class="container">
        <!-- Logo e Nome -->
        <a class="navbar-brand" href="<?= BASE_URL ?>">
            <img id="logo-img" src="<?= BASE_URL ?>assets/img/logo.png" alt="Logo" height="30" class="me-2">
        </a>

        <!-- Botão Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu Principal -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= $pagina_atual === 'dashboard.php' ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>
                            Dashboard
                    </a>
                </li>

                <!-- Empréstimos -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($pagina_atual, ['emprestimos/index.php', 'emprestimos/novo.php', 'emprestimos/visualizar.php']) ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-cash-stack me-1"></i>
                            Empréstimos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>emprestimos/"><i class="bi bi-list-ul me-2"></i>Listar</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>emprestimos/novo.php"><i class="bi bi-plus-circle me-2"></i>Novo</a></li>
                    </ul>
                </li>

                <!-- Clientes -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($pagina_atual, ['clientes/index.php', 'clientes/novo.php', 'clientes/visualizar.php']) ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-people me-1"></i>
                            Clientes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>clientes/"><i class="bi bi-list-ul me-2"></i>Listar</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>clientes/novo.php"><i class="bi bi-person-plus me-2"></i>Novo</a></li>
                    </ul>
                </li>

                <!-- Relatórios -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($pagina_atual, ['relatorios/diario.php', 'relatorios/mensal.php']) ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-graph-up me-1"></i>
                            Relatórios
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>relatorios/diario.php"><i class="bi bi-calendar-day me-2"></i>Diário</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>relatorios/mensal.php"><i class="bi bi-calendar-month me-2"></i>Mensal</a></li>
                    </ul>
                </li>

                
                <!-- Feriados -->
                <li class="nav-item">
                    <a class="nav-link <?= $pagina_atual === 'feriados/index.php' ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>feriados/">
                            <i class="bi bi-calendar-event me-1"></i>
                            Feriados
                    </a>
                </li>
            </ul>

            <!-- Menu do Usuário -->
            <div class="dropdown">
                <a class="nav-link dropdown-toggle text-white active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i>
                    <span class="d-none d-lg-inline">Admin</span>
                    <span class="d-lg-none">Ver Perfil</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>configuracoes/"><i class="bi bi-gear me-2"></i>Configurações</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Botão de Fechar Mobile -->
<button class="btn-close btn-close-white d-lg-none position-fixed top-0 end-0 m-3" 
        style="z-index: 1050;" 
        data-bs-toggle="collapse" 
        data-bs-target="#navbarMain"></button>

<style>
.navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navbar .container {
    max-width: 1200px;
}

.navbar-brand {
    position: relative;
    padding: 0.5rem;
    border-radius: 12px;
    background: rgba(229, 213, 213, 0.39);
    backdrop-filter: blur(1px);
    -webkit-backdrop-filter: blur(1px);
}

.navbar-brand img {
    position: relative;
    z-index: 1;
}

.nav-link {
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    border-radius: 0.25rem;
}

.nav-link.active {
    background-color: rgba(255,255,255,0.2);
    border-radius: 0.25rem;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
}

.dropdown-item:hover {
    background-color: rgba(13,110,253,0.1);
}

.dropdown-item i {
    width: 1.25rem;
    text-align: center;
}

@media (max-width: 991.98px) {
    .navbar-collapse {
        padding: 1rem 0;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 80%;
        max-width: 320px;
        background-color: var(--bs-primary);
        z-index: 1050;
        overflow-y: auto;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .navbar-collapse.show {
        transform: translateX(0);
    }
    
    .dropdown-menu {
        border: none;
        box-shadow: none;
        background-color: rgba(255,255,255,0.1);
        padding-left: 1rem;
        position: static !important;
        transform: none !important;
    }
    
    .dropdown-item {
        color: rgba(255,255,255,0.8);
    }
    
    .dropdown-item:hover {
        background-color: rgba(255,255,255,0.1);
        color: white;
    }
    
    .nav-item.dropdown .dropdown-menu {
        display: none;
    }
    
    .nav-item.dropdown.show .dropdown-menu {
        display: block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Para dispositivos móveis, substitui o comportamento padrão do dropdown
    if (window.innerWidth < 992) {
        // Previne o comportamento padrão em mobile e implementa toggle manual
        document.querySelectorAll('.dropdown-toggle').forEach(function(dropdown) {
            dropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const parent = this.parentElement;
                
                // Fecha outros dropdowns
                document.querySelectorAll('.nav-item.dropdown.show').forEach(function(item) {
                    if (item !== parent) {
                        item.classList.remove('show');
                        item.querySelector('.dropdown-menu').style.display = 'none';
                    }
                });
                
                // Toggle do dropdown atual
                parent.classList.toggle('show');
                const menu = parent.querySelector('.dropdown-menu');
                menu.style.display = parent.classList.contains('show') ? 'block' : 'none';
            });
        });
        
        // Quando clicar em um item do dropdown, fecha o menu
        document.querySelectorAll('.dropdown-item').forEach(function(item) {
            item.addEventListener('click', function() {
                document.querySelector('.navbar-collapse').classList.remove('show');
                updateCloseButton();
            });
        });
    } else {
        // Em desktop usa o comportamento padrão do Bootstrap
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
            new bootstrap.Dropdown(dropdown);
        });
    }

    // Gerencia o botão de fechar mobile
    const navbarToggler = document.querySelector('.navbar-toggler');
    const closeButton = document.querySelector('.btn-close');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    function updateCloseButton() {
        if (window.innerWidth < 992) {
            closeButton.style.display = navbarCollapse.classList.contains('show') ? 'block' : 'none';
        } else {
            closeButton.style.display = 'none';
        }
    }

    // Fecha o menu mobile quando um item é clicado
    document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)').forEach(function(navLink) {
        navLink.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                navbarCollapse.classList.remove('show');
                updateCloseButton();
            }
        });
    });

    navbarToggler.addEventListener('click', function() {
        setTimeout(updateCloseButton, 10);
    });

    closeButton.addEventListener('click', function() {
        setTimeout(updateCloseButton, 10);
    });

    // Atualiza o botão de fechar quando a janela é redimensionada
    window.addEventListener('resize', function() {
        updateCloseButton();
        
        // Recarrega a página ao mudar entre desktop e mobile para garantir o comportamento correto
        const wasMobile = window.innerWidth < 992;
        const isMobile = window.innerWidth < 992;
        
        if (wasMobile !== isMobile) {
            // Apenas recarrega se mudar entre mobile e desktop
            location.reload();
        }
    });
    
    updateCloseButton();
});
</script>
