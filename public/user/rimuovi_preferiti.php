<?php
session_start();

// gestione ajax e post
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
if (!$isAjax) {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (strpos($contentType, 'application/json') !== false) {
        $isAjax = true;
    }
}

if ($isAjax) {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../../app/config/database.php';

$response = ['success' => false, 'message' => ''];
$redirectUrl = 'preferiti.php';

// check utente
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

$id_annuncio = null;

if ($isAjax) {
    //input del json
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (isset($input['id_annuncio'])) {
        $id_annuncio = (int) $input['id_annuncio'];
    }
} else {
    // input del post
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

// mostra output
if ($isAjax) {
    echo json_encode($response);
} else {
    header("Location: $redirectUrl");
}
exit;
