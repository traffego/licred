 <script>
    document.addEventListener('input', function () {
        let nome = document.getElementById('filtro-nome').value.toLowerCase();
        let cpf  = document.getElementById('filtro-cpf').value.toLowerCase();
        let status = document.getElementById('filtro-status').value;

        document.querySelectorAll('#tabela-clientes tbody tr').forEach(function (linha) {
            let colNome = linha.querySelector('.col-nome').textContent.toLowerCase();
            let colCpf = linha.querySelector('.col-cpf').textContent.toLowerCase();
            let colStatus = linha.querySelector('.col-status').textContent;

            let exibe = true;
            if (nome && !colNome.includes(nome)) exibe = false;
            if (cpf && !colCpf.includes(cpf)) exibe = false;
            if (status && status !== colStatus) exibe = false;

            linha.style.display = exibe ? '' : 'none';
        });
    });

    document.getElementById('check-todos').addEventListener('change', function () {
        let check = this.checked;
        document.querySelectorAll('.check-item').forEach(el => el.checked = check);
        toggleExcluirSelecionados();
    });

    document.querySelectorAll('.check-item').forEach(el => {
        el.addEventListener('change', toggleExcluirSelecionados);
    });

    function toggleExcluirSelecionados() {
        let algumMarcado = document.querySelectorAll('.check-item:checked').length > 0;
        document.getElementById('btnExcluirSelecionados').disabled = !algumMarcado;
    }

    document.getElementById('btnExcluirSelecionados').addEventListener('click', function () {
        let selecionados = document.querySelectorAll('.check-item:checked');
        let container = document.getElementById('inputs-exclusao');
        container.innerHTML = '';
        selecionados.forEach(el => {
            container.innerHTML += '<input type="hidden" name="ids[]" value="' + el.value + '">';
        });
        new bootstrap.Modal(document.getElementById('modalConfirmarExclusao')).show();
    });

    document.querySelectorAll('.btn-excluir').forEach(botao => {
        botao.addEventListener('click', function () {
            let id = this.getAttribute('data-id');
            let container = document.getElementById('inputs-exclusao');
            container.innerHTML = '<input type="hidden" name="ids[]" value="' + id + '">';
            new bootstrap.Modal(document.getElementById('modalConfirmarExclusao')).show();
        });
    });

    let linhasOriginais = Array.from(document.querySelectorAll('#tabela-clientes tbody tr'));
    const seletor = document.getElementById('linhasPorPagina');
    function paginar() {
        const qtd = parseInt(seletor.value);
        linhasOriginais.forEach((linha, index) => {
            linha.style.display = (qtd === -1 || index < qtd) ? '' : 'none';
        });
    }
    seletor.addEventListener('change', paginar);
    window.addEventListener('load', paginar);
</script>


<!-- <script src="https://cdn.jsdelivr.net/npm/darkmode-js@1.5.7/lib/darkmode-js.min.js"></script>
<script>
  const options = {
    bottom: '32px', 
    right: '32px', 
    left: 'unset', 
    time: '0.5s', 
    mixColor: '#fff', 
    backgroundColor: '#fff',
    buttonColorDark: '#100f2c',
    buttonColorLight: '#fff',
    saveInCookies: true,
    label: '🌓',
    autoMatchOsTheme: true
  }

  const darkmode = new Darkmode(options);
  darkmode.showWidget();
</script> -->


<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/min/tiny-slider.js"></script>
<script>
  var slider = tns({
    container: '.my-slider',
    items: 1,
    slideBy: 'page',
    autoplay: true,
    autoplayButtonOutput: false,
    autoplayTimeout: 3000,
    mouseDrag: true,
    gutter: 10,
    controls: false,
    nav: true,
    navPosition: 'bottom',
    responsive: {
      768: {
        disable: true
      }
    },

  });
</script> -->

  <script>
    document.querySelectorAll('.icon-bg-bi').forEach(el => {
      const iconName = el.getAttribute('data-icon');
      if (iconName) {
        const icon = document.createElement('i');
        icon.className = `bi ${iconName}`;
        icon.setAttribute('aria-hidden', 'true');
        el.appendChild(icon);
      }
    });
  </script>

<!-- Footer -->
 
<footer class="bg-blue-dark text-white mt-5 py-4">
  <div class="container">
    <div class="row g-4">
      <!-- Coluna 1: Logo e Descrição -->
      <div class="col-md-3 mb-3">
        <a href="<?= BASE_URL ?>" class="mb-3 d-block">
          <img id="logo-footer" src="<?= BASE_URL ?>assets/img/logo.png" alt="Logo" height="40">
        </a>
        <p class="small opacity-75">Sistema de gestão de empréstimos e controle financeiro desenvolvido para facilitar suas operações.</p>
      </div>
      
      <!-- Coluna 2: Links Rápidos -->
      <div class="col-md-3 mb-3">
        <h5 class="fw-bold mb-3">Links Rápidos</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="<?= BASE_URL ?>dashboard.php" class="text-white text-decoration-none"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>emprestimos/" class="text-white text-decoration-none"><i class="bi bi-cash-stack me-2"></i>Empréstimos</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>clientes/" class="text-white text-decoration-none"><i class="bi bi-people me-2"></i>Clientes</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>relatorios/diario.php" class="text-white text-decoration-none"><i class="bi bi-graph-up me-2"></i>Relatórios</a></li>
        </ul>
      </div>
      
      <!-- Coluna 3: Contato -->
      <div class="col-md-3 mb-3">
        <h5 class="fw-bold mb-3">Contato</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><i class="bi bi-envelope me-2"></i>contato@empresa.com</li>
          <li class="mb-2"><i class="bi bi-telephone me-2"></i>(00) 12345-6789</li>
          <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Rua Exemplo, 123</li>
        </ul>
      </div>
      
      <!-- Coluna 4: Redes Sociais -->
      <div class="col-md-3 mb-3">
        <h5 class="fw-bold mb-3">Siga-nos</h5>
        <div class="d-flex gap-3 fs-4">
          <a href="#" class="text-white"><i class="bi bi-facebook"></i></a>
          <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
          <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
          <a href="#" class="text-white"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>
    </div>
    
    <hr class="my-4 opacity-25">
    
    <!-- Copyright -->
    <div class="row">
      <div class="col-md-6">
        <p class="mb-0 small">&copy; <?= date('Y') ?> Sistema de Empréstimos. Todos os direitos reservados.</p>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="mb-0 small">Desenvolvido com <i class="bi bi-heart-fill text-danger"></i> por Sua Empresa</p>
      </div>
    </div>
  </div>
</footer>

<!-- Estilo do Footer -->
<style>
  .bg-blue-dark {
    background-color: #0c3559;
  }
  
  footer {
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
  }
  
  footer a {
    transition: opacity 0.2s ease;
  }
  
  footer a:hover {
    opacity: 0.8;
  }
  
  footer .bi {
    width: 1.25rem;
    display: inline-block;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/functions.js"></script>
</body>
</html>