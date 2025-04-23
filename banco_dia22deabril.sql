-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           10.4.32-MariaDB - mariadb.org binary distribution
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Copiando estrutura para tabela sistema_emprestimosv1_8.clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `tipo_pessoa` tinyint(1) DEFAULT 1 COMMENT '1 = Física, 2 = Jurídica',
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `nascimento` date DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `endereco` varchar(150) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `chave_pix` varchar(150) DEFAULT NULL,
  `indicacao` varchar(150) DEFAULT NULL,
  `status` enum('Ativo','Inativo','Alerta','Atenção') DEFAULT 'Ativo',
  `nome_secundario` varchar(150) DEFAULT NULL,
  `telefone_secundario` varchar(20) DEFAULT NULL,
  `endereco_secundario` varchar(150) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `opt_in_whatsapp` tinyint(1) DEFAULT 1 COMMENT 'Consentimento para receber mensagens',
  `ultima_interacao_whatsapp` timestamp NULL DEFAULT NULL COMMENT 'Data da última interação por WhatsApp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.clientes: ~6 rows (aproximadamente)
REPLACE INTO `clientes` (`id`, `nome`, `cpf`, `telefone`, `email`, `tipo_pessoa`, `cpf_cnpj`, `nascimento`, `cep`, `endereco`, `bairro`, `cidade`, `estado`, `chave_pix`, `indicacao`, `status`, `nome_secundario`, `telefone_secundario`, `endereco_secundario`, `observacoes`, `opt_in_whatsapp`, `ultima_interacao_whatsapp`) VALUES
	(2, 'Max', '', '(21) 3030-1122', 'contato@maxxisolucoes.com.br', 0, '422.102.070-00', '2001-05-10', '20000-000', 'Av. das Indústrias, 456', 'Distrito Industrial', 'Rio de Janeiro', 'RJ', 'financeiro@pix.com', 'Carlos Andrade', 'Ativo', '', '', '', 'Empresa com bom histórico de pagamento', 1, NULL),
	(8, 'Mulher Maravilhinha', '', '(23) 23232-3232', '123@gmail.com', 0, '232.323.232-32', '2324-11-23', '23232-323', 'Rua DO Meio, 1', 'Central', 'Rio de Janeiro', 'RJ', '123@gmail.com', 'Amigão', 'Ativo', 'Amigão', '(34) 56345-6345', '', '', 1, NULL),
	(9, 'Mulher Feia', '', '(63) 45634-5634', '234@gmail.com', 0, '344.634.557-66', '3651-05-12', '34563-456', 'Rua do Mangalho', 'Centro', 'Maceió', 'AL', 'asdfasdfasdfasdfasdfasdfasdfasd@gmail.com', 'Amigao', 'Ativo', '', '', '', '', 1, NULL),
	(10, 'Chico Buarque', '', '(12) 34123-4123', 'chicotebuarcot@gmail.com', 0, '345.634.563-45', '6348-11-02', '34563-456', 'Rua do meu ovo, 34', 'Centro', 'Jandira', 'BA', '', '', 'Alerta', '', '', '', '', 1, NULL),
	(12, 'Elton John', '', '(23) 23232-3232', 'eltonquixeira@gmail.com', 0, '357.334.653-45', '2001-05-10', '26000-000', 'R. Visc. de Pirajá - Ipanema', 'Centro', 'Rio de Janeiro', 'RJ', '1111111111111112', 'Amigao', 'Ativo', 'Bino', '(31) 99999-9999', '', '', 1, NULL),
	(13, 'Milton Friedman', '', '(21) 96738-0813', 'milton@campos.com', 0, '123.321.123-32', '2001-05-10', '66666-666', 'R. Visc. de Pirajá - Ipanema', '', 'Rio de Janeiro', 'RJ', 'financeiro2@pix.com', 'Amigão', 'Ativo', '', '', '', '', 1, NULL),
	(14, 'Beto Barbosa', '', '(66) 66666-6666', 'betobarbosa@licredo.com', 0, '999.988.888-88', '2001-05-10', '26311-490', 'R. Visc. de Pirajá - Ipanema', '', 'Rio de Janeiro', 'RJ', '777777777777777777777777777777', 'Amigao', 'Ativo', 'JONATHAS QUINTANILHA', '(21) 96738-0813', 'Rua Aveiro', '', 1, NULL);

