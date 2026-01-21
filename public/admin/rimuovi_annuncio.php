<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

// Controlla se l'utente è admin
if (!isset($_SESSION['loggedin'], $_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

// Controlla se il campo announcement_id è presente
if (!isset($_POST['announcement_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID annuncio mancante']);
    exit;
}

$announcement_id = intval($_POST['announcement_id']);

// Inizia una transazione per gestire le dipendenze
$conn->begin_transaction();

try {

    $stmt_v = $conn->prepare("DELETE FROM vendita WHERE annuncio_id = ?");
    $stmt_v->bind_param("i", $announcement_id);
    $stmt_v->execute();
    $stmt_v->close();


    $stmt_p = $conn->prepare("DELETE FROM preferiti WHERE annuncio_id = ?");
    $stmt_p->bind_param("i", $announcement_id);
    $stmt_p->execute();
    $stmt_p->close();


    $stmt_c = $conn->prepare("DELETE FROM carrello WHERE annuncio_id = ?");
    $stmt_c->bind_param("i", $announcement_id);
    $stmt_c->execute();
    $stmt_c->close();


    $stmt_pdf = $conn->prepare("DELETE FROM annuncio_pdf WHERE annuncio_id = ?");
    $stmt_pdf->bind_param("i", $announcement_id);
    $stmt_pdf->execute();
    $stmt_pdf->close();


    $stmt = $conn->prepare("DELETE FROM annuncio WHERE id_annuncio = ?");
    $stmt->bind_param("i", $announcement_id);

    if (!$stmt->execute()) {
        throw new Exception("Errore durante l'eliminazione dell'annuncio: " . $stmt->error);
    }
    $stmt->close();

    // Se tutto va a buon fine, conferma la transazione
    $conn->commit();
    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    // In caso di errore, annulla tutto
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
