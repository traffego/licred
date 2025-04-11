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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>