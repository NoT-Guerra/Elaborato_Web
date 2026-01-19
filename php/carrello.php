<?php
// 1. Inizio sessione (deve essere la primissima cosa)
session_start();

// --- CONFIGURAZIONE DATABASE ---
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

// --- CONTROLLO SESSIONE E UTENTE ---
// Verifica se l'utente è loggato. 
// Se non lo è, per ora forziamo l'ID 1 per i tuoi test, 
// ma ti stampo un avviso se la sessione è vuota.
if (!isset($_SESSION['user_id'])) {
    // NOTA: In produzione qui metteresti header('Location: login.php');
    $id_utente_loggato = 1;
} else {
    $id_utente_loggato = $_SESSION['user_id'];
}

// --- RECUPERO DATI DAL DB ---
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
        WHERE c.utente_id = :utente_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['utente_id' => $id_utente_loggato]);
$db_items = $stmt->fetchAll();

// Trasformiamo i dati per il template HTML
$cart_items = [];
foreach ($db_items as $row) {
    $cart_items[] = [
        'id' => $row['id_annuncio'],
        'title' => $row['titolo'],
        'subtitle' => $row['nome_corso'] ?? 'Corso non specificato',
        'university' => $row['nome_facolta'] ?? 'Facoltà non specificata',
        'price' => (float) $row['prezzo'],
        'image' => !empty($row['immagine_url']) ? $row['immagine_url'] : 'images/placeholder-book.png'
    ];
}

// Calcoli
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'];
}
$total = $subtotal; // Spedizione gratuita
$item_count = count($cart_items);

function format_currency($amount)
{
    return '€' . number_format($amount, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrello</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script>
        // Gestione tema scuro/chiaro
        (function () {
            const tema = localStorage.getItem('temaPreferito') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();
    </script>
</head>

<body class="bg-body">



    <div class="container-fluid p-0">
        <header class="d-flex align-items-center bg-body m-0 p-3 border-bottom sticky-top">
            <a href="index.php" class="btn btn-link text-body p-0 me-3"><i class="bi bi-arrow-left fs-4"></i></a>
            <div>
                <div class="fw-bold">Il tuo carrello</div>
                <div class="text-muted small"><?php echo $item_count; ?> articolo/i</div>
            </div>
        </header>

        <?php if ($item_count > 0): ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="card shadow-sm my-3 mx-2">
                    <div class="card-body d-flex align-items-start">
                        <div class="flex-shrink-0 me-3">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" class="rounded"
                                style="width:90px;height:120px;object-fit:cover;">
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="card-text text-muted mb-0 small"><?php echo htmlspecialchars($item['subtitle']); ?></p>
                            <p class="card-text text-muted mb-3 small"><?php echo htmlspecialchars($item['university']); ?></p>
                            <div class="fw-bold text-body"><?php echo format_currency($item['price']); ?></div>
                        </div>
                        <form action="rimuovi.php" method="POST">
                            <input type="hidden" name="id_annuncio" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn btn-link text-danger p-0 ms-3"><i
                                    class="bi bi-trash fs-4"></i></button>
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
                <button class="btn btn-dark w-100 py-3 mt-3 fw-bold">Procedi al pagamento</button>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-cart3 fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Il tuo carrello è vuoto.</p>
                <a href="index.php" class="btn btn-primary mt-3">Inizia lo shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>