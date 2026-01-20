<?php
session_start();

// Gestiamo sia richieste AJAX (JSON) che POST standard (Redirect)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
if (!$isAjax) {
    // Controlla anche l'header Content-Type per fetch() API che non sempre invia X-Requested-With
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (strpos($contentType, 'application/json') !== false) {
        $isAjax = true;
    }
}

// Se è AJAX, rispondimo JSON
if ($isAjax) {
    header('Content-Type: application/json');
}

// --- CONFIGURAZIONE DATABASE ---
require_once __DIR__ . '/../../app/config/database.php';

$response = ['success' => false, 'message' => ''];
$redirectUrl = 'preferiti.php';

// --- CONTROLLO UTENTE ---
if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Devi essere loggato.']);
        exit;
    } else {
        header('Location: ../auth/login.php');
        exit;
    }
}
$id_utente_loggato = $_SESSION['user_id'];

// --- LOGICA RIMOZIONE ---
$id_annuncio = null;

if ($isAjax) {
    // Input JSON
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (isset($input['id_annuncio'])) {
        $id_annuncio = (int) $input['id_annuncio'];
    }
} else {
    // Input POST form
    if (isset($_POST['id_annuncio'])) {
        $id_annuncio = (int) $_POST['id_annuncio'];
    }
}

if ($id_annuncio) {
    try {
        $stmt = $conn->prepare("DELETE FROM preferiti WHERE utente_id = ? AND annuncio_id = ?");
        $stmt->bind_param("ii", $id_utente_loggato, $id_annuncio);
        $stmt->execute();
        $stmt->close();

        $response['success'] = true;
        $response['message'] = "Rimosso dai preferiti.";

        // Conta
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM preferiti WHERE utente_id = ?");
        $countStmt->bind_param("i", $id_utente_loggato);
        $countStmt->execute();
        $countStmt->bind_result($count);
        $countStmt->fetch();
        $countStmt->close();
        $response['count'] = $count;

    } catch (Exception $e) {
        $response['message'] = "Errore durante la rimozione.";
    }
} else {
    $response['message'] = "ID annuncio mancante.";
}

// Output
if ($isAjax) {
    echo json_encode($response);
} else {
    // Redirect per form submit standard
    header("Location: $redirectUrl");
}
exit;
