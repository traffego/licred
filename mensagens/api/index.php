<?php
require_once 'vendor/autoload.php';

use Menuia\Settings;
use Menuia\Device;
use Menuia\Message;

// Configurar as informações da API
$endpoint = "https://chatbot.menuia.com";
$authkey = "vgkyTWyV3eMKdN6t2ErF2ky5Zco2MKKghdF7HppPfA1YKp8dvB";
$appkey = "d563230a-7727-4a4d-8d48-69c0df5c2b87";

// Configurar a API com as credenciais definidas
Settings::setEndpoint($endpoint);
Settings::setAuthkey($authkey);
Settings::setAppkey($appkey);

// Inicializar mensagens de status
$status_message = "";

// Processar envio de mensagem
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "send_message") {
    try {
        $phones = $_POST["phones"] ?? "";
        $message_text = $_POST["message_text"] ?? "";
        $message_type = $_POST["message_type"] ?? "text";
        $file_url = $_POST["file_url"] ?? "";

        if (empty($phones) || empty($message_text)) {
            $status_message = "Telefones e mensagem são obrigatórios";
        } else {
            // Separar múltiplos números
            $phone_list = explode("\n", str_replace("\r", "", $phones));
            $phone_list = array_map('trim', $phone_list);
            $phone_list = array_filter($phone_list); // Remover linhas vazias
            
            $success_count = 0;
            $error_count = 0;
            $error_messages = [];
            
            foreach ($phone_list as $phone) {
                if (empty($phone)) continue;
                
                Message::$phone = $phone;
                Message::$message = $message_text;
                Message::$type = $message_type;
                
                if ($message_type == "media" && !empty($file_url)) {
                    Message::$file_url = $file_url;
                }

                $response = Message::send();
                if (isset($response->status) && $response->status == 200) {
                    $success_count++;
                } else {
                    $error_count++;
                    $error_message = "Erro ao enviar para {$phone}";
                    if (isset($response->message)) {
                        $error_message .= ": " . $response->message;
                    }
                    $error_messages[] = $error_message;
                }
                
                // Aguardar um curto intervalo entre os envios para evitar bloqueios
                usleep(300000); // 0.3 segundos
            }
            
            if ($success_count > 0) {
                $status_message = "Mensagem enviada com sucesso para {$success_count} número(s)";
                if ($error_count > 0) {
                    $status_message .= ", mas falhou para {$error_count} número(s).";
                } else {
                    $status_message .= "!";
                }
            } else if ($error_count > 0) {
                $status_message = "Falha ao enviar mensagens para todos os {$error_count} número(s).";
            }
            
            // Adicionar detalhes de erros se houver
            if (!empty($error_messages)) {
                $status_message .= "<ul class='mt-2 mb-0'>";
                foreach ($error_messages as $msg) {
                    $status_message .= "<li>{$msg}</li>";
                }
                $status_message .= "</ul>";
            }
        }
    } catch (Exception $e) {
        $status_message = "Erro: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio de Mensagens - Menuia WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-top: 1rem;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">Envio de Mensagens - Menuia WhatsApp</h1>
        
        <?php if (!empty($status_message)): ?>
            <div class="alert alert-info">
                <?php echo $status_message; ?>
                
                <?php if (isset($response) && !empty($response)): ?>
                <div class="debug-info mt-3">
                    <h5>Detalhes da Resposta:</h5>
                    <pre><?php print_r($response); ?></pre>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Envio de Mensagens</h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="phones" class="form-label">Números de Telefone</label>
                                <textarea class="form-control" id="phones" name="phones" rows="4" required placeholder="Digite os números de telefone separados por linha"></textarea>
                                <small class="text-muted">Formato: DDI+DDD+Número (ex: 5511999999999). Digite um número por linha para envio em massa.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message_text" class="form-label">Mensagem</label>
                                <textarea class="form-control" id="message_text" name="message_text" rows="4" required></textarea>
                                <small class="text-muted">A mesma mensagem será enviada para todos os números informados acima.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tipo de Mensagem</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="message_type" id="type_text" value="text" checked onchange="toggleFileInput()">
                                    <label class="form-check-label" for="type_text">
                                        Texto
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="message_type" id="type_media" value="media" onchange="toggleFileInput()">
                                    <label class="form-check-label" for="type_media">
                                        Mídia
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="file_url_container" style="display: none;">
                                <label for="file_url" class="form-label">URL do Arquivo</label>
                                <input type="url" class="form-control" id="file_url" name="file_url">
                                <small class="text-muted">Imagens, documentos, áudios ou vídeos</small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="action" value="send_message" class="btn btn-success">Enviar Mensagem</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4>Instruções</h4>
                    </div>
                    <div class="card-body">
                        <p>Esta interface permite enviar mensagens através da API Menuia para WhatsApp.</p>
                        <ol>
                            <li>Digite os números dos telefones de destino (um por linha):
                                <ul>
                                    <li>Formato: DDI+DDD+Número (ex: 5511999999999)</li>
                                    <li>Para enviar para múltiplos destinatários, coloque cada número em uma linha separada</li>
                                </ul>
                            </li>
                            <li>Escreva a mensagem que deseja enviar (a mesma será enviada para todos os números)</li>
                            <li>Selecione o tipo de mensagem (texto ou mídia)</li>
                            <li>Para mensagens de mídia, informe a URL completa do arquivo</li>
                            <li>Clique em "Enviar Mensagem"</li>
                        </ol>
                        <p><strong>Observação:</strong> Esta interface está utilizando suas credenciais Menuia pré-configuradas.</p>
                        
                        <div class="alert alert-warning mt-3">
                            <p><strong>Dica:</strong> Para envio em massa, considere:</p>
                            <ul>
                                <li>Um pequeno intervalo (0.3 segundos) é adicionado entre cada envio para evitar bloqueios</li>
                                <li>Envie para grupos menores de números para melhor performance</li>
                                <li>O WhatsApp pode limitar o número de mensagens enviadas em um curto período</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleFileInput() {
            const mediaType = document.getElementById('type_media').checked;
            const fileUrlContainer = document.getElementById('file_url_container');
            
            if (mediaType) {
                fileUrlContainer.style.display = 'block';
            } else {
                fileUrlContainer.style.display = 'none';
            }
        }
    </script>
</body>
</html> 