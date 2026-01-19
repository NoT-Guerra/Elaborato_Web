<?php
session_start();

// --- CONFIGURAZIONE DATABASE (Copia da carrello.php per coerenza) ---
$host = 'localhost';
$db = 'marketplace_universitario';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

// --- CONTROLLO UTENTE ---
if (!isset($_SESSION['user_id'])) {
    // Per testing, assumiamo ID 1 come in carrello.php
    $id_utente_loggato = 1;
} else {
    $id_utente_loggato = $_SESSION['user_id'];
}

// --- LOGICA RIMOZIONE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_annuncio'])) {
    $id_annuncio = (int) $_POST['id_annuncio'];

    try {
        $stmt = $pdo->prepare("DELETE FROM carrello WHERE utente_id = :utente_id AND annuncio_id = :annuncio_id");
        $stmt->execute([
            'utente_id' => $id_utente_loggato,
            'annuncio_id' => $id_annuncio
        ]);

        // Successo
        $_SESSION['msg_success'] = "Articolo rimosso dal carrello.";
    } catch (Exception $e) {
        // Errore
        $_SESSION['msg_error'] = "Errore durante la rimozione.";
    }
}

// Redirect al carrello
header('Location: carrello.php');
exit;
