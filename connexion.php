<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mysqli = new mysqli("localhost", "root", "", "ecommerce_db");
if ($mysqli->connect_errno) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}
?>
