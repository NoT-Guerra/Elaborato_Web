<?php
session_start();

require_once __DIR__ . '/../../app/config/database.php';

// check login
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'];

// check se è stato passato ID dell'annuncio
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID annuncio non valido.");
}

$annuncio_id = (int) $_GET['id'];

// Rquery per recuperare l'annuncio e le sue info
$sql = "SELECT 
            a.*,
            cp.nome_categoria,
            cond.nome_condizione,
            f.nome_facolta,
            cs.nome_corso,
            u.nome as nome_venditore,
            u.cognome as cognome_venditore,
            u.email as email_venditore
        FROM annuncio a
        JOIN categoria_prodotto cp ON a.categoria_id = cp.id_categoria
        JOIN condizione_prodotto cond ON a.condizione_id = cond.id_condizione
        LEFT JOIN facolta f ON a.facolta_id = f.id_facolta
        LEFT JOIN corso_studio cs ON a.corso_id = cs.id_corso
        JOIN utenti u ON a.venditore_id = u.id_utente
        WHERE a.id_annuncio = ? 
        AND a.is_attivo = 1 
        AND a.is_venduto = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $annuncio_id);
$stmt->execute();
$result = $stmt->get_result();
$annuncio = $result->fetch_assoc();
$stmt->close();

if (!$annuncio) {
    die("Annuncio non trovato o non disponibile.");
}

$data_pubblicazione = date('d/m/Y H:i', strtotime($annuncio['data_pubblicazione']));

// conta articoli nel carrello
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM carrello WHERE utente_id = ?");
    $countStmt->bind_param("i", $_SESSION['user_id']);
    $countStmt->execute();
    $countStmt->bind_result($cart_count);
    $countStmt->fetch();
    $countStmt->close();
}

// check se è nei preferitii
$is_favorite = false;
if ($is_logged_in) {
    $stmtFav = $conn->prepare("SELECT COUNT(*) FROM preferiti WHERE utente_id = ? AND annuncio_id = ?");
    $stmtFav->bind_param("ii", $_SESSION['user_id'], $annuncio_id);
    $stmtFav->execute();
    $stmtFav->bind_result($favCountVal);
    $stmtFav->fetch();
    $is_favorite = $favCountVal > 0;
    $stmtFav->close();
}

