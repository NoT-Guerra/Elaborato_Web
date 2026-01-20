<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Controlla se il campo faculty è presente
if (!isset($_POST['faculty']) || trim($_POST['faculty']) === '') {
    die('Nome della facoltà non valido');
}

$faculty = trim($_POST['faculty']);

// Controlla se la facoltà esiste già
$stmt = $conn->prepare("SELECT COUNT(*) FROM facolta WHERE nome_facolta = ?");
$stmt->bind_param("s", $faculty);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    die('Facoltà già esistente');
}

// Inserisce la nuova facoltà
$stmt = $conn->prepare("INSERT INTO facolta (nome_facolta) VALUES (?)");
$stmt->bind_param("s", $faculty);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: index.php?msg=faculty_added');
    exit;
} else {
    die('Errore durante l\'aggiunta della facoltà');
}