-- Copiando estrutura para tabela sistema_emprestimosv1_8.cobrancas
CREATE TABLE IF NOT EXISTS `cobrancas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destinatario` varchar(20) NOT NULL COMMENT 'Número de telefone do destinatário',
  `destinatario_nome` varchar(100) DEFAULT NULL COMMENT 'Nome do destinatário',
  `conteudo` text NOT NULL COMMENT 'Conteúdo da mensagem',
  `valor` decimal(10,2) NOT NULL COMMENT 'Valor da cobrança',
  `link_pagamento` varchar(500) NOT NULL COMMENT 'Link para pagamento',
  `descricao` varchar(255) DEFAULT NULL COMMENT 'Descrição da cobrança',
  `status` enum('pendente','enviado','erro') NOT NULL DEFAULT 'pendente',
  `id_mensagem` varchar(255) DEFAULT NULL COMMENT 'ID da mensagem na API',
  `erro` text DEFAULT NULL COMMENT 'Mensagem de erro, se houver',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_envio` timestamp NULL DEFAULT NULL,
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuário que enviou a cobrança',
  PRIMARY KEY (`id`),
  KEY `destinatario` (`destinatario`),
  KEY `status` (`status`),
  KEY `usuario_id` (`usuario_id`),
  KEY `data_criacao` (`data_criacao`),
  KEY `data_envio` (`data_envio`),
  CONSTRAINT `cobrancas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.cobrancas: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela sistema_emprestimosv1_8.emprestimos
CREATE TABLE IF NOT EXISTS `emprestimos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `tipo_de_cobranca` enum('parcelada_comum','reparcelada_com_juros') NOT NULL,
  `valor_emprestado` decimal(10,2) NOT NULL,
  `parcelas` int(11) NOT NULL,
  `valor_parcela` decimal(10,2) DEFAULT NULL,
  `juros_percentual` decimal(5,2) DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `json_parcelas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json_parcelas`)),
  `configuracao` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Configurações do empréstimo em formato JSON' CHECK (json_valid(`configuracao`)),
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  KEY `idx_emprestimos_cliente_id` (`cliente_id`),
  KEY `idx_emprestimos_data_inicio` (`data_inicio`),
  CONSTRAINT `emprestimos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.emprestimos: ~2 rows (aproximadamente)
REPLACE INTO `emprestimos` (`id`, `cliente_id`, `tipo_de_cobranca`, `valor_emprestado`, `parcelas`, `valor_parcela`, `juros_percentual`, `data_inicio`, `json_parcelas`, `configuracao`, `data_criacao`, `data_atualizacao`, `status`) VALUES
	(18, 14, 'parcelada_comum', 1000.00, 30, 44.00, 32.00, '2025-04-21', NULL, '{"usar_tlc":false,"tlc_valor":0,"modo_calculo":"parcela","periodo_pagamento":"diario","dias_semana":["feriados"],"considerar_feriados":true,"valor_parcela_padrao":44}', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 'ativo'),
	(19, 14, 'parcelada_comum', 2000.00, 30, 87.00, 30.50, '2025-04-22', NULL, '{"usar_tlc":false,"tlc_valor":0,"modo_calculo":"parcela","periodo_pagamento":"diario","dias_semana":["feriados"],"considerar_feriados":true,"valor_parcela_padrao":87}', '2025-04-21 17:11:52', '2025-04-21 17:11:52', 'ativo'),
	(20, 13, 'parcelada_comum', 1000.00, 10, 110.00, 10.00, '2025-04-22', NULL, '{"usar_tlc":false,"tlc_valor":0,"modo_calculo":"parcela","periodo_pagamento":"diario","dias_semana":["feriados"],"considerar_feriados":true,"valor_parcela_padrao":110}', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 'ativo');

