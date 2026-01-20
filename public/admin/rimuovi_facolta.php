<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

// Controlla se il campo faculty è presente
if (!isset($_POST['faculty']) || trim($_POST['faculty']) === '') {
    echo json_encode(['success' => false, 'error' => 'Nome della facoltà non valido']);
    exit;
}

$faculty = trim($_POST['faculty']);

// Elimina la facoltà
$stmt = $conn->prepare("DELETE FROM facolta WHERE nome_facolta = ?");
$stmt->bind_param("s", $faculty);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Errore durante l\'eliminazione della facoltà. Verificare se ci sono utenti o materie associate.']);
    exit;
}
