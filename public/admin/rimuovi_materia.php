<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

if (
    !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true ||
    !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != true
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

if (!isset($_POST['subject']) || empty(trim($_POST['subject']))) {
    echo json_encode(['success' => false, 'error' => 'Nome materia mancante']);
    exit;
}

$subjectName = trim($_POST['subject']);
if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Errore di connessione al database']);
    exit;
}

try {
    $checkQuery = "SELECT COUNT(*) as count FROM corso_studio WHERE nome_corso = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $subjectName);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkData = $checkResult->fetch_assoc();
    $checkStmt->close();

    if ($checkData['count'] == 0) {
        echo json_encode(['success' => false, 'error' => 'Materia non trovata']);
        exit;
    }

    $checkAdsQuery = "SELECT COUNT(*) as count FROM annuncio WHERE corso_id IN (SELECT id_corso FROM corso_studio WHERE nome_corso = ?)";
    $checkAdsStmt = $conn->prepare($checkAdsQuery);
    $checkAdsStmt->bind_param("s", $subjectName);
    $checkAdsStmt->execute();
    $checkAdsResult = $checkAdsStmt->get_result();
    $checkAdsData = $checkAdsResult->fetch_assoc();
    $checkAdsStmt->close();

    if ($checkAdsData['count'] > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Impossibile eliminare: ci sono annunci associati a questa materia'
        ]);
        exit;
    }
    // materia eliminata
    $deleteQuery = "DELETE FROM corso_studio WHERE nome_corso = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("s", $subjectName);

    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Materia eliminata con successo']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Errore durante l\'eliminazione: ' . $conn->error]);
    }

    $deleteStmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Errore: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>