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
if (isset($_SESSION['id_utente']) && $user_id == $_SESSION['id_utente']) {
    echo json_encode(['success' => false, 'error' => 'Non puoi eliminare il tuo stesso account da qui']);
    exit;
}

// Inizia una transazione per garantire l'atomicità delle operazioni
$conn->begin_transaction();

try {
    // PRIMO: Elimina le vendite associate all'utente (sia come acquirente che come venditore)
    $conn->query("DELETE FROM vendita WHERE acquirente_id = $user_id OR venditore_id = $user_id");

    // SECONDO: Elimina i preferiti dell'utente
    $conn->query("DELETE FROM preferiti WHERE utente_id = $user_id");

    // TERZO: Elimina gli elementi del carrello dell'utente
    $conn->query("DELETE FROM carrello WHERE utente_id = $user_id");

    // QUARTO: Elimina gli annunci dell'utente (con CASCADE eliminerà anche preferiti/carrello relativi)
    // Prima elimina i PDF associati agli annunci dell'utente
    $conn->query("DELETE FROM annuncio_pdf WHERE annuncio_id IN (SELECT id_annuncio FROM annuncio WHERE venditore_id = $user_id)");

    // Poi elimina gli annunci
    $conn->query("DELETE FROM annuncio WHERE venditore_id = $user_id");

    // QUINTO: Finalmente elimina l'utente
    $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ?");
    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        throw new Exception("Errore nell'eliminazione dell'utente: " . $stmt->error);
    }

    // Conferma la transazione
    $conn->commit();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Utente eliminato con successo']);

} catch (Exception $e) {
    // Rollback in caso di errore
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

exit;