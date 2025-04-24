<?php
// Configurações de ambiente
define('ENVIRONMENT', 'development'); // development, production
define('DEBUG_MODE', true);

// Configurações de banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_emprestimos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações de sessão
define('SESSION_NAME', 'SISTEMA_EMPRESTIMOS');
define('SESSION_LIFETIME', 7200); // 2 horas em segundos
define('SESSION_PATH', '');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false);
define('SESSION_HTTPONLY', true);

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');

// Caminho base da URL do sistema (ajuste conforme seu ambiente)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_path = '';
define('BASE_URL', $protocol . $host . $base_path);

// Configurações de upload
define('UPLOAD_MAX_SIZE', 5242880); // 5MB em bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Configurações de email
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@example.com');
define('SMTP_FROM_NAME', 'Sistema de Empréstimos');

// Configurações de segurança
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL_CHAR', true);

// Configurações de cache
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hora em segundos
define('CACHE_DIR', __DIR__ . '/cache/');
