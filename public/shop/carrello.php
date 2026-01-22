<?php
session_start();

// mostra messaggi di errore o successo
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show m-2" role="alert">
            <span class="bi bi-exclamation-triangle me-2" aria-hidden="true"></span>' . $_SESSION['error_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show m-2" role="alert">
            <span class="bi bi-check-circle me-2" aria-hidden="true"></span>' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['success_message']);
}
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $id_utente_loggato = 1;
} else {
    $id_utente_loggato = $_SESSION['user_id'];
}

$sql = "SELECT 
            a.id_annuncio, 
            a.titolo, 
            a.prezzo, 
            a.immagine_url,
            cs.nome_corso,
            f.nome_facolta
        FROM carrello c
        JOIN annuncio a ON c.annuncio_id = a.id_annuncio
        LEFT JOIN corso_studio cs ON a.corso_id = cs.id_corso
        LEFT JOIN facolta f ON a.facolta_id = f.id_facolta
        WHERE c.utente_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_utente_loggato);
$stmt->execute();
$result = $stmt->get_result();
$db_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// trasforma i dati per il template html
$cart_items = [];
foreach ($db_items as $row) {
    // gestione immagine: se inizia con http usa quella, altrimenti aggiungi prefisso assets e usa basename
    $img_db = $row['immagine_url'];
    if (!empty($img_db)) {
        if (str_starts_with($img_db, 'http')) {
            $imgUrl = $img_db;
        } else {
            $imgUrl = '../assets/img/' . basename($img_db);
        }
    } else {
        $imgUrl = 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=600';
    }

    $cart_items[] = [
        'id' => $row['id_annuncio'],
        'title' => $row['titolo'],
        'subtitle' => $row['nome_corso'] ?? 'Corso non specificato',
        'university' => $row['nome_facolta'] ?? 'Facoltà non specificata',
        'price' => (float) $row['prezzo'],
        'image' => $imgUrl,
    ];
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'];
}
$total = $subtotal;
$item_count = count($cart_items);

function format_currency($amount)
{
    return '€' . number_format($amount, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Carrello</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <script>
        // tema scuro/chiaro
        (function () {
            const tema = localStorage.getItem('temaPreferito') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();
    </script>
</head>

<body class="bg-body">
    <div class="container-fluid p-0">
        <header class="d-flex align-items-center bg-body m-0 p-3 border-bottom sticky-top">
            <a href="../index.php" class="btn btn-link text-body p-0 me-3" aria-label="Torna alla Home"><span
                    class="bi bi-arrow-left fs-4" aria-hidden="true"></span></a>
            <div>
                <h1 class="h5 fw-bold mb-0">Il tuo carrello</h1>
                <div class="text-muted small"><?php echo $item_count; ?> articolo/i</div>
            </div>
        </header>

        <?php if ($item_count > 0): ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="card shadow-sm my-3 mx-2">
                    <div class="card-body d-flex align-items-start">
                        <div class="flex-shrink-0 me-3">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" class="rounded"
                                style="width:90px;height:120px;object-fit:cover;"
                                alt="<?php echo htmlspecialchars($item['title']); ?>" />
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="card-title h5 fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h2>
                            <p class="card-text text-muted mb-0 small"><?php echo htmlspecialchars($item['subtitle']); ?></p>
                            <p class="card-text text-muted mb-3 small"><?php echo htmlspecialchars($item['university']); ?></p>
                            <div class="fw-bold text-body"><?php echo format_currency($item['price']); ?></div>
                        </div>
                        <form action="rimuovi.php" method="POST">
                            <input type="hidden" name="id_annuncio" value="<?php echo $item['id']; ?>" />
                            <button type="submit" class="btn btn-link text-danger p-0 ms-3" aria-label="Rimuovi prodotto"><span
                                    class="bi bi-trash fs-4" aria-hidden="true"></span></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="card shadow-sm mt-4 mx-2 p-3">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotale</span>
                    <span class="fw-semibold"><?php echo format_currency($subtotal); ?></span>
                </div>
                <div class="d-flex justify-content-between pt-3 border-top">
                    <span class="fw-bold fs-5">Totale</span>
                    <span class="fw-bold fs-5"><?php echo format_currency($total); ?></span>
                </div>
                <form action="processa_pagamento.php" method="POST">
                    <button type="submit" class="btn btn-primary w-100 py-3 mt-3 fw-bold">
                        <span class="bi bi-credit-card me-2" aria-hidden="true"></span>Procedi al pagamento
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <span class="bi bi-cart3 fs-1 text-muted" aria-hidden="true"></span>
                <p class="mt-3 text-muted">Il tuo carrello è vuoto.</p>
                <a href="../index.php" class="btn btn-primary mt-3">Inizia lo shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>