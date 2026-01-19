<?php
session_start();
header('Content-Type: application/json');

// --- CONFIGURAZIONE DATABASE ---
$host = 'localhost';
$db = 'marketplace_universitario';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$response = ['success' => false, 'message' => ''];

// --- CONTROLLO UTENTE ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Devi essere loggato per aggiungere ai preferiti.']);
    exit;
}
$id_utente_loggato = $_SESSION['user_id'];

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
    exit;
}

// --- LOGICA AGGIUNTA ---
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (isset($input['id_annuncio'])) {
    $id_annuncio = (int) $input['id_annuncio'];

    try {
        // Check se esiste già
        $check = $pdo->prepare("SELECT id_preferito FROM preferiti WHERE utente_id = :u AND annuncio_id = :a");
        $check->execute(['u' => $id_utente_loggato, 'a' => $id_annuncio]);

        if ($check->fetch()) {
            $response['message'] = "Annuncio già nei preferiti.";
            $response['success'] = true; // Consideriamo successo anche se c'era già, per idempotenza
        } else {
            $stmt = $pdo->prepare("INSERT INTO preferiti (utente_id, annuncio_id) VALUES (:u, :a)");
            $stmt->execute(['u' => $id_utente_loggato, 'a' => $id_annuncio]);
            $response['message'] = "Aggiunto ai preferiti!";
            $response['success'] = true;
        }

        // Conta totale preferiti
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM preferiti WHERE utente_id = :u");
        $countStmt->execute(['u' => $id_utente_loggato]);
        $response['count'] = $countStmt->fetchColumn();

    } catch (Exception $e) {
        $response['message'] = "Errore: " . $e->getMessage();
    }
} else {
    $response['message'] = "ID annuncio mancante.";
}

echo json_encode($response);
