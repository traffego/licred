<?php
$titulo_pagina = "Templates de Mensagens";
$scripts_header = '
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

<!-- DataTables JavaScript -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
';

require_once '../config.php';
require_once '../includes/conexao.php';
require_once '../includes/autenticacao.php';

// Verifica se é uma requisição AJAX
if (isset($_REQUEST['acao'])) {
    header('Content-Type: application/json');
    $acao = $_REQUEST['acao'] ?? '';
    $response = ['success' => false, 'message' => 'Ação inválida'];

    switch ($acao) {
        case 'listar':
            $sql = "SELECT * FROM templates_mensagens WHERE ativo = 1 ORDER BY status, nome";
            $result = $conn->query($sql);
            
            $templates = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $templates[] = $row;
                }
                $response = ['success' => true, 'data' => $templates];
            } else {
                $response = ['success' => false, 'message' => 'Erro ao buscar templates: ' . $conn->error];
            }
            break;

        case 'obter':
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $sql = "SELECT * FROM templates_mensagens WHERE id = ? AND ativo = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $row = $result->fetch_assoc()) {
                    $response = ['success' => true, 'data' => $row];
                } else {
                    $response = ['success' => false, 'message' => 'Template não encontrado'];
                }
            } else {
                $response = ['success' => false, 'message' => 'ID inválido'];
            }
            break;

        case 'salvar':
            $id = (int)($_REQUEST['id'] ?? 0);
            $dados = [
                'nome' => $_REQUEST['nome'] ?? '',
                'status' => $_REQUEST['status'] ?? '',
                'mensagem' => $_REQUEST['mensagem'] ?? '',
                'incluir_nome' => (int)($_REQUEST['incluir_nome'] ?? 0),
                'incluir_valor' => (int)($_REQUEST['incluir_valor'] ?? 0),
                'incluir_vencimento' => (int)($_REQUEST['incluir_vencimento'] ?? 0),
                'incluir_atraso' => (int)($_REQUEST['incluir_atraso'] ?? 0),
                'incluir_valor_total' => (int)($_REQUEST['incluir_valor_total'] ?? 0),
                'incluir_valor_em_aberto' => (int)($_REQUEST['incluir_valor_em_aberto'] ?? 0),
                'incluir_total_parcelas' => (int)($_REQUEST['incluir_total_parcelas'] ?? 0),
                'incluir_parcelas_pagas' => (int)($_REQUEST['incluir_parcelas_pagas'] ?? 0),
                'incluir_valor_pago' => (int)($_REQUEST['incluir_valor_pago'] ?? 0),
                'incluir_numero_parcela' => (int)($_REQUEST['incluir_numero_parcela'] ?? 0),
                'incluir_lista_parcelas' => (int)($_REQUEST['incluir_lista_parcelas'] ?? 0),
                'incluir_link_pagamento' => (int)($_REQUEST['incluir_link_pagamento'] ?? 0),
                'usuario_id' => $_SESSION['usuario_id']
            ];

            if (empty($dados['nome']) || empty($dados['status']) || empty($dados['mensagem'])) {
                $response = ['success' => false, 'message' => 'Preencha todos os campos obrigatórios'];
                break;
            }

            if ($id > 0) {
                // Atualização
                $sql = "UPDATE templates_mensagens SET 
                    nome = ?, status = ?, mensagem = ?,
                    incluir_nome = ?, incluir_valor = ?, incluir_vencimento = ?, incluir_atraso = ?,
                    incluir_valor_total = ?, incluir_valor_em_aberto = ?, incluir_total_parcelas = ?,
                    incluir_parcelas_pagas = ?, incluir_valor_pago = ?, incluir_numero_parcela = ?,
                    incluir_lista_parcelas = ?, incluir_link_pagamento = ?
                    WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssiiiiiiiiiiiiii",
                    $dados['nome'], $dados['status'], $dados['mensagem'],
                    $dados['incluir_nome'], $dados['incluir_valor'], $dados['incluir_vencimento'],
                    $dados['incluir_atraso'], $dados['incluir_valor_total'], $dados['incluir_valor_em_aberto'],
                    $dados['incluir_total_parcelas'], $dados['incluir_parcelas_pagas'], $dados['incluir_valor_pago'],
                    $dados['incluir_numero_parcela'], $dados['incluir_lista_parcelas'], $dados['incluir_link_pagamento'],
                    $id
                );
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Template atualizado com sucesso'];
                } else {
                    $response = ['success' => false, 'message' => 'Erro ao atualizar template'];
                }
            } else {
                // Inserção
                $sql = "INSERT INTO templates_mensagens (
                    nome, status, mensagem,
                    incluir_nome, incluir_valor, incluir_vencimento, incluir_atraso,
                    incluir_valor_total, incluir_valor_em_aberto, incluir_total_parcelas,
                    incluir_parcelas_pagas, incluir_valor_pago, incluir_numero_parcela,
                    incluir_lista_parcelas, incluir_link_pagamento, usuario_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssiiiiiiiiiiiii",
                    $dados['nome'], $dados['status'], $dados['mensagem'],
                    $dados['incluir_nome'], $dados['incluir_valor'], $dados['incluir_vencimento'],
                    $dados['incluir_atraso'], $dados['incluir_valor_total'], $dados['incluir_valor_em_aberto'],
                    $dados['incluir_total_parcelas'], $dados['incluir_parcelas_pagas'], $dados['incluir_valor_pago'],
                    $dados['incluir_numero_parcela'], $dados['incluir_lista_parcelas'], $dados['incluir_link_pagamento'],
                    $dados['usuario_id']
                );
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Template criado com sucesso'];
                } else {
                    $response = ['success' => false, 'message' => 'Erro ao criar template'];
                }
            }
            break;

        case 'excluir':
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $sql = "UPDATE templates_mensagens SET ativo = 0 WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Template excluído com sucesso'];
                } else {
                    $response = ['success' => false, 'message' => 'Erro ao excluir template'];
                }
            } else {
                $response = ['success' => false, 'message' => 'ID inválido'];
            }
            break;

        case 'processar':
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $sql = "SELECT * FROM templates_mensagens WHERE id = ? AND ativo = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $template = $result->fetch_assoc()) {
                    $mensagem = $template['mensagem'];
                    
                    // Dados de exemplo para preview
                    $dados = [
                        'nome_cliente' => 'João da Silva',
                        'valor_parcela' => 'R$ 1.000,00',
                        'data_vencimento' => '10/05/2024',
                        'atraso' => '5 dias',
                        'valor_total' => 'R$ 12.000,00',
                        'valor_em_aberto' => 'R$ 8.000,00',
                        'total_parcelas' => '12',
                        'parcelas_pagas' => '4',
                        'valor_pago' => 'R$ 4.000,00',
                        'numero_parcela' => '5',
                        'lista_parcelas_restantes' => '5, 6, 7, 8, 9, 10, 11, 12',
                        'link_pagamento' => 'https://exemplo.com/pagar',
                        'nomedogestor' => $_SESSION['nome'] ?? 'Gestor'
                    ];
                    
                    foreach ($dados as $chave => $valor) {
                        $mensagem = str_replace('{' . $chave . '}', $valor, $mensagem);
                    }
                    
                    $response = ['success' => true, 'data' => $mensagem];
                } else {
                    $response = ['success' => false, 'message' => 'Template não encontrado'];
                }
            } else {
                $response = ['success' => false, 'message' => 'ID inválido'];
            }
            break;
    }

    echo json_encode($response);
    exit;
}

