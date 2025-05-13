<?php
// Conectar ao banco de dados
$conn = new mysqli('localhost', 'root', '', 'sistema_emprestimos');

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// SQL para criar tabela
$sql = "CREATE TABLE IF NOT EXISTS solicitacoes_saque (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    usuario_id INT NOT NULL, 
    conta_id INT NOT NULL, 
    valor DECIMAL(10,2) NOT NULL, 
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente', 
    descricao TEXT, 
    data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP, 
    data_processamento DATETIME, 
    observacao_admin TEXT, 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id), 
    FOREIGN KEY (conta_id) REFERENCES contas(id)
)";

// Executar a query
if ($conn->query($sql) === TRUE) {
    echo "Tabela 'solicitacoes_saque' criada com sucesso!";
} else {
    echo "Erro ao criar tabela: " . $conn->error;
}

$conn->close();
?> 