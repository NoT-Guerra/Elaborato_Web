<?php
session_start();
header('Content-Type: application/json');

// --- CONFIGURAZIONE DATABASE ---
require_once __DIR__ . '/../../app/config/database.php';

$response = ['success' => false, 'message' => ''];

// --- CONTROLLO UTENTE ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Devi essere loggato per aggiungere ai preferiti.']);
    exit;
}
$id_utente_loggato = $_SESSION['user_id'];

// --- LOGICA AGGIUNTA ---
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (isset($input['id_annuncio'])) {
    $id_annuncio = (int) $input['id_annuncio'];

    try {
        // Check se esiste già
        $check = $conn->prepare("SELECT id_preferito FROM preferiti WHERE utente_id = ? AND annuncio_id = ?");
        $check->bind_param("ii", $id_utente_loggato, $id_annuncio);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $response['message'] = "Annuncio già nei preferiti.";
            $response['success'] = true; // Consideriamo successo anche se c'era già, per idempotenza
        } else {
            $stmt = $conn->prepare("INSERT INTO preferiti (utente_id, annuncio_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_utente_loggato, $id_annuncio);
            $stmt->execute();
            $stmt->close();
            $response['message'] = "Aggiunto ai preferiti!";
            $response['success'] = true;
        }
        $check->close();

        // Conta totale preferiti
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM preferiti WHERE utente_id = ?");
        $countStmt->bind_param("i", $id_utente_loggato);
        $countStmt->execute();
        $countStmt->bind_result($count);
        $countStmt->fetch();
        $countStmt->close();
        $response['count'] = $count;

    } catch (Exception $e) {
        $response['message'] = "Errore: " . $e->getMessage();
    }
} else {
    $response['message'] = "ID annuncio mancante.";
}

echo json_encode($response);
