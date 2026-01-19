<?php
session_start();
require_once '../config/database.php';

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Controlla se l'ID annuncio è presente e numerico
if (!isset($_POST['announcement_id']) || !is_numeric($_POST['announcement_id'])) {
    die('ID annuncio non valido');
}

$announcementId = (int) $_POST['announcement_id'];

// Elimina l'annuncio
$stmt = $conn->prepare("DELETE FROM annuncio WHERE id_annuncio = ?");
$stmt->bind_param("i", $announcementId);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: ../admin.php?msg=announcement_deleted');
    exit;
} else {
    die('Errore durante l\'eliminazione dell\'annuncio');
}
