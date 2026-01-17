<?php
session_start();

// Mock Data for Cart (Simulate DB/Session)
$cart_items = [
    [
        'id' => 1,
        'title' => 'Analisi Matematica 1 - Bramanti',
        'subtitle' => 'Analisi Matematica 1',
        'university' => 'Politecnico di Milano',
        'price' => 25.00,
        'image' => 'images/placeholder-book.png'
    ]
];

// Calculate Totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'];
}
$shipping = 0; // Free
$total = $subtotal + $shipping;

// Format currency helper
function format_currency($amount)
{
    return '€' . number_format($amount, 2, ',', '.');
}

$item_count = count($cart_items);
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
        (function () {
            try {
                const tema = localStorage.getItem('temaPreferito') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', tema);
            } catch (e) { console.warn('Impossibile applicare tema:', e) }
        })();
    </script>
    <link rel="stylesheet" href="style/style.css">
</head>

<body class="bg-body">
    <div class="container-fluid p-0">
        <header class="d-flex align-items-center bg-body m-0 p-3 border-bottom sticky-top">
            <a href="index.php" class="btn btn-link text-body p-0 me-3" aria-label="Torna indietro">
                <i class="bi bi-arrow-left fs-4"></i>
            </a>
            <div class="d-flex align-items-center">
                <span class="bg-primary p-2 rounded-3 me-3 fs-5"><i class="bi bi-cart text-white"></i></span>
                <div>
                    <div class="fw-bold">Il tuo carrello</div>
                    <div class="text-muted small"><?php echo $item_count; ?>
                        articolo<?php echo $item_count !== 1 ? 'i' : ''; ?></div>
                </div>
            </div>
        </header>

        <?php if ($item_count > 0): ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="card shadow-sm my-3 mx-2">
                    <div class="card-body d-flex align-items-start">
                        <div class="flex-shrink-0 me-3">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Anteprima prodotto" class="rounded"
                                style="width:90px;height:120px;object-fit:cover;">
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="card-text text-muted mb-0 small"><?php echo htmlspecialchars($item['subtitle']); ?></p>
                            <p class="card-text text-muted mb-3 small"><?php echo htmlspecialchars($item['university']); ?></p>
                            <div class="fw-bold text-body"><?php echo format_currency($item['price']); ?></div>
                        </div>
                        <button class="btn btn-link text-danger p-0 ms-3" aria-label="Rimuovi articolo">
                            <i class="bi bi-trash fs-4"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="card shadow-sm mt-4 mx-2">
                <div class="card-body">
                    <h4 class="fw-bold mb-3" style="font-size: 1.1rem;">Riepilogo ordine</h4>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotale (<?php echo $item_count; ?>
                            articol<?php echo $item_count !== 1 ? 'i' : 'o'; ?>)</span>
                        <span class="fw-semibold"><?php echo format_currency($subtotal); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Spedizione</span>
                        <span class="text-success fw-bold">Gratuita</span>
                    </div>

                    <div class="d-flex justify-content-between pt-3 border-top">
                        <span class="fw-bold fs-5">Totale</span>
                        <span class="fw-bold fs-5"><?php echo format_currency($total); ?></span>
                    </div>
                </div>
                <div class="p-3 text-center">
                    <button id="btn-procedi" class="btn btn-dark w-100 py-3 mb-3 fw-bold">Procedi al pagamento</button>
                    <a id="btn-continua" href="index.php" class="btn btn-outline-secondary w-100 py-3 fw-bold">Continua lo
                        shopping</a>
                </div>

                <button class="btn btn-secondary rounded-circle position-fixed end-0 me-3 mb-3" style="bottom:1rem;"
                    aria-label="Aiuto">
                    <i class="bi bi-question-circle"></i>
                </button>

            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-cart3 fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Il tuo carrello è vuoto.</p>
                <a href="index.php" class="btn btn-primary mt-3">Inizia lo shopping</a>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script>
        (function () {
            const btnProcedi = document.getElementById('btn-procedi');
            const btnContinua = document.getElementById('btn-continua');

            function applyThemeToButtons(theme) {
                // Se il carrello è vuoto, questi elementi potrebbero non esistere
                if (!btnProcedi || !btnContinua) return;

                if (theme === 'dark') {
                    // Procedi: tutto bianco con testo nero
                    btnProcedi.classList.remove('btn-dark');
                    btnProcedi.classList.add('btn-light');
                    btnProcedi.classList.remove('text-white');
                    btnProcedi.classList.add('text-dark');

                    // Continua: bordo bianco e testo bianco
                    btnContinua.classList.remove('btn-outline-secondary');
                    btnContinua.classList.add('btn-outline-light');
                    btnContinua.classList.remove('text-dark');
                    btnContinua.classList.add('text-white');
                } else {
                    // Light theme: Procedi scuro, testo bianco
                    btnProcedi.classList.remove('btn-light');
                    btnProcedi.classList.add('btn-dark');
                    btnProcedi.classList.remove('text-dark');
                    btnProcedi.classList.add('text-white');

                    // Continua: bordo secondario e testo scuro
                    btnContinua.classList.remove('btn-outline-light');
                    btnContinua.classList.add('btn-outline-secondary');
                    btnContinua.classList.remove('text-white');
                    btnContinua.classList.add('text-dark');
                }
            }

            // Applica tema iniziale
            try {
                const tema = localStorage.getItem('temaPreferito') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                applyThemeToButtons(tema);
            } catch (e) { console.warn('Impossibile leggere tema:', e); }

            // Aggiorna quando il tema cambia in un'altra scheda
            window.addEventListener('storage', (e) => {
                if (e.key === 'temaPreferito') applyThemeToButtons(e.newValue || 'light');
            });
        })();
    </script>
</body>

</html>