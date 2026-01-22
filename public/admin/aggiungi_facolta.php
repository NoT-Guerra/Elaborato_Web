<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

if (!isset($_POST['faculty']) || trim($_POST['faculty']) === '') {
    echo json_encode(['success' => false, 'error' => 'Nome della facoltà non valido']);
    exit;
}

$faculty = trim($_POST['faculty']);

// controlla se la facoltà esiste già
$stmt = $conn->prepare("SELECT COUNT(*) FROM facolta WHERE nome_facolta = ?");
$stmt->bind_param("s", $faculty);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo json_encode(['success' => false, 'error' => 'Facoltà già esistente']);
    exit;
}

// nuova facoltà
$stmt = $conn->prepare("INSERT INTO facolta (nome_facolta) VALUES (?)");
$stmt->bind_param("s", $faculty);

if ($stmt->execute()) {
    $id = $stmt->insert_id;
    $stmt->close();
    echo json_encode(['success' => true, 'faculty' => $faculty, 'id' => $id]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Errore durante l\'aggiunta della facoltà']);
    exit;
}
