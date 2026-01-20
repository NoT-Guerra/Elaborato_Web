<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

// Controlla se il campo user_id è presente
if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID utente mancante']);
    exit;
}

$user_id = intval($_POST['user_id']);

// Impedisci di eliminare se stessi
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Non puoi eliminare il tuo stesso account da qui']);
    exit;
}

// Elimina l'utente
$stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Errore durante l\'eliminazione dell\'utente']);
    exit;
}
