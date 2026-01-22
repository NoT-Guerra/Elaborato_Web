<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

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

if (empty($cart_items)) {
    $_SESSION['error_message'] = "Il tuo carrello è vuoto o contiene articoli non più disponibili.";
    header('Location: carrello.php');
    exit;
}
// prezzo
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['prezzo'];
}
$conn->begin_transaction();

try {
    foreach ($cart_items as $item) {
        $annuncio_id = $item['annuncio_id'];
        $venditore_id = $item['venditore_id'];
        $prezzo = $item['prezzo'];

        // verifica se annuncio disponibile
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

        // vendita
        $insert_vendita = "INSERT INTO vendita (annuncio_id, acquirente_id, venditore_id, prezzo_vendita) 
                           VALUES (?, ?, ?, ?)";
        $stmt_vendita = $conn->prepare($insert_vendita);
        $stmt_vendita->bind_param("iiid", $annuncio_id, $user_id, $venditore_id, $prezzo);
        $stmt_vendita->execute();
        $stmt_vendita->close();

        if (strtolower($item['nome_categoria']) === 'pdf' || $item['is_digitale'] == 1) {
            // prodotti digitali: rimangono attivi per permettere altri acquisti
            $update_annuncio = "UPDATE annuncio SET is_venduto = 0, is_attivo = 1 WHERE id_annuncio = ?";
        } else {
            // prodotti fisici: non più disponibili
            $update_annuncio = "UPDATE annuncio SET is_venduto = 1, is_attivo = 0 WHERE id_annuncio = ?";
        }

        $stmt_update = $conn->prepare($update_annuncio);
        $stmt_update->bind_param("i", $annuncio_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 3. Rimuovi l'articolo dal carrello
        if (strtolower($item['nome_categoria']) === 'pdf' || $item['is_digitale'] == 1) {
            // prodotti digitali, rimuovi solo dal carrello dell'utente attuale
            $delete_carrello = "DELETE FROM carrello WHERE annuncio_id = ? AND utente_id = ?";
            $stmt_delete = $conn->prepare($delete_carrello);
            $stmt_delete->bind_param("ii", $annuncio_id, $user_id);
        } else {
            // prodotti fisici, rimuovi dal carrello di TUTTI gli utenti
            $delete_carrello = "DELETE FROM carrello WHERE annuncio_id = ?";
            $stmt_delete = $conn->prepare($delete_carrello);
            $stmt_delete->bind_param("i", $annuncio_id);
        }
        $stmt_delete->execute();
        $stmt_delete->close();

        // rimuovi dai preferiti
        if (strtolower($item['nome_categoria']) !== 'pdf' && $item['is_digitale'] != 1) {
            $delete_preferiti = "DELETE FROM preferiti WHERE annuncio_id = ?";
            $stmt_pref = $conn->prepare($delete_preferiti);
            $stmt_pref->bind_param("i", $annuncio_id);
            $stmt_pref->execute();
            $stmt_pref->close();
        }
    }
    $conn->commit();

    // prepara i dati per la pagina di conferma
    $_SESSION['purchase_success'] = true;
    $_SESSION['purchase_items'] = $cart_items;
    $_SESSION['purchase_total'] = $total;
    $_SESSION['purchase_date'] = date('d/m/Y H:i:s');

    // porta a pagina di conferma
    header('Location: conferma_acquisto.php');
    exit;

} catch (Exception $e) {
    // errore
    $conn->rollback();

    $_SESSION['error_message'] = "Errore durante l'acquisto: " . $e->getMessage();
    header('Location: carrello.php');
    exit;
}
?>