<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Recupera gli articoli nel carrello dell'utente
$sql = "SELECT 
            c.annuncio_id,
            a.titolo,
            a.prezzo,
            a.venditore_id,
            a.categoria_id,
            cp.nome_categoria,
            a.is_digitale
        FROM carrello c
        JOIN annuncio a ON c.annuncio_id = a.id_annuncio
        JOIN categoria_prodotto cp ON a.categoria_id = cp.id_categoria
        WHERE c.utente_id = ? AND a.is_attivo = 1 AND a.is_venduto = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Verifica se il carrello è vuoto
if (empty($cart_items)) {
    $_SESSION['error_message'] = "Il tuo carrello è vuoto o contiene articoli non più disponibili.";
    header('Location: carrello.php');
    exit;
}

// Calcola il totale
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['prezzo'];
}

// Inizia la transazione
$conn->begin_transaction();

try {
    foreach ($cart_items as $item) {
        $annuncio_id = $item['annuncio_id'];
        $venditore_id = $item['venditore_id'];
        $prezzo = $item['prezzo'];

        // Verifica se l'annuncio è ancora disponibile (doppio controllo per sicurezza)
        $check_sql = "SELECT is_attivo, is_venduto FROM annuncio WHERE id_annuncio = ? FOR UPDATE";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $annuncio_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $annuncio = $check_result->fetch_assoc();
        $check_stmt->close();

        if (!$annuncio || $annuncio['is_venduto'] || !$annuncio['is_attivo']) {
            throw new Exception("L'articolo '{$item['titolo']}' non è più disponibile.");
        }

        // 1. Registra la vendita
        $insert_vendita = "INSERT INTO vendita (annuncio_id, acquirente_id, venditore_id, prezzo_vendita) 
                           VALUES (?, ?, ?, ?)";
        $stmt_vendita = $conn->prepare($insert_vendita);
        $stmt_vendita->bind_param("iiid", $annuncio_id, $user_id, $venditore_id, $prezzo);
        $stmt_vendita->execute();
        $stmt_vendita->close();

        // 2. Aggiorna lo stato dell'annuncio
        // Per prodotti digitali (PDF) rimangono attivi, per gli altri no
        if (strtolower($item['nome_categoria']) === 'pdf' || $item['is_digitale'] == 1) {
            // Prodotti digitali: marcati come venduti ma rimangono attivi per altri acquisti
            $update_annuncio = "UPDATE annuncio SET is_venduto = 1 WHERE id_annuncio = ?";
        } else {
            // Prodotti fisici: non più disponibili
            $update_annuncio = "UPDATE annuncio SET is_venduto = 1, is_attivo = 0 WHERE id_annuncio = ?";
        }

        $stmt_update = $conn->prepare($update_annuncio);
        $stmt_update->bind_param("i", $annuncio_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 3. Rimuovi l'articolo dal carrello di TUTTI gli utenti (non solo dell'acquirente)
        // Perché se un prodotto fisico viene venduto, non può essere nel carrello di altri
        $delete_carrello = "DELETE FROM carrello WHERE annuncio_id = ?";
        $stmt_delete = $conn->prepare($delete_carrello);
        $stmt_delete->bind_param("i", $annuncio_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // 4. Rimuovi anche dai preferiti (per prodotti fisici)
        if (strtolower($item['nome_categoria']) !== 'pdf' && $item['is_digitale'] != 1) {
            $delete_preferiti = "DELETE FROM preferiti WHERE annuncio_id = ?";
            $stmt_pref = $conn->prepare($delete_preferiti);
            $stmt_pref->bind_param("i", $annuncio_id);
            $stmt_pref->execute();
            $stmt_pref->close();
        }
    }

    // 5. Conferma la transazione
    $conn->commit();

    // Prepara i dati per la pagina di conferma
    $_SESSION['purchase_success'] = true;
    $_SESSION['purchase_items'] = $cart_items;
    $_SESSION['purchase_total'] = $total;
    $_SESSION['purchase_date'] = date('d/m/Y H:i:s');

    // Reindirizza alla pagina di conferma
    header('Location: conferma_acquisto.php');
    exit;

} catch (Exception $e) {
    // Annulla la transazione in caso di errore
    $conn->rollback();

    $_SESSION['error_message'] = "Errore durante l'acquisto: " . $e->getMessage();
    header('Location: carrello.php');
    exit;
}
?>