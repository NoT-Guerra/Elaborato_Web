<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die('Accesso negato');
}

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    die('ID non valido');
}

$userId = (int) $_POST['user_id'];

// blocca auto-eliminazione
if ($userId === $_SESSION['user_id']) {
    die('Non puoi eliminare te stesso');
}

$stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

header('Location: admin.php?deleted=1');
exit;
