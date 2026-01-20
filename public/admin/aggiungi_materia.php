<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

// Controlla i parametri
if (!isset($_POST['subject']) || trim($_POST['subject']) === '' || !isset($_POST['faculty_id'])) {
    echo json_encode(['success' => false, 'error' => 'Dati mancanti']);
    exit;
}

$subject = trim($_POST['subject']);
$faculty_id = intval($_POST['faculty_id']);

// Controlla se la materia esiste già per questa facoltà (o globalmente se preferisci)
$stmt = $conn->prepare("SELECT COUNT(*) FROM corso_studio WHERE nome_corso = ? AND facolta_id = ?");
$stmt->bind_param("si", $subject, $faculty_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo json_encode(['success' => false, 'error' => 'Materia già esistente per questa facoltà']);
    exit;
}

// Inserisce la nuova materia
$stmt = $conn->prepare("INSERT INTO corso_studio (nome_corso, facolta_id) VALUES (?, ?)");
$stmt->bind_param("si", $subject, $faculty_id);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true, 'subject' => $subject]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Errore durante l\'aggiunta della materia']);
    exit;
}
