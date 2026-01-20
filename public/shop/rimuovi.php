<?php
session_start();

// --- CONFIGURAZIONE DATABASE ---
require_once __DIR__ . '/../../app/config/database.php';

// --- CONTROLLO UTENTE ---
if (!isset($_SESSION['user_id'])) {
    // Per testing, assumiamo ID 1 come in carrello.php
    $id_utente_loggato = 1;
} else {
    $id_utente_loggato = $_SESSION['user_id'];
}

// --- LOGICA RIMOZIONE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_annuncio'])) {
    $id_annuncio = (int) $_POST['id_annuncio'];

    try {
        $stmt = $conn->prepare("DELETE FROM carrello WHERE utente_id = ? AND annuncio_id = ?");
        $stmt->bind_param("ii", $id_utente_loggato, $id_annuncio);
        $stmt->execute();
        $stmt->close();

        // Successo
        $_SESSION['msg_success'] = "Articolo rimosso dal carrello.";
    } catch (Exception $e) {
        // Errore
        $_SESSION['msg_error'] = "Errore durante la rimozione.";
    }
}

// Redirect al carrello
header('Location: carrello.php');
exit;
