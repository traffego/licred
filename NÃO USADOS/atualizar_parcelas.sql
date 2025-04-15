-- Script para atualizar o formato do JSON das parcelas
UPDATE emprestimos 
SET json_parcelas = (
    SELECT JSON_ARRAYAGG(
        JSON_OBJECT(
            'numero', p.numero,
            'vencimento', p.vencimento,
            'valor', p.valor,
            'status', p.status,
            'valor_pago', COALESCE(p.valor_pago, 0),
            'data_pagamento', p.data_pagamento,
            'forma_pagamento', p.forma_pagamento
        )
    )
    FROM JSON_TABLE(
        json_parcelas,
        '$[*]' COLUMNS (
            numero INT PATH '$.numero',
            vencimento DATE PATH '$.vencimento',
            valor DECIMAL(10,2) PATH '$.valor',
            status VARCHAR(20) PATH '$.status',
            valor_pago DECIMAL(10,2) PATH '$.valor_pago',
            data_pagamento DATE PATH '$.data_pagamento',
            forma_pagamento VARCHAR(50) PATH '$.forma_pagamento'
        )
    ) AS p
)
WHERE json_parcelas IS NOT NULL; 