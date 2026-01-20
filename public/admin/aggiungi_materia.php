<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Controlla se il campo subject è presente
if (!isset($_POST['subject']) || trim($_POST['subject']) === '') {
    die('Nome della materia non valido');
}

$subject = trim($_POST['subject']);

// Controlla se la materia esiste già
$stmt = $conn->prepare("SELECT COUNT(*) FROM corso_studio WHERE nome_corso = ?");
$stmt->bind_param("s", $subject);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    die('Materia già esistente');
}

// Inserisce la nuova materia
$stmt = $conn->prepare("INSERT INTO corso_studio (nome_corso) VALUES (?)");
$stmt->bind_param("s", $subject);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: index.php?msg=subject_added');
    exit;
} else {
    die('Errore durante l\'aggiunta della materia');
}
