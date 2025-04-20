-- Criação da tabela de templates de mensagens
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserir alguns templates padrão
INSERT INTO `templates_mensagens` (`nome`, `status`, `mensagem`, `incluir_nome`, `incluir_valor`, `incluir_vencimento`, `incluir_atraso`, `incluir_valor_total`, `incluir_valor_em_aberto`, `incluir_total_parcelas`, `incluir_parcelas_pagas`, `incluir_valor_pago`, `incluir_numero_parcela`, `incluir_lista_parcelas`, `incluir_link_pagamento`, `usuario_id`) VALUES
('Lembrete de Pagamento', 'pendente', 'Olá {nome_cliente},\n\nGostaríamos de lembrar que sua parcela de {valor_parcela} vence no dia {data_vencimento}.\n\nValor total do empréstimo: {valor_total}\nValor em aberto: {valor_em_aberto}\nTotal de parcelas: {total_parcelas}\nParcelas pagas: {parcelas_pagas}\nValor já pago: {valor_pago}\n\nPara facilitar seu pagamento, acesse: {link_pagamento}\n\nAtenciosamente,\n{nomedogestor}', 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 0, 1, 1),
('Aviso de Atraso', 'atrasado', 'Olá {nome_cliente},\n\nSua parcela de {valor_parcela} que vencia em {data_vencimento} está atrasada há {atraso}.\n\nValor total do empréstimo: {valor_total}\nValor em aberto: {valor_em_aberto}\nTotal de parcelas: {total_parcelas}\nParcelas pagas: {parcelas_pagas}\nValor já pago: {valor_pago}\n\nPara regularizar sua situação, acesse: {link_pagamento}\n\nAtenciosamente,\n{nomedogestor}', 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 1),
('Confirmação de Quitação', 'quitado', 'Olá {nome_cliente},\n\nRecebemos seu pagamento e confirmamos a quitação do empréstimo.\n\nValor total do empréstimo: {valor_total}\nTotal de parcelas: {total_parcelas}\nValor pago: {valor_pago}\n\nAgradecemos a preferência!\n\nAtenciosamente,\n{nomedogestor}', 1, 0, 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 1); 