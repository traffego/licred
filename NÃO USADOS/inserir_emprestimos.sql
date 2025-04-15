-- Empréstimo 1: Parcelado comum
INSERT INTO emprestimos (
    cliente_id,
    tipo_de_cobranca,
    valor_emprestado,
    parcelas,
    valor_parcela,
    juros_percentual,
    data_inicio,
    json_parcelas,
    configuracao,
    observacoes
) VALUES (
    2, -- Maxxxxxxxxi Soluções Ltda
    'parcelada_comum',
    1000.00,
    12,
    100.00,
    5.00,
    '2024-03-01',
    '[
        {"parcela": 1, "data": "01/03/2024", "valor": "100.00", "paga": true, "valor_pago": "100.00", "data_pagamento": "2024-03-01", "forma_pagamento": "pix"},
        {"parcela": 2, "data": "01/04/2024", "valor": "100.00", "paga": true, "valor_pago": "100.00", "data_pagamento": "2024-04-01", "forma_pagamento": "pix"},
        {"parcela": 3, "data": "01/05/2024", "valor": "100.00", "paga": true, "valor_pago": "100.00", "data_pagamento": "2024-05-01", "forma_pagamento": "pix"},
        {"parcela": 4, "data": "01/06/2024", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 5, "data": "01/07/2024", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 6, "data": "01/08/2024", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 7, "data": "01/09/2024", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 8, "data": "01/10/2024", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 9, "data": "01/11/2024", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 10, "data": "01/12/2024", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 11, "data": "01/01/2025", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 12, "data": "01/02/2025", "valor": "100.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null}
    ]',
    '{
        "usar_tlc": false,
        "tlc_valor": 0.00,
        "modo_calculo": "parcela",
        "periodo_pagamento": "mensal",
        "dias_semana": ["sabado", "domingo"],
        "considerar_feriados": true
    }',
    'Empréstimo parcelado comum com 12 parcelas mensais'
);

-- Empréstimo 2: Reparcelado com juros
INSERT INTO emprestimos (
    cliente_id,
    tipo_de_cobranca,
    valor_emprestado,
    parcelas,
    valor_parcela,
    juros_percentual,
    data_inicio,
    json_parcelas,
    configuracao,
    observacoes
) VALUES (
    8, -- Mulher Maravilhinha
    'reparcelada_com_juros',
    2000.00,
    24,
    120.00,
    8.00,
    '2024-02-15',
    '[
        {"parcela": 1, "data": "15/02/2024", "valor": "120.00", "paga": true, "valor_pago": "120.00", "data_pagamento": "2024-02-15", "forma_pagamento": "pix"},
        {"parcela": 2, "data": "15/03/2024", "valor": "120.00", "paga": true, "valor_pago": "120.00", "data_pagamento": "2024-03-15", "forma_pagamento": "pix"},
        {"parcela": 3, "data": "15/04/2024", "valor": "120.00", "paga": true, "valor_pago": "120.00", "data_pagamento": "2024-04-15", "forma_pagamento": "pix"},
        {"parcela": 4, "data": "15/05/2024", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 5, "data": "15/06/2024", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 6, "data": "15/07/2024", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 7, "data": "15/08/2024", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 8, "data": "15/09/2024", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 9, "data": "15/10/2024", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 10, "data": "15/11/2024", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 11, "data": "15/12/2024", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 12, "data": "15/01/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 13, "data": "15/02/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 14, "data": "15/03/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 15, "data": "15/04/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 16, "data": "15/05/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 17, "data": "15/06/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 18, "data": "15/07/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 19, "data": "15/08/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 20, "data": "15/09/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 21, "data": "15/10/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 22, "data": "15/11/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 23, "data": "15/12/2025", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 24, "data": "15/01/2026", "valor": "120.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null}
    ]',
    '{
        "usar_tlc": true,
        "tlc_valor": 200.00,
        "modo_calculo": "taxa",
        "periodo_pagamento": "quinzenal",
        "dias_semana": ["domingo"],
        "considerar_feriados": true
    }',
    'Empréstimo reparcelado com juros mais altos e TLC'
);

-- Empréstimo 3: Parcelado comum com TLC
INSERT INTO emprestimos (
    cliente_id,
    tipo_de_cobranca,
    valor_emprestado,
    parcelas,
    valor_parcela,
    juros_percentual,
    data_inicio,
    json_parcelas,
    configuracao,
    observacoes
) VALUES (
    10, -- Chico Buarque
    'parcelada_comum',
    5000.00,
    36,
    200.00,
    6.50,
    '2024-01-01',
    '[
        {"parcela": 1, "data": "01/01/2024", "valor": "200.00", "paga": true, "valor_pago": "200.00", "data_pagamento": "2024-01-01", "forma_pagamento": "pix"},
        {"parcela": 2, "data": "01/02/2024", "valor": "200.00", "paga": true, "valor_pago": "200.00", "data_pagamento": "2024-02-01", "forma_pagamento": "pix"},
        {"parcela": 3, "data": "01/03/2024", "valor": "200.00", "paga": true, "valor_pago": "200.00", "data_pagamento": "2024-03-01", "forma_pagamento": "pix"},
        {"parcela": 4, "data": "01/04/2024", "valor": "200.00", "paga": true, "valor_pago": "200.00", "data_pagamento": "2024-04-01", "forma_pagamento": "pix"},
        {"parcela": 5, "data": "01/05/2024", "valor": "200.00", "paga": true, "valor_pago": "200.00", "data_pagamento": "2024-05-01", "forma_pagamento": "pix"},
        {"parcela": 6, "data": "01/06/2024", "valor": "200.00", "paga": true, "valor_pago": "200.00", "data_pagamento": "2024-06-01", "forma_pagamento": "pix"},
        {"parcela": 7, "data": "01/07/2024", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 8, "data": "01/08/2024", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 9, "data": "01/09/2024", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 10, "data": "01/10/2024", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 11, "data": "01/11/2024", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 12, "data": "01/12/2024", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 13, "data": "01/01/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 14, "data": "01/02/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 15, "data": "01/03/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 16, "data": "01/04/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 17, "data": "01/05/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 18, "data": "01/06/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 19, "data": "01/07/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 20, "data": "01/08/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 21, "data": "01/09/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 22, "data": "01/10/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 23, "data": "01/11/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 24, "data": "01/12/2025", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 25, "data": "01/01/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 26, "data": "01/02/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 27, "data": "01/03/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 28, "data": "01/04/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 29, "data": "01/05/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 30, "data": "01/06/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 31, "data": "01/07/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 32, "data": "01/08/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 33, "data": "01/09/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 34, "data": "01/10/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 35, "data": "01/11/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null},
        {"parcela": 36, "data": "01/12/2026", "valor": "200.00", "paga": false, "valor_pago": null, "data_pagamento": null, "forma_pagamento": null}
    ]',
    '{
        "usar_tlc": true,
        "tlc_valor": 500.00,
        "modo_calculo": "parcela",
        "periodo_pagamento": "mensal",
        "dias_semana": ["sabado", "domingo"],
        "considerar_feriados": true
    }',
    'Empréstimo parcelado comum com TLC e 36 parcelas mensais'
); 