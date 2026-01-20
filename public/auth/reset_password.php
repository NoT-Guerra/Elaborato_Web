<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Controlla input
if (!isset($_POST['user_id'], $_POST['new_password']) || !is_numeric($_POST['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Dati non validi']));
}

$userId = (int) $_POST['user_id'];
$newPassword = trim($_POST['new_password']);

if (strlen($newPassword) < 6) {
    die(json_encode(['success' => false, 'error' => 'La password deve essere di almeno 6 caratteri']));
}

// Hash della password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Aggiorna la password dell'utente
$stmt = $conn->prepare("UPDATE utenti SET password = ? WHERE id_utente = ?");
$stmt->bind_param("si", $hashedPassword, $userId);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
} else {
    die(json_encode(['success' => false, 'error' => 'Errore durante il reset della password']));
}