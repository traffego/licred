# Sistema de Gestão de Empréstimos e Cobranças

## Visão Geral

O Sistema de Gestão de Empréstimos e Cobranças é uma aplicação web desenvolvida em PHP para gerenciar o ciclo completo de empréstimos, desde o cadastro de clientes, criação de contratos de empréstimo, gerenciamento de parcelas, até a cobrança e acompanhamento de pagamentos. O sistema também oferece recursos para envio de mensagens via WhatsApp, utilizando a API Menuia para comunicação com clientes.

## Funcionalidades Principais

### 1. Gestão de Clientes
- Cadastro completo de clientes com informações pessoais
- Histórico de empréstimos por cliente
- Visualização de status de pagamentos

### 2. Gestão de Empréstimos
- Criação de novos contratos de empréstimo
- Configuração flexível de juros e taxas
- Geração automática de parcelas
- Visualização detalhada de contratos

### 3. Gestão de Parcelas
- Registro de pagamentos (total ou parcial)
- Acompanhamento de parcelas vencidas
- Regras de carência para atraso (1 dia após vencimento)
- Geração de recibos de pagamento

### 4. Sistema de Cobrança
- Visualização de parcelas pendentes e atrasadas
- Envio de mensagens de cobrança via WhatsApp
- Templates personalizáveis para diferentes situações
- Registro de histórico de comunicações

### 5. Relatórios e Dashboard
- Visão geral de empréstimos ativos
- Estatísticas de pagamentos e atrasos
- Acompanhamento de fluxo de caixa

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
├── emprestimos/        # Módulo de gestão de empréstimos e parcelas
│   └── parcelas/       # Submódulo de gestão de parcelas
├── feriados/           # Gestão de feriados para regras de negócio
├── includes/           # Arquivos de inclusão e funções auxiliares
├── js/                 # Arquivos JavaScript
├── logs/               # Logs do sistema
├── mensagens/          # Módulo de gestão de mensagens
│   └── api/            # Integração com API de mensagens (Menuia)
├── sql/                # Scripts SQL para configuração inicial
├── uploads/            # Armazenamento de arquivos enviados
├── config.php          # Configurações gerais
├── dashboard.php       # Painel principal
├── index.php           # Ponto de entrada
├── login.php           # Autenticação
└── logout.php          # Encerramento de sessão
```

## Regras de Negócio Importantes

### Parcelas e Atrasos
O sistema considera uma parcela como **atrasada** apenas um dia após o vencimento. Por exemplo, uma parcela com vencimento em 23/03/2025 só será considerada atrasada a partir do dia 24/03/2025. Isso dá ao cliente o dia completo do vencimento para realizar o pagamento.

### Envio de Mensagens
O sistema utiliza a API Menuia para envio de mensagens via WhatsApp. Para isso, é necessário configurar as credenciais da API nas configurações do sistema:
- Endpoint (geralmente https://chatbot.menuia.com)
- App Key (fornecida pelo Menuia)
- Auth Key (fornecida pelo Menuia)

### Templates de Mensagens
O sistema permite a criação de templates de mensagens para diferentes situações:
- Cobranças de parcelas pendentes
- Avisos de parcelas atrasadas
- Confirmações de pagamento
- Recibos de quitação

## Requisitos Técnicos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Extensões PHP: mysqli, mbstring, json, curl

## Instalação

1. Clone o repositório ou extraia os arquivos para seu servidor web
2. Crie um banco de dados MySQL
3. Importe os scripts SQL da pasta `sql/`
4. Configure o arquivo `config.php` com os dados de conexão ao banco
5. Acesse o sistema pelo navegador e faça login com as credenciais padrão:
   - Usuário: admin
   - Senha: admin123
6. Altere a senha padrão nas configurações

## Configuração da API de Mensagens

1. Acesse o menu "Configurações"
2. Navegue até a aba "MenuIA (WhatsApp)"
3. Preencha os campos:
   - Endpoint
   - App Key
   - Auth Key
4. Salve as configurações

## Suporte e Contribuições

Para reportar problemas ou sugerir melhorias, entre em contato com a equipe de desenvolvimento.

---

© 2024 Sistema de Gestão de Empréstimos e Cobranças | Todos os direitos reservados 