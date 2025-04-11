<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistema_emprestimos";

$conn = mysqli_connect($host, $usuario, $senha, $banco);

if (!$conn) {
    error_log("Erro ao conectar no MySQL: " . mysqli_connect_error());
    die("Erro interno. Tente novamente mais tarde.");
}
