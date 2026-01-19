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

// --- CONTROLLO UTENTE ---
if (!isset($_SESSION['user_id'])) {
    $id_utente_loggato = 1; // Fallback
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
        $check = $pdo->prepare("SELECT id_carrello FROM carrello WHERE utente_id = :u AND annuncio_id = :a");
        $check->execute(['u' => $id_utente_loggato, 'a' => $id_annuncio]);

        if ($check->fetch()) {
            $response['success'] = false;
            $response['message'] = "Prodotto già nel carrello!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO carrello (utente_id, annuncio_id) VALUES (:u, :a)");
            $result = $stmt->execute(['u' => $id_utente_loggato, 'a' => $id_annuncio]);

            $response['success'] = true;
            $response['message'] = "Prodotto aggiunto al carrello!";

            // Get new count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM carrello WHERE utente_id = :u");
            $countStmt->execute(['u' => $id_utente_loggato]);
            $response['count'] = $countStmt->fetchColumn();
        }
    } catch (Exception $e) {
        $response['message'] = "Errore durante l'aggiunta: " . $e->getMessage();
    }
} else {
    $response['message'] = "ID annuncio mancante.";
}

echo json_encode($response);
exit;
