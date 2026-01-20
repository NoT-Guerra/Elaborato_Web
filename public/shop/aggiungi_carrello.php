<?php
session_start();
header('Content-Type: application/json');

// --- CONFIGURAZIONE DATABASE ---
require_once __DIR__ . '/../../app/config/database.php';

$response = ['success' => false, 'message' => ''];

// --- CONTROLLO UTENTE ---
if (!isset($_SESSION['user_id'])) {
    $id_utente_loggato = 1; // Fallback temporaneo come nell'originale, o meglio bloccare?
    // Manteniamo logica originale: se non loggato usa ID 1 (test mode) o gestisci
    // Ma meglio essere sicuri. Carrello richiede login di solito.
    // L'originale aveva fallback. Manteniamolo per ora o usiamo session.
    // Se non set, $id_utente_loggato = 1;
} else {
    $id_utente_loggato = $_SESSION['user_id'];
}

// --- LOGICA AGGIUNTA ---
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Support both JSON input and standard POST
if (!$input && isset($_POST['id_annuncio'])) {
    $input = ['id_annuncio' => $_POST['id_annuncio']];
}

if (isset($input['id_annuncio'])) {
    $id_annuncio = (int) $input['id_annuncio'];

    try {
        // Check concurrency/duplicates
        // Usiamo MySQLi
        $check = $conn->prepare("SELECT id_carrello FROM carrello WHERE utente_id = ? AND annuncio_id = ?");
        $check->bind_param("ii", $id_utente_loggato, $id_annuncio);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $response['success'] = false;
            $response['message'] = "Prodotto già nel carrello!";
        } else {
            $check->close(); // Chiudi check prima di inserire

            $stmt = $conn->prepare("INSERT INTO carrello (utente_id, annuncio_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_utente_loggato, $id_annuncio);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Prodotto aggiunto al carrello!";
                $stmt->close();

                // Get new count
                $countStmt = $conn->prepare("SELECT COUNT(*) FROM carrello WHERE utente_id = ?");
                $countStmt->bind_param("i", $id_utente_loggato);
                $countStmt->execute();
                $countStmt->bind_result($count);
                $countStmt->fetch();
                $countStmt->close();

                $response['count'] = $count;
            } else {
                $stmt->close();
                throw new Exception("Errore insert");
            }
        }
    } catch (Exception $e) {
        $response['message'] = "Errore durante l'aggiunta: " . $e->getMessage();
    }
} else {
    $response['message'] = "ID annuncio mancante.";
}

echo json_encode($response);
exit;
