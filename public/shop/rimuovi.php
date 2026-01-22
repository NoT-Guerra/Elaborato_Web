<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
if (!isset($_SESSION['user_id'])) {
    $id_utente_loggato = 1;
} else {
    $id_utente_loggato = $_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_annuncio'])) {
    $id_annuncio = (int) $_POST['id_annuncio'];

    try {
        $stmt = $conn->prepare("DELETE FROM carrello WHERE utente_id = ? AND annuncio_id = ?");
        $stmt->bind_param("ii", $id_utente_loggato, $id_annuncio);
        $stmt->execute();
        $stmt->close();

        // tutto ok
        $_SESSION['msg_success'] = "Articolo rimosso dal carrello.";
    } catch (Exception $e) {
        // errore
        $_SESSION['msg_error'] = "Errore durante la rimozione.";
    }
}

header('Location: carrello.php');
exit;
