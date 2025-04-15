-- Criação da tabela emprestimos
CREATE TABLE IF NOT EXISTS emprestimos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    tipo_de_cobranca ENUM('parcelada_comum', 'reparcelada_com_juros') NOT NULL,
    valor_emprestado DECIMAL(10,2) NOT NULL,
    parcelas INT NOT NULL,
    valor_parcela DECIMAL(10,2) NULL,
    juros_percentual DECIMAL(5,2) NULL,
    data_inicio DATE NOT NULL,
    json_parcelas JSON NULL,
    configuracao JSON NOT NULL COMMENT 'Configurações do empréstimo em formato JSON',
    observacoes TEXT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para melhor performance
CREATE INDEX idx_emprestimos_cliente_id ON emprestimos(cliente_id);
CREATE INDEX idx_emprestimos_data_inicio ON emprestimos(data_inicio);

-- Exemplo de como seria o JSON de configuração:
/*
{
    "usar_tlc": false,
    "tlc_valor": 0.00,
    "modo_calculo": "parcela",
    "periodo_pagamento": "mensal",
    "dias_semana": ["sabado", "domingo"],
    "considerar_feriados": true
}
*/ 