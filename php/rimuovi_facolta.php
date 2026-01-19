<?php
session_start();
require_once '../config/database.php';

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Controlla se il campo faculty è presente
if (!isset($_POST['faculty']) || trim($_POST['faculty']) === '') {
    die('Nome della facoltà non valido');
}

$faculty = trim($_POST['faculty']);

// Elimina la facoltà
$stmt = $conn->prepare("DELETE FROM facolta WHERE nome_facolta = ?");
$stmt->bind_param("s", $faculty);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: ../admin.php?msg=faculty_deleted');
    exit;
} else {
    die('Errore durante l\'eliminazione della facoltà');
}
