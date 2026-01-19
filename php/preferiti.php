<?php
session_start();

// --- CONFIGURAZIONE DATABASE ---
$host = 'localhost';
$db = 'marketplace_universitario';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// --- CONTROLLO UTENTE ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
    die("Errore di connessione: " . $e->getMessage());
}

// --- RECUPERO PREFERITI ---
$sql = "SELECT 
            a.id_annuncio, 
            a.titolo, 
            a.prezzo, 
            a.immagine_url,
            a.descrizione,
            a.data_pubblicazione,
            a.is_digitale,
            cs.nome_corso,
            f.nome_facolta,
            cp.nome_categoria,
            cond.nome_condizione
        FROM preferiti p
        JOIN annuncio a ON p.annuncio_id = a.id_annuncio
        LEFT JOIN corso_studio cs ON a.corso_id = cs.id_corso
        LEFT JOIN facolta f ON a.facolta_id = f.id_facolta
        JOIN categoria_prodotto cp ON a.categoria_id = cp.id_categoria
        JOIN condizione_prodotto cond ON a.condizione_id = cond.id_condizione
        WHERE p.utente_id = :utente_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['utente_id' => $id_utente_loggato]);
$favorite_items = $stmt->fetchAll();
$item_count = count($favorite_items);

// Conta articoli nel carrello per header
$cart_count = 0;
$stmtCart = $pdo->prepare("SELECT COUNT(*) FROM carrello WHERE utente_id = :u");
$stmtCart->execute(['u' => $id_utente_loggato]);
$cart_count = $stmtCart->fetchColumn();

// Conta preferiti (sarà uguale a item_count, ma lo facciamo per coerenza con index)
$fav_count = $item_count;

