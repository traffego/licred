# Sistema de Empréstimos LiCred v1.8

## Visão Geral

O LiCred é um sistema completo para gerenciamento de empréstimos financeiros, focado em pequenas e médias empresas de crédito. Desenvolvido em PHP com armazenamento em banco de dados MySQL/MariaDB, o sistema oferece uma interface web responsiva que permite o controle total do ciclo de vida dos empréstimos, desde a concessão até a quitação.

## Funcionalidades Principais

### 1. Dashboard
- Visão geral das operações financeiras
- Total emprestado, recebido e pendente
- Empréstimos ativos e atrasados
- Acesso rápido às principais funcionalidades
- Painéis informativos coloridos para facilitar a visualização de dados importantes

### 2. Gestão de Clientes
- Cadastro completo de clientes (pessoa física e jurídica)
- Gerenciamento de informações de contato e endereço
- Histórico de empréstimos por cliente
- Busca e filtros avançados
- Gestão de status de clientes (Ativo, Inativo, Alerta, Atenção)
- Opt-in para comunicações via WhatsApp

### 3. Gestão de Empréstimos
- Criação de diferentes tipos de empréstimos (parcelados comuns, reparcelados com juros)
- Configuração flexível de juros e parcelas
- Cálculo automático de valores
- Definição de datas de início e prazos
- Suporte a diferentes modos de cálculo (por parcela, por juros)
- Diferentes períodos de pagamento (diário, semanal, mensal)
- Consideração de feriados e finais de semana na geração de parcelas
- TLC (Taxa de Liberação de Crédito) opcional

### 4. Gestão de Parcelas
- Geração automática de parcelas conforme configuração
- Registro de pagamentos (total ou parcial)
- Emissão de recibos de pagamento
- Controle de parcelas atrasadas
- Quitação antecipada de empréstimos
- Visualização detalhada do status de cada parcela
- Regras de carência para atraso (1 dia após vencimento)

### 5. Sistema de Cobranças
- Envio automatizado de lembretes de pagamento
- Integração com WhatsApp para cobranças (via API MenuIA)
- Templates personalizáveis de mensagens
- Histórico de mensagens enviadas
- Controle de status de envio e leitura
- Links de pagamento

### 6. Relatórios
- Relatório diário de operações
- Análise de recebimentos
- Visualização de empréstimos e pagamentos por período
- Indicadores de performance financeira
- Exportação de dados

### 7. Configurações
- Personalização dos dados da empresa
- Configuração de integrações (Mercado Pago, MenuIA)
- Gestão de feriados nacionais e locais
- Configuração de backups
- Personalização visual (logos, ícones)
- Definição de parâmetros de segurança

### 8. Segurança
- Sistema de autenticação de usuários
- Diferentes níveis de acesso
- Registro de atividades
- Configurações de senha segura
- Proteção contra ataques comuns

## Estrutura do Sistema

```
├── api/                # APIs para integração externa
├── assets/             # Arquivos estáticos (imagens, etc.)
├── backups/            # Armazenamento de backups
├── cache/              # Arquivos de cache do sistema
├── clientes/           # Módulo de gestão de clientes
├── configuracoes/      # Configurações do sistema
├── css/                # Arquivos de estilo
├── database/           # Scripts e estrutura do banco de dados
├── emprestimos/        # Módulo de gestão de empréstimos
│   └── parcelas/       # Submódulo de gestão de parcelas e cobranças
├── feriados/           # Gestão de feriados para regras de negócio
├── includes/           # Arquivos de inclusão e funções auxiliares
├── js/                 # Arquivos JavaScript
├── logs/               # Logs do sistema
├── mensagens/          # Módulo de gestão de mensagens
├── relatorios/         # Módulo de relatórios e análises
├── sql/                # Scripts SQL para configuração inicial
├── uploads/            # Armazenamento de arquivos enviados
├── .htaccess           # Configurações básicas do Apache
├── config.php          # Configurações gerais
├── dashboard.php       # Painel principal
├── index.php           # Ponto de entrada
├── login.php           # Autenticação
└── logout.php          # Encerramento de sessão
```

## Regras de Negócio Importantes

### Parcelas e Atrasos
O sistema considera uma parcela como **atrasada** apenas um dia após o vencimento. Por exemplo, uma parcela com vencimento em 23/03/2025 só será considerada atrasada a partir do dia 24/03/2025. Isso dá ao cliente o dia completo do vencimento para realizar o pagamento.

### Feriados e Dias de Cobrança
O sistema permite configurar feriados nacionais, estaduais e municipais, além de definir quais dias da semana devem ser considerados para vencimento de parcelas. É possível, por exemplo, evitar que parcelas vençam em finais de semana ou feriados.

### Envio de Mensagens
O sistema utiliza a API MenuIA para envio de mensagens via WhatsApp. Para isso, é necessário configurar as credenciais da API nas configurações do sistema:
- Endpoint (geralmente https://chatbot.menuia.com)
- App Key
- Auth Key

### Templates de Mensagens
O sistema permite a criação de templates de mensagens para diferentes situações:
- Cobranças de parcelas pendentes
- Avisos de parcelas atrasadas
- Confirmações de pagamento
- Recibos de quitação

## Requisitos Técnicos

- PHP 7.4 ou superior
- MySQL 5.7 ou MariaDB 10.3+
- Servidor web Apache
- Extensões PHP: mysqli, mbstring, json, curl, gd
- Acesso à internet para funcionalidades de WhatsApp e pagamentos online

## Instalação

1. Clone o repositório ou extraia os arquivos para seu servidor web
2. Crie um banco de dados MySQL
3. Importe o arquivo `LicredV1.sql` ou `emprestimos.sql` para criar a estrutura do banco
4. Configure o arquivo `config.php` com os dados de conexão ao banco
5. Acesse o sistema pelo navegador e faça login com as credenciais padrão
6. Altere a senha padrão nas configurações
7. Configure os dados da empresa em "Configurações"

## Configuração da API de Mensagens

1. Acesse o menu "Configurações"
2. Navegue até a seção de integração com WhatsApp
3. Preencha os campos:
   - Endpoint
   - App Key
   - Auth Key
4. Salve as configurações

## Tecnologias Utilizadas

- **Backend**: PHP
- **Banco de Dados**: MySQL/MariaDB
- **Frontend**: HTML, CSS, JavaScript, Bootstrap
- **APIs Integradas**: MenuIA (WhatsApp), Mercado Pago (opcional)
- **Arquitetura**: Aplicação web tradicional com estrutura modular

## Suporte e Contribuições

Para reportar problemas ou sugerir melhorias, entre em contato com a equipe de desenvolvimento.

---

© 2024 LiCred - Sistema de Empréstimos | Todos os direitos reservados 