// gestione preferiti
$fav_count = 0;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM preferiti WHERE utente_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fav_count);
    $stmt->fetch();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($annuncio['titolo']); ?> - UniboMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <style>
        .badge-custom {
            padding: 0.5em 1em;
            border-radius: 20px;
            font-size: 0.9rem;
        }

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

        .product-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }

        .seller-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }

        .description-box {
            white-space: pre-line;
            line-height: 1.6;
        }

        .sticky-sidebar {
            position: sticky;
            top: 20px;
        }

        /* gestione tema scuro */
        [data-bs-theme="dark"] .card {
            background-color: #1a202c;
            border-color: #2d3748;
        }

        [data-bs-theme="dark"] .product-image {
            background-color: #2d3748 !important;
        }

        [data-bs-theme="dark"] .seller-info {
            background-color: #2d3748 !important;
        }

        [data-bs-theme="dark"] body {
            background-color: #17191c;
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

        .btn-categoria {
            transition: all 0.2s;
        }

        .btn-categoria:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        [data-bs-theme="dark"] .btn-outline-custom-white {
            color: #fff;
            border-color: #fff;
            background-color: transparent;
        }

        [data-bs-theme="dark"] .btn-outline-custom-white:hover {
            background-color: #fff;
            color: #000;
        }

        .btn-outline-custom-white {
            color: #000;
            border-color: #000;
        }

        .btn-outline-custom-white:hover {
            background-color: #000;
            color: #fff;
        }

        [data-bs-theme="dark"] header .btn-dark {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        [data-bs-theme="dark"] header .btn-outline-dark {
            color: #fff !important;
            border-color: #fff !important;
        }

        [data-bs-theme="dark"] header .btn-outline-dark:hover {
            background-color: #fff !important;
            color: #000 !important;
        }

        #cart-counter-header,
        #fav-counter-header {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(25%, -25%);
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
            border-radius: 50%;
        }
    </style>
</head>

<body>
    <header class="sticky-top bg-body border-bottom shadow-sm">
        <div class="container-fluid p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="../index.php" class="btn btn-link text-body p-0 me-3" aria-label="Torna indietro">
                        <span class="bi bi-arrow-left fs-4" aria-hidden="true"></span>
                    </a>
                    <div>
                        <h1 class="h5 fw-bold mb-0">UniboMarket</h1>
                    </div>
                </div>

                <!-- azioni possibili -->
                <div class="d-flex align-items-center gap-2">
                    <?php if ($is_logged_in): ?>


                        <!-- profilo -->
                        <div class="d-none d-md-flex align-items-center">
                            <span class="me-3 text-muted">
                                <span class="bi bi-person-circle me-1" aria-hidden="true"></span>
                                <?php echo htmlspecialchars($_SESSION['nome'] . ' ' . $_SESSION['cognome']); ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <!-- login e registrati -->
                        <a href="../auth/login.php" class="btn btn-outline-dark d-none d-md-flex align-items-center px-3">
                            <span class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></span>Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <img src="<?php
                        $img_db = $annuncio['immagine_url'];
                        if (!empty($img_db)) {
                            if (str_starts_with($img_db, 'http')) {
                                $imgUrl = $img_db;
                            } else {
                                $imgUrl = '../assets/img/' . basename($img_db);
                            }
                        } else {
                            $imgUrl = 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=600';
                        }
                        echo htmlspecialchars($imgUrl); ?>" class="product-image"
                            alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>"/>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Descrizione</h2>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($annuncio['descrizione'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="sticky-sidebar">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <!-- titolo e categoria -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span
                                        class="badge badge-custom categoria-<?php echo strtolower($annuncio['nome_categoria']); ?> mb-2">
                                        <span class="bi bi-book me-1" aria-hidden="true"></span>
                                        <?php echo htmlspecialchars($annuncio['nome_categoria']); ?>
                                    </span>
                                    <h2 class="h4 fw-bold"><?php echo htmlspecialchars($annuncio['titolo']); ?></h2>
                                </div>
                            </div>

                            <!-- prezzo -->
                            <div class="mb-4">
                                <div class="h2 fw-bold text-primary mb-1">
                                    €<?php echo number_format($annuncio['prezzo'], 2); ?>
                                </div>
                                <small class="text-muted">IVA inclusa</small>
                            </div>

                            <!-- dettagli -->
                            <div class="mb-4">
                                <h3 class="h6 fw-bold mb-3">Dettagli prodotto</h3>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="text-muted small">Condizione</div>
                                        <div class="fw-semibold">
                                            <?php
                                            $condizione_lower = strtolower($annuncio['nome_condizione']);
                                            $condizione_class = '';
                                            if ($condizione_lower == 'nuovo')
                                                $condizione_class = 'text-info';
                                            elseif ($condizione_lower == 'ottimo')
                                                $condizione_class = 'text-success';
                                            elseif ($condizione_lower == 'buono')
                                                $condizione_class = 'text-warning';
                                            else
                                                $condizione_class = 'text-secondary';
                                            ?>
                                            <span class="<?php echo $condizione_class; ?>">
                                                <?php echo htmlspecialchars($annuncio['nome_condizione']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="text-muted small">Tipo</div>
                                        <div class="fw-semibold">
                                            <?php echo (strtolower($annuncio['nome_categoria']) === 'pdf') ? 'Digitale' : 'Fisico'; ?>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="text-muted small">Facoltà</div>
                                        <div class="fw-semibold">
                                            <?php echo htmlspecialchars($annuncio['nome_facolta'] ?? 'Non specificata'); ?>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="text-muted small">Corso</div>
                                        <div class="fw-semibold">
                                            <?php echo htmlspecialchars($annuncio['nome_corso'] ?? 'Non specificato'); ?>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="text-muted small">Pubblicato il</div>
                                        <div class="fw-semibold"><?php echo $data_pubblicazione; ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- gestione del venditore -->
                            <div class="seller-info mb-4">
                                <h3 class="h6 fw-bold mb-3">Informazioni venditore</h3>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <span class="bi bi-person-circle fs-2" aria-hidden="true"></span>
                                    </div>
                                    <div>
                                        <div class="fw-bold">
                                            <?php echo htmlspecialchars($annuncio['nome_venditore'] . ' ' . $annuncio['cognome_venditore']); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($annuncio['email_venditore']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-3">
                                <button class="btn btn-primary btn-lg py-3 fw-bold aggiungi-carrello"
                                    data-id="<?php echo $annuncio['id_annuncio']; ?>">
                                    <span class="bi bi-cart-plus me-2" aria-hidden="true"></span>Aggiungi al carrello
                                </button>

                                <button class="btn btn-outline-custom-white btn-lg py-3 btn-preferiti"
                                    data-id="<?php echo $annuncio['id_annuncio']; ?>">
                                    <?php if ($is_favorite): ?>
                                        <span class="bi bi-suit-heart-fill me-2 text-danger"
                                            aria-hidden="true"></span>Rimuovi
                                        dai preferiti
                                    <?php else: ?>
                                        <span class="bi bi-suit-heart me-2" aria-hidden="true"></span>Aggiungi ai preferiti
                                    <?php endif; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">UniboMarket</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Notifica
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // gestione tema
        (function () {
            const tema = localStorage.getItem('temaPreferito') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();

        // funzione per mostrare humburger
        function showToast(message, isSuccess = true) {
            const toastEl = document.getElementById('liveToast');
            if (!toastEl) return;

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

            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }

        // aggiunti al carrello
        document.querySelector('.aggiungi-carrello').addEventListener('click', function (e) {
            e.preventDefault();
            const idAnnuncio = this.dataset.id;

            fetch('aggiungi_carrello.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_annuncio: idAnnuncio })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, true);
                    } else {
                        showToast(data.message, false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Errore di comunicazione con il server.', false);
                });
        });

        // gestione preferiti
        document.querySelector('.btn-preferiti').addEventListener('click', function (e) {
            e.preventDefault();
            const id = '<?php echo $annuncio_id; ?>';
            const btn = this;
            const icon = btn.querySelector('span.bi');

            // check Verifica stato attuale (icona)
            const isAdded = icon.classList.contains('bi-heart-fill');
            const url = isAdded ? '../user/rimuovi_preferiti.php' : '../user/aggiungi_preferiti.php';

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_annuncio: id })
            })
                .then(res => res.json())

                .then(data => {
                    if (data.success) {
                        // aggiorna contatore header
                        const counter = document.getElementById('fav-counter-header');
                        if (data.count !== undefined && counter) {
                            counter.textContent = data.count;
                            if (data.count > 0) counter.classList.remove('d-none');
                            else counter.classList.add('d-none');
                        }

                        if (isAdded) {
                            icon.className = 'bi bi-heart me-2';
                            btn.innerHTML = '<span class="bi bi-heart me-2" aria-hidden="true"></span>Aggiungi ai preferiti';
                            showToast('Rimosso dai preferiti.', true);
                        } else {
                            icon.className = 'bi bi-heart-fill me-2 text-danger';
                            btn.innerHTML = '<span class="bi bi-heart-fill me-2 text-danger" aria-hidden="true"></span>Rimuovi dai preferiti';
                            showToast('Aggiunto ai preferiti!', true);
                        }
                    } else {
                        showToast(data.message || 'Errore operazione', false);
                    }
                })
                .catch(err => showToast('Errore di connessione', false));
        });

        // check caricamento
        document.addEventListener('DOMContentLoaded', () => {
            const id = '<?php echo $annuncio_id; ?>';
        });
    </script>

</body>

</html>