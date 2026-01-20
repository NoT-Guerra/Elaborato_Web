<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

// Controlla se il campo announcement_id è presente
if (!isset($_POST['announcement_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID annuncio mancante']);
    exit;
}

$announcement_id = intval($_POST['announcement_id']);

// Elimina l'annuncio
$stmt = $conn->prepare("DELETE FROM annuncio WHERE id_annuncio = ?");
$stmt->bind_param("i", $announcement_id);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Errore durante l\'eliminazione dell\'annuncio']);
    exit;
}