// Helper function
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
    <title>I tuoi Preferiti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .annuncio.nascosto {
            display: none !important;
        }

        .card-annuncio {
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 12px;
            overflow: hidden;
        }

        .card-annuncio:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .img-wrapper {
            height: 200px;
            overflow: hidden;
            background-color: #f8f9fa;
        }

        .img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Usiamo btn-preferiti come stile, ma qui sarà per rimuovere */
        .btn-preferiti {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border: none;
            z-index: 10;
        }

        .price-badge {
            font-size: 0.9rem;
            padding: 0.25rem 0.75rem;
        }

        [data-bs-theme="dark"] .card {
            background-color: #2d3748;
            border-color: #4a5568;
        }

        [data-bs-theme="dark"] .btn-preferiti {
            background-color: #374151;
            color: #e9ecef;
        }

        [data-bs-theme="dark"] .img-wrapper {
            background-color: #1f2937;
        }

        [data-bs-theme="dark"] .text-body,
        [data-bs-theme="dark"] .btn-link i,
        [data-bs-theme="dark"] .bi {
            color: #f8f9fa !important;
        }

        [data-bs-theme="dark"] .btn-dark {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        [data-bs-theme="dark"] .btn-outline-dark {
            color: #f8f9fa !important;
            border-color: #f8f9fa !important;
        }

        #cart-counter,
        #fav-counter,
        #cart-counter-header {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(25%, -25%);
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
            border-radius: 50%;
        }

        /* Stili per categorie (richiesti per i badge) */
        .categoria-libro {
            background-color: #e3f2fd !important;
            color: #1565c0 !important;
        }

        .categoria-appunti {
            background-color: #f3e5f5 !important;
            color: #7b1fa2 !important;
        }

        .categoria-digitale {
            background-color: #e8f5e8 !important;
            color: #2e7d32 !important;
        }

        .categoria-pdf {
            background-color: #f8f0f0 !important;
            color: #c62828 !important;
        }

        .categoria-materiale {
            background-color: #fff3e0 !important;
            color: #ef6c00 !important;
        }

        .categoria-altro {
            background-color: #f5f5f5 !important;
            color: #616161 !important;
        }

        [data-bs-theme="dark"] .categoria-libro {
            background-color: #1e3a5f !important;
            color: #90caf9 !important;
        }

        [data-bs-theme="dark"] .categoria-appunti {
            background-color: #4a1c5c !important;
            color: #e1bee7 !important;
        }

        [data-bs-theme="dark"] .categoria-digitale {
            background-color: #1b3a1b !important;
            color: #a5d6a7 !important;
        }

        [data-bs-theme="dark"] .categoria-pdf {
            background-color: #4a1c1c !important;
            color: #ff8a80 !important;
        }

        [data-bs-theme="dark"] .categoria-materiale {
            background-color: #5d4037 !important;
            color: #ffcc80 !important;
        }

        [data-bs-theme="dark"] .categoria-altro {
            background-color: #424242 !important;
            color: #e0e0e0 !important;
        }
    </style>
    <script>
        // Gestione tema
        (function () {
            const tema = localStorage.getItem('temaPreferito') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();
    </script>
</head>

<body class="bg-body">

    <!-- Header (semplificato ma coerente) -->
    <header class="d-flex align-items-center bg-body m-0 p-3 border-bottom sticky-top shadow-sm">
        <a href="index.php" class="btn btn-link text-body p-0 me-3"><i class="bi bi-arrow-left fs-4"></i></a>
        <div class="flex-grow-1">
            <div class="fw-bold fs-5">I tuoi Preferiti</div>
            <div class="text-muted small"><?php echo $item_count; ?> articolo/i salvati</div>
        </div>

        <a href="carrello.php" class="btn btn-link text-body position-relative">
            <i class="bi bi-cart fs-4"></i>
            <?php if ($cart_count > 0): ?>
                <span id="cart-counter-header" class="badge rounded-pill bg-danger">
                    <?php echo $cart_count; ?>
                </span>
            <?php endif; ?>
        </a>
    </header>

    <div class="container-fluid py-4 px-lg-5">
        <?php if ($item_count > 0): ?>
            <div class="row g-4 justify-content-center">
                <?php foreach ($favorite_items as $annuncio):
                    $categoria_lower = strtolower($annuncio['nome_categoria']);
                    $condizione_lower = strtolower($annuncio['nome_condizione']);
                    $classe_categoria = 'categoria-' . $categoria_lower;

                    // Calcolo tempo
                    $data_pubblicazione = date('d/m/Y', strtotime($annuncio['data_pubblicazione']));
                    $oggi = date('Y-m-d');
                    $data_pub = date('Y-m-d', strtotime($annuncio['data_pubblicazione']));
                    if ($data_pub == $oggi)
                        $tempo_pubblicazione = 'Oggi';
                    elseif ($data_pub == date('Y-m-d', strtotime('-1 day')))
                        $tempo_pubblicazione = 'Ieri';
                    else {
                        $differenza = (strtotime($oggi) - strtotime($data_pub)) / (60 * 60 * 24);
                        $tempo_pubblicazione = ($differenza < 7) ? floor($differenza) . ' giorni fa' : $data_pubblicazione;
                    }

                    $immagine_url = $annuncio['immagine_url'] ?? 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e';
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm card-annuncio">
                            <!-- Bottone Rimuovi preferiti -->
                            <button class="btn-preferiti remove-fav-btn" data-id="<?php echo $annuncio['id_annuncio']; ?>"
                                title="Rimuovi dai preferiti">
                                <i class="bi bi-x-lg text-danger"></i>
                            </button>

                            <div class="img-wrapper">
                                <img src="<?php echo htmlspecialchars($immagine_url); ?>"
                                    alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0 text-truncate" style="max-width: 70%;">
                                        <?php echo htmlspecialchars($annuncio['titolo']); ?>
                                    </h6>
                                    <span class="badge bg-primary-subtle text-primary price-badge">
                                        <?php echo format_currency($annuncio['prezzo']); ?>
                                    </span>
                                </div>
                                <p class="small text-muted mb-2 flex-grow-1">
                                    <?php echo htmlspecialchars(substr($annuncio['descrizione'], 0, 80) . (strlen($annuncio['descrizione']) > 80 ? '...' : '')); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge <?php echo $classe_categoria; ?> border">
                                        <?php if ($annuncio['is_digitale']): ?>
                                            <i class="bi bi-file-earmark-text me-1"></i>
                                        <?php else: ?>
                                            <i class="bi bi-book me-1"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($annuncio['nome_categoria']); ?>
                                    </span>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?php echo htmlspecialchars($annuncio['nome_facolta'] ?? 'N/A'); ?>
                                    </small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <div>
                                        <span class="badge 
                                            <?php
                                            if ($condizione_lower == 'nuovo')
                                                echo 'bg-info-subtle text-info';
                                            elseif ($condizione_lower == 'ottimo')
                                                echo 'bg-success-subtle text-success';
                                            elseif ($condizione_lower == 'buono')
                                                echo 'bg-warning-subtle text-warning';
                                            else
                                                echo 'bg-secondary-subtle text-secondary';
                                            ?>">
                                            <?php echo htmlspecialchars($annuncio['nome_condizione']); ?>
                                        </span>
                                        <small class="text-muted d-block mt-1"><?php echo $tempo_pubblicazione; ?></small>
                                    </div>
                                    <button class="btn btn-dark btn-sm px-3 rounded-pill add-to-cart-btn"
                                        data-id="<?php echo $annuncio['id_annuncio']; ?>">
                                        <i class="bi bi-cart-plus me-1"></i>Aggiungi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-suit-heart fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Non hai ancora aggiunto nulla ai preferiti.</p>
                <a href="index.php" class="btn btn-primary mt-3">Esplora annunci</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast -->
    <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">UniMarket</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message, isSuccess = true) {
            const toastEl = document.getElementById('liveToast');
            const toastBody = toastEl.querySelector('.toast-body');
            const toastHeader = toastEl.querySelector('.toast-header');
            toastBody.textContent = message;
            if (isSuccess) {
                toastHeader.classList.remove('text-danger');
                toastHeader.classList.add('text-success');
            } else {
                toastHeader.classList.remove('text-success');
                toastHeader.classList.add('text-danger');
            }
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        // Aggiungi al carrello
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                fetch('aggiungi_carrello.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_annuncio: id })
                })
                    .then(res => res.json())
                    .then(data => {
                        showToast(data.message, data.success);
                        if (data.count !== undefined) {
                            const counter = document.getElementById('cart-counter-header');
                            if (counter) counter.innerText = data.count;
                        }
                    })
                    .catch(err => showToast('Errore di comunicazione', false));
            });
        });

        // Rimuovi preferito (usando AJAX per fluidità)
        document.querySelectorAll('.remove-fav-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const cardCol = this.closest('.col-xl-3'); // Selettore della colonna

                fetch('rimuovi_preferiti.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_annuncio: id })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Rimuovi card con animazione
                            cardCol.style.transition = "opacity 0.3s";
                            cardCol.style.opacity = "0";
                            setTimeout(() => {
                                cardCol.remove();
                                // Se non ci sono più elementi, ricarica per mostrare "vuoto"
                                if (document.querySelectorAll('.card-annuncio').length === 0) {
                                    location.reload();
                                }
                            }, 300);
                            showToast("Rimosso dai preferiti.", true);
                        } else {
                            showToast(data.message, false);
                        }
                    })
                    .catch(err => showToast('Errore durante la rimozione', false));
            });
        });
    </script>
</body>

</html>