-- Copiando estrutura para tabela sistema_emprestimosv1_8.feriados
CREATE TABLE IF NOT EXISTS `feriados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `tipo` enum('fixo','movel') DEFAULT NULL,
  `evitar` enum('sim_evitar','nao_evitar') DEFAULT 'sim_evitar',
  `local` enum('nacional','estadual','municipal') DEFAULT 'nacional',
  `ano` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data` (`data`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.feriados: ~15 rows (aproximadamente)
REPLACE INTO `feriados` (`id`, `nome`, `data`, `tipo`, `evitar`, `local`, `ano`) VALUES
	(1, 'Confraternização Universal', '2025-01-01', 'fixo', 'sim_evitar', 'nacional', 2025),
	(2, 'Carnaval (Segunda-feira)', '2025-03-03', 'movel', 'sim_evitar', 'nacional', 2025),
	(3, 'Carnaval (Terça-feira)', '2025-03-04', 'movel', 'sim_evitar', 'nacional', 2025),
	(4, 'Quarta-feira de Cinzas (até 12h)', '2025-03-05', 'movel', 'sim_evitar', 'nacional', 2025),
	(5, 'Sexta-feira Santa (Paixão de Cristo)', '2025-04-18', 'movel', 'sim_evitar', 'nacional', 2025),
	(6, 'Tiradentes', '2025-04-21', 'fixo', 'sim_evitar', 'nacional', 2025),
	(7, 'Dia do Trabalho', '2025-05-01', 'fixo', 'sim_evitar', 'nacional', 2025),
	(8, 'Corpus Christi', '2025-06-19', 'movel', 'sim_evitar', 'nacional', 2025),
	(9, 'Independência do Brasil', '2025-09-07', 'fixo', 'sim_evitar', 'nacional', 2025),
	(10, 'Nossa Senhora Aparecida', '2025-10-12', 'fixo', 'sim_evitar', 'nacional', 2025),
	(11, 'Finados', '2025-11-02', 'fixo', 'sim_evitar', 'nacional', 2025),
	(12, 'Proclamação da República', '2025-11-15', 'fixo', 'sim_evitar', 'nacional', 2025),
	(13, 'Natal', '2025-12-25', 'fixo', 'sim_evitar', 'nacional', 2025),
	(14, 'Véspera de Natal', '2025-12-24', 'fixo', 'sim_evitar', 'nacional', 2025),
	(15, 'Véspera de Ano Novo', '2025-12-31', 'fixo', 'sim_evitar', 'nacional', 2025);

-- Copiando estrutura para tabela sistema_emprestimosv1_8.historico
CREATE TABLE IF NOT EXISTS `historico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emprestimo_id` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `emprestimo_id` (`emprestimo_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.historico: ~0 rows (aproximadamente)
REPLACE INTO `historico` (`id`, `emprestimo_id`, `tipo`, `descricao`, `valor`, `data`, `usuario_id`, `created_at`) VALUES
	(1, 16, 'quitacao', 'Quitação do empréstimo', 968.00, '2025-04-20', 1, '2025-04-21 00:59:10');

-- Copiando estrutura para tabela sistema_emprestimosv1_8.historico_mensagens
CREATE TABLE IF NOT EXISTS `historico_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destinatario` varchar(20) NOT NULL COMMENT 'Número de telefone do destinatário',
  `destinatario_nome` varchar(100) DEFAULT NULL COMMENT 'Nome do destinatário',
  `conteudo` text NOT NULL COMMENT 'Conteúdo da mensagem',
  `status` enum('enviando','enviado','falha','entregue','lida') NOT NULL DEFAULT 'enviando',
  `id_mensagem` varchar(255) DEFAULT NULL COMMENT 'ID da mensagem na API',
  `erro` text DEFAULT NULL COMMENT 'Mensagem de erro, se houver',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_envio` timestamp NULL DEFAULT NULL,
  `data_entrega` timestamp NULL DEFAULT NULL,
  `data_leitura` timestamp NULL DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuário que enviou a mensagem',
  PRIMARY KEY (`id`),
  KEY `destinatario` (`destinatario`),
  KEY `status` (`status`),
  KEY `usuario_id` (`usuario_id`),
  KEY `data_criacao` (`data_criacao`),
  KEY `data_envio` (`data_envio`),
  CONSTRAINT `historico_mensagens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.historico_mensagens: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela sistema_emprestimosv1_8.mensagens_log
CREATE TABLE IF NOT EXISTS `mensagens_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emprestimo_id` int(11) DEFAULT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `telefone` varchar(20) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` datetime NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status` enum('sucesso','erro') NOT NULL DEFAULT 'sucesso',
  `erro` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emprestimo_id` (`emprestimo_id`),
  KEY `parcela_id` (`parcela_id`),
  KEY `template_id` (`template_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `data_envio` (`data_envio`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.mensagens_log: ~0 rows (aproximadamente)
REPLACE INTO `mensagens_log` (`id`, `emprestimo_id`, `parcela_id`, `template_id`, `telefone`, `mensagem`, `data_envio`, `usuario_id`, `status`, `erro`) VALUES
	(1, 20, 91, 1, '5521967380813', 'Olá Milton Friedman,\r\n\r\nGostaríamos de lembrar que sua parcela de R$ 110,00 vence no dia 22/04/2025.\r\n\r\nValor total do empréstimo: R$ 1.100,00\r\nValor em aberto: R$ 1.100,00\r\nTotal de parcelas: 10\r\nParcelas pagas: 0\r\nValor já pago: R$ 0,00\r\n\r\nPara facilitar seu pagamento, acesse: http://localhost/licred2/sistema_emprestimos_v1.8/sistema_emprestimos_v1/pagamento/link.php?p=91\r\n\r\nAtenciosamente,\r\nGestor', '2025-04-21 20:56:34', 1, 'sucesso', NULL),
	(2, 20, 91, 9, '5521967380813', 'Olá Milton Friedman,\r\n\r\nSua parcela de R$ 110,00 que vencia em 22/04/2025 está atrasada há 0 dias.\r\n\r\nValor total do empréstimo: R$ 1.100,00\r\nValor em aberto: R$ 1.100,00\r\nTotal de parcelas: 10\r\nParcelas pagas: 0\r\nValor já pago: R$ 0,00\r\n\r\nPara regularizar sua situação, acesse: http://localhost/licred2/sistema_emprestimos_v1.8/sistema_emprestimos_v1/pagamento/link.php?p=91\r\n\r\nAtenciosamente,\r\nGestor', '2025-04-21 21:28:09', 1, 'sucesso', NULL),
	(3, 20, 91, 6, '5521967380813', 'Olá, Milton Friedman! Seja bem-vindo(a) ao sistema de empréstimos. Estamos à disposição para ajudá-lo(a). Atenciosamente, Gestor.', '2025-04-21 21:30:52', 1, 'sucesso', NULL),
	(4, 20, 91, 1, '5521967380813', 'Olá Milton Friedman,\r\n\r\nGostaríamos de lembrar que sua parcela de R$ 110,00 vence no dia 22/04/2025.\r\n\r\nValor total do empréstimo: R$ 1.100,00\r\nValor em aberto: R$ 1.100,00\r\nTotal de parcelas: 10\r\nParcelas pagas: 0\r\nValor já pago: R$ 0,00\r\n\r\nPara facilitar seu pagamento, acesse: http://localhost/licred2/sistema_emprestimos_v1.8/sistema_emprestimos_v1/pagamento/link.php?p=91\r\n\r\nAtenciosamente,\r\nGestor', '2025-04-22 11:48:44', 1, 'sucesso', NULL);

-- Copiando estrutura para tabela sistema_emprestimosv1_8.parcelas
CREATE TABLE IF NOT EXISTS `parcelas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emprestimo_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `vencimento` date NOT NULL,
  `status` enum('pendente','parcial','pago','atrasado') DEFAULT 'pendente',
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `diferenca_transacao` decimal(10,2) DEFAULT 0.00,
  `acao_diferenca` varchar(50) DEFAULT NULL,
  `valor_original` decimal(10,2) DEFAULT NULL,
  `ultima_cobranca` timestamp NULL DEFAULT NULL COMMENT 'Data da última cobrança enviada',
  `total_cobrancas` int(11) DEFAULT 0 COMMENT 'Total de cobranças enviadas',
  PRIMARY KEY (`id`),
  KEY `idx_emprestimo_numero` (`emprestimo_id`,`numero`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`vencimento`),
  CONSTRAINT `parcelas_ibfk_1` FOREIGN KEY (`emprestimo_id`) REFERENCES `emprestimos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.parcelas: ~68 rows (aproximadamente)
REPLACE INTO `parcelas` (`id`, `emprestimo_id`, `numero`, `valor`, `vencimento`, `status`, `valor_pago`, `data_pagamento`, `forma_pagamento`, `observacao`, `created_at`, `updated_at`, `diferenca_transacao`, `acao_diferenca`, `valor_original`, `ultima_cobranca`, `total_cobrancas`) VALUES
	(31, 18, 1, 44.00, '2025-04-21', 'pago', 44.00, '2025-04-21', 'dinheiro', 'valor_original: 44.00, diferenca_transacao: 0 | diferenca_transacao: 0, acao_diferenca: desconto_proximas', '2025-04-21 13:32:59', '2025-04-21 13:38:10', 0.00, NULL, NULL, NULL, 0),
	(32, 18, 2, 44.00, '2025-04-22', 'pago', 44.00, '2025-04-21', 'dinheiro', 'valor_original: 44.00, diferenca_transacao: 0 | diferenca_transacao: 0, acao_diferenca: desconto_proximas', '2025-04-21 13:32:59', '2025-04-21 13:38:21', 0.00, NULL, NULL, NULL, 0),
	(33, 18, 3, 44.00, '2025-04-23', 'pago', 44.00, '2025-04-21', 'dinheiro', 'valor_original: 44.00, diferenca_transacao: 0 | diferenca_transacao: 0, acao_diferenca: desconto_proximas', '2025-04-21 13:32:59', '2025-04-21 13:38:31', 0.00, NULL, NULL, NULL, 0),
	(34, 18, 4, 44.00, '2025-04-24', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(35, 18, 5, 44.00, '2025-04-25', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(36, 18, 6, 44.00, '2025-04-26', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(37, 18, 7, 44.00, '2025-04-27', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(38, 18, 8, 44.00, '2025-04-28', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(39, 18, 9, 44.00, '2025-04-29', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(40, 18, 10, 44.00, '2025-04-30', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(41, 18, 11, 44.00, '2025-05-01', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(42, 18, 12, 44.00, '2025-05-02', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(43, 18, 13, 44.00, '2025-05-03', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(44, 18, 14, 44.00, '2025-05-04', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(45, 18, 15, 44.00, '2025-05-05', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(46, 18, 16, 44.00, '2025-05-06', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(47, 18, 17, 44.00, '2025-05-07', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(48, 18, 18, 44.00, '2025-05-08', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(49, 18, 19, 44.00, '2025-05-09', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(50, 18, 20, 44.00, '2025-05-10', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(51, 18, 21, 44.00, '2025-05-11', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(52, 18, 22, 44.00, '2025-05-12', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(53, 18, 23, 44.00, '2025-05-13', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(54, 18, 24, 44.00, '2025-05-14', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(55, 18, 25, 44.00, '2025-05-15', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(56, 18, 26, 44.00, '2025-05-16', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(57, 18, 27, 44.00, '2025-05-17', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(58, 18, 28, 44.00, '2025-05-18', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(59, 18, 29, 44.00, '2025-05-19', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(60, 18, 30, 44.00, '2025-05-20', 'pendente', NULL, NULL, NULL, 'valor_original: 44.00, diferenca_transacao: 0', '2025-04-21 13:32:59', '2025-04-21 13:32:59', 0.00, NULL, NULL, NULL, 0),
	(61, 19, 1, 87.00, '2025-04-22', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(62, 19, 2, 87.00, '2025-04-23', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(63, 19, 3, 87.00, '2025-04-24', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(64, 19, 4, 87.00, '2025-04-25', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(65, 19, 5, 87.00, '2025-04-26', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(66, 19, 6, 87.00, '2025-04-27', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(67, 19, 7, 87.00, '2025-04-28', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(68, 19, 8, 87.00, '2025-04-29', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(69, 19, 9, 87.00, '2025-04-30', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(70, 19, 10, 87.00, '2025-05-02', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(71, 19, 11, 87.00, '2025-05-03', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(72, 19, 12, 87.00, '2025-05-04', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(73, 19, 13, 87.00, '2025-05-05', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(74, 19, 14, 87.00, '2025-05-06', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(75, 19, 15, 87.00, '2025-05-07', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(76, 19, 16, 87.00, '2025-05-08', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(77, 19, 17, 87.00, '2025-05-09', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(78, 19, 18, 87.00, '2025-05-10', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(79, 19, 19, 87.00, '2025-05-11', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(80, 19, 20, 87.00, '2025-05-12', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(81, 19, 21, 87.00, '2025-05-13', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(82, 19, 22, 87.00, '2025-05-14', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(83, 19, 23, 87.00, '2025-05-15', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(84, 19, 24, 87.00, '2025-05-16', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(85, 19, 25, 87.00, '2025-05-17', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(86, 19, 26, 87.00, '2025-05-18', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(87, 19, 27, 87.00, '2025-05-19', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(88, 19, 28, 87.00, '2025-05-20', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(89, 19, 29, 87.00, '2025-05-21', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(90, 19, 30, 87.00, '2025-05-22', 'pendente', NULL, NULL, NULL, 'valor_original: 87.00, diferenca_transacao: 0', '2025-04-21 17:11:53', '2025-04-21 17:11:53', 0.00, NULL, NULL, NULL, 0),
	(91, 20, 1, 110.00, '2025-04-22', 'atrasado', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-22 14:47:51', 0.00, NULL, NULL, NULL, 0),
	(92, 20, 2, 110.00, '2025-04-23', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0),
	(93, 20, 3, 110.00, '2025-04-24', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0),
	(94, 20, 4, 110.00, '2025-04-25', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0),
	(95, 20, 5, 110.00, '2025-04-26', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0),
	(96, 20, 6, 110.00, '2025-04-27', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0),
	(97, 20, 7, 110.00, '2025-04-28', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0),
	(98, 20, 8, 110.00, '2025-04-29', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0),
	(99, 20, 9, 110.00, '2025-04-30', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0),
	(100, 20, 10, 110.00, '2025-05-02', 'pendente', NULL, NULL, NULL, 'valor_original: 110.00, diferenca_transacao: 0', '2025-04-21 18:33:21', '2025-04-21 18:33:21', 0.00, NULL, NULL, NULL, 0);

-- Copiando estrutura para tabela sistema_emprestimosv1_8.templates_mensagens
CREATE TABLE IF NOT EXISTS `templates_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL,
  `mensagem` text NOT NULL,
  `incluir_nome` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_valor` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_vencimento` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_atraso` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_valor_total` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_valor_em_aberto` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_total_parcelas` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_parcelas_pagas` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_valor_pago` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_numero_parcela` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_lista_parcelas` tinyint(1) NOT NULL DEFAULT 0,
  `incluir_link_pagamento` tinyint(1) NOT NULL DEFAULT 0,
  `usuario_id` int(11) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `status` (`status`),
  KEY `ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.templates_mensagens: ~8 rows (aproximadamente)
REPLACE INTO `templates_mensagens` (`id`, `nome`, `status`, `mensagem`, `incluir_nome`, `incluir_valor`, `incluir_vencimento`, `incluir_atraso`, `incluir_valor_total`, `incluir_valor_em_aberto`, `incluir_total_parcelas`, `incluir_parcelas_pagas`, `incluir_valor_pago`, `incluir_numero_parcela`, `incluir_lista_parcelas`, `incluir_link_pagamento`, `usuario_id`, `ativo`, `data_criacao`, `data_atualizacao`) VALUES
	(1, 'Lembrete de Pagamento', 'pendente', 'Olá {nome_cliente},\r\n\r\nGostaríamos de lembrar que sua parcela de {valor_parcela} vence no dia {data_vencimento}.\r\n\r\nValor total do empréstimo: {valor_total}\r\nValor em aberto: {valor_em_aberto}\r\nTotal de parcelas: {total_parcelas}\r\nParcelas pagas: {parcelas_pagas}\r\nValor já pago: {valor_pago}\r\n\r\nPara facilitar seu pagamento, acesse: {link_pagamento}\r\n\r\nAtenciosamente,\r\n{nomedogestor}', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-04-16 18:20:59', '2025-04-22 00:22:37'),
	(2, 'Aviso de Atrasossss', 'atrasado', 'Olá {nome_cliente},\r\n\r\nSua parcela de {valor_parcela} que vencia em {data_vencimento} está atrasada há {atraso}.\r\n\r\nValor total do empréstimo: {valor_total}\r\nValor em aberto: {valor_em_aberto}\r\nTotal de parcelas: {total_parcelas}\r\nParcelas pagas: {parcelas_pagas}\r\nValor já pago: {valor_pago}\r\n\r\nPara regularizar sua situação, acesse: {link_pagamento}\r\n\r\nAtenciosamente,\r\n{nomedogestor}', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, '2025-04-16 18:20:59', '2025-04-22 00:22:06'),
	(3, 'Confirmação de Quitação', 'quitado', 'Olá {nome_cliente},\r\n\r\nRecebemos seu pagamento e confirmamos a quitação do empréstimo.\r\n\r\nValor total do empréstimo: {valor_total}\r\nTotal de parcelas: {total_parcelas}\r\nValor pago: {valor_pago}\r\n\r\nAgradecemos a preferência!\r\n\r\nAtenciosamente,\r\n{nomedogestor}', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-04-16 18:20:59', '2025-04-16 21:15:18'),
	(4, 'Novo Template BRabãozxcvzxcvzxcvzxcv', 'atrasado', 'asdfasdfasdfasdfasdfazxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcvzxcvsdfas', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, '2025-04-16 20:45:45', '2025-04-16 21:35:03'),
	(5, 'jhgkjhgkjghjkhgkjhg', 'quitado', 'Olá {nome_cliente},\r\n\r\nSua parcela de {valor_parcela} que vencia em {data_vencimento} está atrasada há {atraso}.\r\n\r\nValor total do empréstimo: {valor_total}\r\nValor em aberto: {valor_em_aberto}\r\nTotal de parcelas: {total_parcelas}\r\nParcelas pagas: {parcelas_pagas}\r\nValor já pago: {valor_pago}\r\n\r\nPara regularizar sua situação, acesse: {link_pagamento}\r\n\r\nAtenciosamente,\r\n{nomedogestor}', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-04-16 21:34:37', '2025-04-16 21:34:37'),
	(6, 'Boas-vindas', 'texto', 'Olá, {nome_cliente}! Seja bem-vindo(a) ao sistema de empréstimos. Estamos à disposição para ajudá-lo(a). Atenciosamente, {nomedogestor}.', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-04-21 20:11:21', '2025-04-21 20:11:21'),
	(7, 'Lembrete de Pagamento', 'texto', 'Olá, {nome_cliente}! Gostaríamos de lembrá-lo(a) sobre o pagamento que vence em breve. Se já realizou o pagamento, por favor desconsidere esta mensagem. Atenciosamente, {nomedogestor}.', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-04-21 20:11:21', '2025-04-21 20:11:21'),
	(8, 'Confirmação de Empréstimo', 'texto', 'Olá, {nome_cliente}! Seu empréstimo foi aprovado com sucesso. Para mais informações, entre em contato conosco. Atenciosamente, {nomedogestor}.', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-04-21 20:11:21', '2025-04-21 20:11:21'),
	(9, 'Teste de coisa', 'pendente', 'Olá {nome_cliente},\r\n\r\nSua parcela de {valor_parcela} que vencia em {data_vencimento} está atrasada há {atraso}.\r\n\r\nValor total do empréstimo: {valor_total}\r\nValor em aberto: {valor_em_aberto}\r\nTotal de parcelas: {total_parcelas}\r\nParcelas pagas: {parcelas_pagas}\r\nValor já pago: {valor_pago}\r\n\r\nPara regularizar sua situação, acesse: {link_pagamento}\r\n\r\nAtenciosamente,\r\n{nomedogestor}', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, '2025-04-22 00:24:27', '2025-04-22 00:24:27');

-- Copiando estrutura para tabela sistema_emprestimosv1_8.templates_mensagens_guardar2
CREATE TABLE IF NOT EXISTS `templates_mensagens_guardar2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `status` enum('texto','imagem','documento') NOT NULL DEFAULT 'texto',
  `mensagem` text NOT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.templates_mensagens_guardar2: ~3 rows (aproximadamente)
REPLACE INTO `templates_mensagens_guardar2` (`id`, `nome`, `status`, `mensagem`, `arquivo`, `ativo`, `criado_em`, `atualizado_em`) VALUES
	(1, 'Boas-vindas', 'texto', 'Olá, {nome_cliente}! Seja bem-vindo(a) ao sistema de empréstimos. Estamos à disposição para ajudá-lo(a). Atenciosamente, {nomedogestor}.', NULL, 1, '2025-04-21 17:14:53', NULL),
	(2, 'Lembrete de Pagamento', 'texto', 'Olá, {nome_cliente}! Gostaríamos de lembrá-lo(a) sobre o pagamento que vence em breve. Se já realizou o pagamento, por favor desconsidere esta mensagem. Atenciosamente, {nomedogestor}.', NULL, 1, '2025-04-21 17:14:53', NULL),
	(3, 'Confirmação de Empréstimo', 'texto', 'Olá, {nome_cliente}! Seu empréstimo foi aprovado com sucesso. Para mais informações, entre em contato conosco. Atenciosamente, {nomedogestor}.', NULL, 1, '2025-04-21 17:14:53', NULL);

-- Copiando estrutura para tabela sistema_emprestimosv1_8.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.usuarios: ~0 rows (aproximadamente)
REPLACE INTO `usuarios` (`id`, `email`, `senha`) VALUES
	(1, 'admin@teste.com', '$2a$10$I1saM1f4iI9gmOeADt8yvelB//rjtwK1h1xJcHP5PKJFjTr3IgD6y');

-- Copiando estrutura para tabela sistema_emprestimosv1_8.whatsapp_configs
CREATE TABLE IF NOT EXISTS `whatsapp_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT 'WhatsApp Principal',
  `numero` varchar(20) DEFAULT NULL COMMENT 'Número de telefone com DDD',
  `api_token` varchar(255) DEFAULT NULL COMMENT 'Token de acesso à API',
  `qrcode_data` text DEFAULT NULL COMMENT 'QR Code em base64',
  `data_qrcode` timestamp NULL DEFAULT NULL COMMENT 'Data de geração do QR Code',
  `status_conexao` enum('disconnected','connected','aguardando') NOT NULL DEFAULT 'disconnected',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `padrao` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se é o WhatsApp padrão',
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela sistema_emprestimosv1_8.whatsapp_configs: ~0 rows (aproximadamente)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
