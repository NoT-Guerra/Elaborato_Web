<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID utente mancante']);
    exit;
}

$user_id = intval($_POST['user_id']);
if (isset($_SESSION['id_utente']) && $user_id == $_SESSION['id_utente']) {
    echo json_encode(['success' => false, 'error' => 'Non puoi eliminare il tuo stesso account da qui']);
    exit;
}

$conn->begin_transaction();

try {
    $conn->query("DELETE FROM vendita WHERE acquirente_id = $user_id OR venditore_id = $user_id");
    $conn->query("DELETE FROM preferiti WHERE utente_id = $user_id");
    $conn->query("DELETE FROM carrello WHERE utente_id = $user_id");
    $conn->query("DELETE FROM annuncio_pdf WHERE annuncio_id IN (SELECT id_annuncio FROM annuncio WHERE venditore_id = $user_id)");
    $conn->query("DELETE FROM annuncio WHERE venditore_id = $user_id");
    $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Errore nell'eliminazione dell'utente: " . $stmt->error);
    }

    // fine giusta
    $conn->commit();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Utente eliminato con successo']);

} catch (Exception $e) {
    // rollback se errore
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

exit;