// Se não for AJAX, renderiza a página
require_once '../includes/head.php';
?>

<div class="container py-4">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Templates de Mensagens</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTemplate">
            <i class="fas fa-plus"></i> Novo Template
        </button>
    </div>

    <div class="row g-4" id="containerTemplates">
        <!-- Os templates serão inseridos aqui -->
    </div>
</div>

<style>
    body {
        background: linear-gradient(135deg, #2c3e50 0%, #1a1a1a 100%);
        min-height: 100vh;
    }

    .page-header {
        background: #fff;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .page-header h1 {
        color: #344767;
        margin: 0;
        font-size: 1.8rem;
        font-weight: 600;
    }

    .page-header .btn {
        background: #0d6efd;
        border: none;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .page-header .btn:hover {
        background: #0b5ed7;
        transform: translateY(-2px);
    }

    .card-template {
        background: #fff;
        transition: all 0.3s ease;
        border: 0;
        margin-bottom: 20px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border-radius: 0.75rem;
        overflow: hidden;
        height: 400px;
        display: flex;
        flex-direction: column;
    }

    .card-template:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .card-template .card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        flex-shrink: 0;
    }

    .card-template .card-title {
        color: #344767;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0;
    }

    .status-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 10;
        font-size: 0.8rem;
        padding: 0.5em 1em;
        border-radius: 30px;
    }

    .status-pendente { 
        background-color: #ffc107;
        color: #000;
    }

    .status-atrasado { 
        background-color: #dc3545;
        color: #fff;
    }

    .status-quitado { 
        background-color: #198754;
        color: #fff;
    }

    .template-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 1rem 1.5rem;
    }

    .template-tag {
        font-size: 0.75rem;
        padding: 0.25em 0.75em;
        border-radius: 20px;
        background-color: #e9ecef;
        color: #495057;
    }

    .card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .card-title {
        color: #344767;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0;
    }

    .btn-actions {
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: flex-end;
        border-top: 1px solid #e9ecef;
    }

    .btn-editar {
        background-color: #11cdef;
        border-color: #11cdef;
        color: #fff;
    }

    .btn-editar:hover {
        background-color: #0fb5d4;
        border-color: #0fb5d4;
        color: #fff;
    }

    .btn-excluir {
        background-color: #f5365c;
        border-color: #f5365c;
    }

    .btn-excluir:hover {
        background-color: #f01d48;
        border-color: #f01d48;
    }

    .modal-content {
        background: #2c3e50;
        color: #fff;
    }

    .modal-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modal-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .form-control, .form-select {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .form-control:focus, .form-select:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: #0d6efd;
        color: #fff;
    }

    .form-label {
        color: #fff;
    }

    .checkbox-tag {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .checkbox-tag.checked {
        background: #0d6efd;
    }

    .checkbox-tag.todos {
        background: #198754;
    }

    .checkbox-tag.todos.checked {
        background-color: #dc3545;
    }

    .card-template .template-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        flex: 1;
        align-content: flex-start;
        overflow-y: auto;
    }

    .card-template .template-tag {
        font-size: 0.75rem;
        padding: 0.25em 0.75em;
        border-radius: 20px;
        background-color: #e9ecef;
        color: #495057;
        height: fit-content;
        white-space: nowrap;
    }

    .card-template .btn-actions {
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: flex-end;
        border-top: 1px solid #e9ecef;
        flex-shrink: 0;
    }

    .whatsapp-container {
        display: flex;
        flex-direction: column;
        background-color: #e5ddd5;
        background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAE7SURBVEhLY2QAGT/78TczM7OG8fHxZ9++fbN+//7969OnT+//+PHjAYgPAvj4+MKgWu8A2UeB+BxQ/SWgHfVA/hwQRoHkgwD3W1tb9crKyr8JCQmDGFBBOtDCb0CfQggIDwRpaWk/X7x4sQXqBBQAFBgFdPCHsbGx/0CaCFTRPKCcOpCpgCxAiisDE2MXUDhJAai4G7I80OAKoA1VQA+f+//vv8nfv3/LgOoigfgAEOcDLfkINPQb0CCwgf8Y/zsCcTcQbwLi/UC8CKbw///wf4Acwv///42A8ktB4v////sPNOQfSAyuEegJcADkCoWEBLQAKB8L9Fy5goLCeqBzDjAxMSVeIPzfBArV/y7/obh6oBOYCGkEOrQCSG0C0ieB+DkDI6MpyGXYANBVGkCqAYiZ8EkOVgAAdxuJC6HnMpYAAAAASUVORK5CYII=");
        background-repeat: repeat;
        position: relative;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .whatsapp-header {
        background-color: #128C7E;
        color: white;
        padding: 10px 15px;
        display: flex;
        align-items: center;
    }

    .whatsapp-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #ddd;
        margin-right: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #128C7E;
        background-color: white;
    }

    .whatsapp-contact-info {
        display: flex;
        flex-direction: column;
    }

    .whatsapp-contact-name {
        font-weight: bold;
        font-size: 1rem;
    }

    .whatsapp-contact-status {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .whatsapp-header-actions {
        margin-left: auto;
        display: flex;
        gap: 15px;
        color: white;
    }

    .whatsapp-header-actions i {
        font-size: 1.2rem;
    }

    .whatsapp-chat {
        flex: 1;
        padding: 15px;
        display: flex;
        flex-direction: column;
        min-height: 200px;
    }

    .whatsapp-message {
        max-width: 80%;
        background-color: #DCF8C6;
        border-radius: 10px;
        padding: 12px 15px;
        margin-bottom: 5px;
        position: relative;
        align-self: flex-end;
        font-size: 0.9rem;
        line-height: 1.5;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        white-space: pre-line;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .whatsapp-message::after {
        content: '';
        position: absolute;
        top: 0;
        right: -8px;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 0 0 10px 10px;
        border-color: transparent transparent transparent #DCF8C6;
    }

    .whatsapp-message-time {
        font-size: 0.65rem;
        color: #999;
        float: right;
        margin-top: 2px;
        margin-left: 8px;
    }

    .whatsapp-message-status {
        display: inline-block;
        margin-left: 4px;
        color: #4FC3F7;
    }

    .whatsapp-footer {
        padding: 10px;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
    }

    .whatsapp-input {
        flex: 1;
        background-color: white;
        border-radius: 20px;
        padding: 8px 15px;
        margin: 0 10px;
        display: flex;
        align-items: center;
    }

    .whatsapp-input i {
        color: #777;
        margin-right: 10px;
    }

    .whatsapp-input-placeholder {
        color: #999;
        font-size: 0.9rem;
    }

    .whatsapp-send {
        width: 40px;
        height: 40px;
        background-color: #128C7E;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .mensagem-container,
    .informacoes-container {
        display: block !important;
    }

    .btn-editar-mensagem {
        display: none;
    }

    .template-tag {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        margin: 0.25rem;
        border-radius: 0.25rem;
        background-color: #e9ecef;
        color: #495057;
        font-size: 0.875rem;
        border: 1px solid #dee2e6;
        opacity: 0.7;
    }

    .template-tag.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
        opacity: 1;
    }

    .template-tags {
        display: flex;
        flex-wrap: wrap;
        padding: 1rem;
        min-height: 120px;
        overflow-y: auto;
    }

    .checkbox-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .checkbox-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background-color: #e9ecef;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        user-select: none;
    }

    .checkbox-tag:hover {
        background-color: #dee2e6;
    }

    .checkbox-tag input[type="checkbox"] {
        display: none;
    }

    .checkbox-tag label {
        margin-bottom: 0;
        cursor: pointer;
        font-size: 0.9rem;
        padding: 0;
    }

    .checkbox-tag.checked {
        background-color: #0d6efd;
        color: white;
    }

    .checkbox-tag.checked:hover {
        background-color: #0b5ed7;
    }

    .checkbox-tag.todos {
        background-color: #198754;
        color: white;
        font-weight: 500;
    }

    .checkbox-tag.todos:hover {
        background-color: #157347;
    }

    .checkbox-tag.todos.checked {
        background-color: #dc3545;
    }

    .checkbox-tag.todos.checked:hover {
        background-color: #bb2d3b;
    }
</style>

<!-- Modal Template -->
<div class="modal fade" id="modalTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template de Mensagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formTemplate" action="salvar_template.php" method="POST">
                    <input type="hidden" id="template_id" name="id">
                    
                    <div class="form-group-row">
                        <div class="form-group">
                            <label for="nome" class="form-label">Nome do Template</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Selecione...</option>
                                <option value="pendente">Pendente</option>
                                <option value="atrasado">Atrasado</option>
                                <option value="quitado">Quitado</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-editar-mensagem">
                        <i class="fas fa-edit"></i> Editar Conteúdo da Mensagem
                    </button>
                    
                    <div class="whatsapp-container mb-3">
                        <div class="whatsapp-header">
                            <div class="whatsapp-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="whatsapp-contact-info">
                                <div class="whatsapp-contact-name">Cliente</div>
                                <div class="whatsapp-contact-status" id="previewStatus">online</div>
                            </div>
                            <div class="whatsapp-header-actions">
                                <i class="fas fa-video"></i>
                                <i class="fas fa-phone"></i>
                                <i class="fas fa-ellipsis-v"></i>
                            </div>
                        </div>
                        <div class="whatsapp-chat">
                            <div class="whatsapp-message" id="previewMensagem">
                                Digite uma mensagem para visualizar o preview...
                                <span class="whatsapp-message-time">12:00
                                    <i class="fas fa-check-double whatsapp-message-status"></i>
                                </span>
                            </div>
                        </div>
                        <div class="whatsapp-footer">
                            <i class="far fa-smile"></i>
                            <div class="whatsapp-input">
                                <i class="fas fa-paperclip"></i>
                                <span class="whatsapp-input-placeholder">Digite uma mensagem</span>
                            </div>
                            <div class="whatsapp-send">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mensagem-container">
                        <div class="mb-3">
                            <label for="mensagem" class="form-label">Mensagem</label>
                            <textarea class="form-control" id="mensagem" name="mensagem" rows="5" required></textarea>
                        </div>
                    </div>
                    
                    <div class="informacoes-container">
                        <div class="mb-3">
                            <label class="form-label">Informações a Incluir</label>
                            <div class="checkbox-tags">
                                <label class="checkbox-tag todos">
                                    <input type="checkbox" id="incluir_todos">
                                    <span>TODOS</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_nome" name="incluir_nome" value="1">
                                    <span>Nome do Cliente</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_valor" name="incluir_valor" value="1">
                                    <span>Valor da Parcela</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_vencimento" name="incluir_vencimento" value="1">
                                    <span>Data de Vencimento</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_atraso" name="incluir_atraso" value="1">
                                    <span>Dias de Atraso</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_valor_total" name="incluir_valor_total" value="1">
                                    <span>Valor Total</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_valor_em_aberto" name="incluir_valor_em_aberto" value="1">
                                    <span>Valor em Aberto</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_total_parcelas" name="incluir_total_parcelas" value="1">
                                    <span>Total de Parcelas</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_parcelas_pagas" name="incluir_parcelas_pagas" value="1">
                                    <span>Parcelas Pagas</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_valor_pago" name="incluir_valor_pago" value="1">
                                    <span>Valor Pago</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_numero_parcela" name="incluir_numero_parcela" value="1">
                                    <span>Número da Parcela</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_lista_parcelas" name="incluir_lista_parcelas" value="1">
                                    <span>Lista de Parcelas</span>
                                </label>
                                <label class="checkbox-tag">
                                    <input type="checkbox" id="incluir_link_pagamento" name="incluir_link_pagamento" value="1">
                                    <span>Link de Pagamento</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Tratamento das mensagens de retorno
    <?php if (isset($_GET['sucesso'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: 'Template salvo com sucesso!',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            // Remove os parâmetros da URL
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        let mensagem = '';
        switch ('<?php echo $_GET['erro']; ?>') {
            case 'campos_obrigatorios':
                mensagem = 'Por favor, preencha todos os campos obrigatórios.';
                break;
            case 'status_invalido':
                mensagem = 'O status selecionado é inválido.';
                break;
            case 'erro_sql':
                mensagem = 'Erro ao salvar o template. Tente novamente.';
                break;
            case 'template_nao_encontrado':
                mensagem = 'Template não encontrado ou você não tem permissão para editá-lo.';
                break;
            case 'metodo_invalido':
                mensagem = 'Método de requisição inválido.';
                break;
            default:
                mensagem = 'Ocorreu um erro ao processar sua solicitação.';
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: mensagem
        }).then(() => {
            // Remove os parâmetros da URL
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    <?php endif; ?>

    // Função para verificar se todas as tags estão marcadas
    function verificarTodasTagsMarcadas() {
        const totalTags = $('.checkbox-tag:not(.todos)').length;
        const checkedTags = $('.checkbox-tag:not(.todos) input[type="checkbox"]:checked').length;
        const todosTag = $('.checkbox-tag.todos');
        
        if (totalTags === checkedTags) {
            todosTag.addClass('checked');
            todosTag.find('input[type="checkbox"]').prop('checked', true);
        } else {
            todosTag.removeClass('checked');
            todosTag.find('input[type="checkbox"]').prop('checked', false);
        }
    }

    // Função para verificar quais tags estão sendo usadas na mensagem
    function verificarTagsUsadas(mensagem) {
        const tags = {
            'nome_cliente': 'incluir_nome',
            'valor_parcela': 'incluir_valor',
            'data_vencimento': 'incluir_vencimento',
            'atraso': 'incluir_atraso',
            'valor_total': 'incluir_valor_total',
            'valor_em_aberto': 'incluir_valor_em_aberto',
            'total_parcelas': 'incluir_total_parcelas',
            'parcelas_pagas': 'incluir_parcelas_pagas',
            'valor_pago': 'incluir_valor_pago',
            'numero_parcela': 'incluir_numero_parcela',
            'lista_parcelas_restantes': 'incluir_lista_parcelas',
            'link_pagamento': 'incluir_link_pagamento',
            'nomedogestor': 'incluir_nome_gestor'
        };

        // Desmarcar todas as checkboxes primeiro
        $('.checkbox-tag input[type="checkbox"]').prop('checked', false);
        $('.checkbox-tag').removeClass('checked');

        // Verificar cada tag na mensagem
        for (const [tag, checkboxId] of Object.entries(tags)) {
            if (mensagem.includes('{' + tag + '}')) {
                $(`#${checkboxId}`).prop('checked', true);
                $(`#${checkboxId}`).closest('.checkbox-tag').addClass('checked');
            }
        }

        // Verificar estado do botão TODOS
        verificarTodasTagsMarcadas();
    }

    // Função para obter as tags usadas em uma mensagem
    function obterTagsUsadas(mensagem) {
        const tags = {
            'nome_cliente': 'Nome do Cliente',
            'valor_parcela': 'Valor da Parcela',
            'data_vencimento': 'Data de Vencimento',
            'atraso': 'Dias de Atraso',
            'valor_total': 'Valor Total',
            'valor_em_aberto': 'Valor em Aberto',
            'total_parcelas': 'Total de Parcelas',
            'parcelas_pagas': 'Parcelas Pagas',
            'valor_pago': 'Valor Pago',
            'numero_parcela': 'Número da Parcela',
            'lista_parcelas_restantes': 'Lista de Parcelas',
            'link_pagamento': 'Link de Pagamento',
            'nomedogestor': 'Nome do Gestor'
        };
        
        const tagsUsadas = [];
        for (const [tag, label] of Object.entries(tags)) {
            if (mensagem.includes('{' + tag + '}')) {
                tagsUsadas.push(label);
            }
        }
        return tagsUsadas;
    }

    // Função para formatar a hora atual
    function getHoraAtual() {
        const agora = new Date();
        return agora.getHours() + ':' + (agora.getMinutes() < 10 ? '0' : '') + agora.getMinutes();
    }

    // Função para carregar os templates
    function carregarTemplates() {
        $.post('templates_mensagens.php', {
            acao: 'listar'
        }, function(response) {
            if (response.success) {
                var html = '';
                response.data.forEach(function(template) {
                    var statusClass = 'status-' + template.status;
                    var statusText = {
                        'pendente': 'Pendente',
                        'atrasado': 'Atrasado',
                        'quitado': 'Quitado'
                    }[template.status] || template.status;

                    // Obter tags usadas na mensagem
                    var tagsUsadas = obterTagsUsadas(template.mensagem);
                    
                    // Lista de todas as tags possíveis
                    var todasTags = [
                        { id: 'incluir_nome', label: 'Nome do Cliente' },
                        { id: 'incluir_valor', label: 'Valor da Parcela' },
                        { id: 'incluir_vencimento', label: 'Data de Vencimento' },
                        { id: 'incluir_atraso', label: 'Dias de Atraso' },
                        { id: 'incluir_valor_total', label: 'Valor Total' },
                        { id: 'incluir_valor_em_aberto', label: 'Valor em Aberto' },
                        { id: 'incluir_total_parcelas', label: 'Total de Parcelas' },
                        { id: 'incluir_parcelas_pagas', label: 'Parcelas Pagas' },
                        { id: 'incluir_valor_pago', label: 'Valor Pago' },
                        { id: 'incluir_numero_parcela', label: 'Número da Parcela' },
                        { id: 'incluir_lista_parcelas', label: 'Lista de Parcelas' },
                        { id: 'incluir_link_pagamento', label: 'Link de Pagamento' }
                    ];

                    // Gerar HTML para todas as tags
                    var tagsHtml = todasTags.map(tag => {
                        const isAtiva = tagsUsadas.includes(tag.label);
                        return `<span class="template-tag ${isAtiva ? 'active' : ''}">${tag.label}</span>`;
                    }).join('');

                    html += `
                        <div class="col-md-4">
                            <div class="card card-template">
                                <div class="card-header">
                                    <span class="badge ${statusClass} status-badge">${statusText}</span>
                                    <h5 class="card-title">${template.nome}</h5>
                                </div>
                                
                                <div class="template-tags">
                                    ${tagsHtml}
                                </div>
                                
                                <div class="btn-actions">
                                    <button class="btn btn-sm btn-editar me-2" data-id="${template.id}">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-excluir" data-id="${template.id}">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#containerTemplates').html(html);
            } else {
                alert(response.message || 'Erro ao carregar templates');
            }
        });
    }

    // Carregar templates ao iniciar
    carregarTemplates();
    
    // Editar template
    $('#containerTemplates').on('click', '.btn-editar', function() {
        var id = $(this).data('id');
        $.post('templates_mensagens.php', {
            acao: 'obter',
            id: id
        }, function(response) {
            if (response.success) {
                var template = response.data;
                $('#template_id').val(template.id);
                $('#nome').val(template.nome);
                $('#status').val(template.status);
                $('#mensagem').val(template.mensagem);
                
                // Marcar os checkboxes baseado nos dados do template
                $('#incluir_nome').prop('checked', template.incluir_nome == 1);
                $('#incluir_valor').prop('checked', template.incluir_valor == 1);
                $('#incluir_vencimento').prop('checked', template.incluir_vencimento == 1);
                $('#incluir_atraso').prop('checked', template.incluir_atraso == 1);
                $('#incluir_valor_total').prop('checked', template.incluir_valor_total == 1);
                $('#incluir_valor_em_aberto').prop('checked', template.incluir_valor_em_aberto == 1);
                $('#incluir_total_parcelas').prop('checked', template.incluir_total_parcelas == 1);
                $('#incluir_parcelas_pagas').prop('checked', template.incluir_parcelas_pagas == 1);
                $('#incluir_valor_pago').prop('checked', template.incluir_valor_pago == 1);
                $('#incluir_numero_parcela').prop('checked', template.incluir_numero_parcela == 1);
                $('#incluir_lista_parcelas').prop('checked', template.incluir_lista_parcelas == 1);
                $('#incluir_link_pagamento').prop('checked', template.incluir_link_pagamento == 1);
                
                // Atualizar classes das tags
                $('.checkbox-tag').each(function() {
                    const checkbox = $(this).find('input[type="checkbox"]');
                    $(this).toggleClass('checked', checkbox.prop('checked'));
                });
                
                // Mostrar containers e atualizar botão
                $('.mensagem-container, .informacoes-container').addClass('show');
                $('.btn-editar-mensagem').html('<i class="fas fa-edit"></i> Ocultar Conteúdo');
                
                atualizarPreview();
                $('#modalTemplate').modal('show');
            }
        });
    });
    
    // Excluir template
    $('#containerTemplates').on('click', '.btn-excluir', function() {
        var id = $(this).data('id');
        if (confirm('Tem certeza que deseja excluir este template?')) {
            $.post('templates_mensagens.php', {
                acao: 'excluir',
                id: id
            }, function(response) {
                if (response.success) {
                    carregarTemplates();
                }
                alert(response.message);
            });
        }
    });
    
    // Atualizar checkboxes antes do envio do formulário
    $('#formTemplate').on('submit', function() {
        // Adiciona os checkboxes não marcados como 0
        $('.checkbox-tag:not(.todos) input[type="checkbox"]').each(function() {
            if (!$(this).is(':checked')) {
                $(this).val('0');
            } else {
                $(this).val('1');
            }
        });
    });
    
    // Adicionar evento de clique nas tags
    $('.checkbox-tag').on('click', function() {
        const checkbox = $(this).find('input[type="checkbox"]');
        const isChecked = !checkbox.prop('checked'); // Inverte o estado atual
        checkbox.prop('checked', isChecked);
        $(this).toggleClass('checked', isChecked);
        atualizarPreview();
    });
    
    // Função para atualizar o preview no modal
    function atualizarPreview() {
        var mensagem = $('#mensagem').val();
        var status = $('#status').val();
        
        // Atualizar status no preview
        var statusText = {
            'pendente': 'online',
            'atrasado': 'digitando...',
            'quitado': 'visto por último hoje'
        }[status] || 'online';
        $('#previewStatus').text(statusText);
        
        // Dados de exemplo para preview
        var dados = {
            'nome_cliente': 'João da Silva',
            'valor_parcela': 'R$ 1.000,00',
            'data_vencimento': '10/05/2024',
            'atraso': '5 dias',
            'valor_total': 'R$ 12.000,00',
            'valor_em_aberto': 'R$ 8.000,00',
            'total_parcelas': '12',
            'parcelas_pagas': '4',
            'valor_pago': 'R$ 4.000,00',
            'numero_parcela': '5',
            'lista_parcelas_restantes': '5, 6, 7, 8, 9, 10, 11, 12',
            'link_pagamento': 'https://exemplo.com/pagar',
            'nomedogestor': 'Gestor'
        };
        
        // Mapeamento dos checkboxes para as tags
        var mapeamento = {
            'incluir_nome': 'nome_cliente',
            'incluir_valor': 'valor_parcela',
            'incluir_vencimento': 'data_vencimento',
            'incluir_atraso': 'atraso',
            'incluir_valor_total': 'valor_total',
            'incluir_valor_em_aberto': 'valor_em_aberto',
            'incluir_total_parcelas': 'total_parcelas',
            'incluir_parcelas_pagas': 'parcelas_pagas',
            'incluir_valor_pago': 'valor_pago',
            'incluir_numero_parcela': 'numero_parcela',
            'incluir_lista_parcelas': 'lista_parcelas_restantes',
            'incluir_link_pagamento': 'link_pagamento'
        };
        
        // Substituir as tags na mensagem baseado nos checkboxes selecionados
        for (var checkbox in mapeamento) {
            if ($('#' + checkbox).is(':checked')) {
                var tag = mapeamento[checkbox];
                var regex = new RegExp('{' + tag + '}', 'g');
                mensagem = mensagem.replace(regex, dados[tag]);
            } else {
                // Remover a tag se o checkbox não estiver marcado
                var tag = mapeamento[checkbox];
                var regex = new RegExp('{' + tag + '}', 'g');
                mensagem = mensagem.replace(regex, '');
            }
        }
        
        // Atualizar mensagem no preview
        var mensagemFormatada = mensagem.replace(/\n/g, '<br>');
        var horaAtual = getHoraAtual();
        
        $('#previewMensagem').html(`
            ${mensagemFormatada}
            <span class="whatsapp-message-time">${horaAtual}
                <i class="fas fa-check-double whatsapp-message-status"></i>
            </span>
        `);
    }
    
    // Atualizar preview quando a mensagem mudar
    $('#mensagem').on('input', function() {
        verificarTagsUsadas($(this).val());
        atualizarPreview();
    });
    
    // Limpar formulário e resetar estado ao abrir modal
    $('#modalTemplate').on('show.bs.modal', function() {
        // Resetar texto do botão
        $('.btn-editar-mensagem').html('<i class="fas fa-edit"></i> Editar Conteúdo da Mensagem');
        
        if (!$('#template_id').val()) {
            $('#formTemplate')[0].reset();
            
            // Definir modelo de mensagem básica
            var modeloMensagem = `Olá {nome_cliente},

Sua parcela de {valor_parcela} que vencia em {data_vencimento} está atrasada há {atraso}.

Valor total do empréstimo: {valor_total}
Valor em aberto: {valor_em_aberto}
Total de parcelas: {total_parcelas}
Parcelas pagas: {parcelas_pagas}
Valor já pago: {valor_pago}

Para regularizar sua situação, acesse: {link_pagamento}

Atenciosamente,
{nomedogestor}`;

            $('#mensagem').val(modeloMensagem);
            $('#previewMensagem').empty();
            
            // Desmarcar todas as checkboxes
            $('.checkbox-tag input[type="checkbox"]').prop('checked', false);
            $('.checkbox-tag').removeClass('checked');
            
            // Atualizar preview com o modelo básico
            atualizarPreview();
        }
    });

    // Remover o evento de clique que mostra/esconde o conteúdo
    $('.btn-editar-mensagem').off('click');
});
</script>

<?php require_once '../includes/footer.php'; ?>