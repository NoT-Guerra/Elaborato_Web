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
            a.is_digitale,
            a.is_venduto
        FROM carrello c
        JOIN annuncio a ON c.annuncio_id = a.id_annuncio
        JOIN categoria_prodotto cp ON a.categoria_id = cp.id_categoria
        WHERE c.utente_id = ? AND a.is_attivo = 1";

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

// calcolo prezzo
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
        $is_digitale = $item['is_digitale'];
        $nome_categoria = strtolower($item['nome_categoria']);

        // verifica se l'utente ha già acquistato questo annuncio
        $check_acquisto_sql = "SELECT COUNT(*) as gia_acquistato 
                              FROM vendita 
                              WHERE annuncio_id = ? AND acquirente_id = ?";
        $check_acquisto_stmt = $conn->prepare($check_acquisto_sql);
        $check_acquisto_stmt->bind_param("ii", $annuncio_id, $user_id);
        $check_acquisto_stmt->execute();
        $check_acquisto_result = $check_acquisto_stmt->get_result();
        $acquisto_data = $check_acquisto_result->fetch_assoc();
        $check_acquisto_stmt->close();

        if ($acquisto_data['gia_acquistato'] > 0) {
            throw new Exception("Hai già acquistato l'articolo '{$item['titolo']}'.");
        }

        // verifica se annuncio è ancora disponibile 
        $check_sql = "SELECT is_attivo, is_venduto FROM annuncio WHERE id_annuncio = ? FOR UPDATE";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $annuncio_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $annuncio = $check_result->fetch_assoc();
        $check_stmt->close();

        if (!$annuncio || !$annuncio['is_attivo']) {
            throw new Exception("L'articolo '{$item['titolo']}' non è più attivo.");
        }

        // per prodotti FISICI, verifica che non sia già venduto
        if ($nome_categoria !== 'pdf' && $is_digitale != 1 && $annuncio['is_venduto']) {
            throw new Exception("L'articolo '{$item['titolo']}' (prodotto fisico) è già stato venduto.");
        }

        // inserimento vendita
        $insert_vendita = "INSERT INTO vendita (annuncio_id, acquirente_id, venditore_id, prezzo_vendita) 
                           VALUES (?, ?, ?, ?)";
        $stmt_vendita = $conn->prepare($insert_vendita);
        $stmt_vendita->bind_param("iiid", $annuncio_id, $user_id, $venditore_id, $prezzo);
        $stmt_vendita->execute();
        $stmt_vendita->close();

        // aggiornamento annuncio (solo per prodotti fisici)
        if ($nome_categoria === 'pdf' || $is_digitale == 1) {
            // prodotti digitali: NON aggiornare is_venduto, rimane attivo per altri acquisti
            // annuncio rimane con is_venduto = 0 e is_attivo = 1
        } else {
            // prodotti fisici: marcare come venduto e non più attivo
            $update_annuncio = "UPDATE annuncio SET is_venduto = 1, is_attivo = 0 WHERE id_annuncio = ?";
            $stmt_update = $conn->prepare($update_annuncio);
            $stmt_update->bind_param("i", $annuncio_id);
            $stmt_update->execute();
            $stmt_update->close();
        }

        // rimozione dal carrello (solo per l'utente corrente)
        $delete_carrello = "DELETE FROM carrello WHERE annuncio_id = ? AND utente_id = ?";
        $stmt_delete = $conn->prepare($delete_carrello);
        $stmt_delete->bind_param("ii", $annuncio_id, $user_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // rimozione dai preferiti (solo per prodotti fisici venduti)
        if ($nome_categoria !== 'pdf' && $is_digitale != 1) {
            $delete_preferiti = "DELETE FROM preferiti WHERE annuncio_id = ?";
            $stmt_pref = $conn->prepare($delete_preferiti);
            $stmt_pref->bind_param("i", $annuncio_id);
            $stmt_pref->execute();
            $stmt_pref->close();
        }

        // per prodotti fisici, rimuovi dal carrello di TUTTI gli altri utenti
        if ($nome_categoria !== 'pdf' && $is_digitale != 1) {
            $delete_carrello_altri = "DELETE FROM carrello WHERE annuncio_id = ? AND utente_id != ?";
            $stmt_delete_altri = $conn->prepare($delete_carrello_altri);
            $stmt_delete_altri->bind_param("ii", $annuncio_id, $user_id);
            $stmt_delete_altri->execute();
            $stmt_delete_altri->close();
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