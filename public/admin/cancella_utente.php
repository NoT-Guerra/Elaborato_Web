<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Controlla se l'ID utente è presente e numerico
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    die('ID utente non valido');
}

$userId = (int) $_POST['user_id'];

// Elimina l'utente
$stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: index.php?msg=user_deleted');
    exit;
} else {
    die('Errore durante l\'eliminazione dell\'utente');
}
