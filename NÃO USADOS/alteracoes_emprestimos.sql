-- Alterações na tabela emprestimos para o novo formulário

-- Adicionar novos campos
ALTER TABLE emprestimos
ADD COLUMN tipo_cobranca ENUM('parcelada_comum', 'reparcelada_com_juros') NOT NULL AFTER tipo,
ADD COLUMN usar_tlc TINYINT(1) NOT NULL DEFAULT 0 AFTER tipo_cobranca,
ADD COLUMN tlc_valor DECIMAL(10,2) DEFAULT NULL AFTER usar_tlc,
ADD COLUMN modo_calculo ENUM('parcela', 'taxa') NOT NULL AFTER tlc_valor,
ADD COLUMN periodo_pagamento ENUM('diario', 'semanal', 'quinzenal', 'trimestral', 'mensal') NOT NULL AFTER modo_calculo,
ADD COLUMN dias_semana JSON DEFAULT NULL AFTER periodo_pagamento,
ADD COLUMN considerar_feriados TINYINT(1) NOT NULL DEFAULT 1 AFTER dias_semana;

-- Renomear campo tipo para tipo_emprestimo
ALTER TABLE emprestimos
CHANGE COLUMN tipo tipo_emprestimo ENUM('gota', 'quitacao') NOT NULL;

-- Adicionar campos de auditoria e observações
ALTER TABLE emprestimos
ADD COLUMN observacoes TEXT DEFAULT NULL AFTER considerar_feriados,
ADD COLUMN data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER observacoes,
ADD COLUMN data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao; 