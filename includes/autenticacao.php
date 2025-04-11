<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}