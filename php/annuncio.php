<?php
session_start();

// Configurazione database
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

// Verifica se è stato passato l'ID annuncio
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID annuncio non valido.");
}

$annuncio_id = (int)$_GET['id'];

// Recupera i dettagli dell'annuncio
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
        WHERE a.id_annuncio = :id 
        AND a.is_attivo = 1 
        AND a.is_venduto = 0";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $annuncio_id]);
$annuncio = $stmt->fetch();

if (!$annuncio) {
    die("Annuncio non trovato o non disponibile.");
}

// Formatta la data
$data_pubblicazione = date('d/m/Y H:i', strtotime($annuncio['data_pubblicazione']));

// Conta articoli nel carrello (se l'utente è loggato)
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM carrello WHERE utente_id = :user_id");
    $countStmt->execute(['user_id' => $_SESSION['user_id']]);
    $cart_count = $countStmt->fetchColumn();
}

// Verifica se l'utente è loggato
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'];
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($annuncio['titolo']); ?> - UniboMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .badge-custom {
            padding: 0.5em 1em;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .categoria-libro { background-color: #e3f2fd !important; color: #1565c0 !important; }
        .categoria-appunti { background-color: #f3e5f5 !important; color: #7b1fa2 !important; }
        .categoria-digitale { background-color: #e8f5e8 !important; color: #2e7d32 !important; }
        .categoria-pdf { background-color: #f8f0f0 !important; color: #c62828 !important; }
        .categoria-materiale { background-color: #fff3e0 !important; color: #ef6c00 !important; }
        .categoria-altro { background-color: #f5f5f5 !important; color: #616161 !important; }
        
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
        
        [data-bs-theme="dark"] .product-image {
            background-color: #2d3748;
        }
        
        [data-bs-theme="dark"] .seller-info {
            background-color: #2d3748;
        }
        
        [data-bs-theme="dark"] .categoria-libro { background-color: #1e3a5f !important; color: #90caf9 !important; }
        [data-bs-theme="dark"] .categoria-appunti { background-color: #4a1c5c !important; color: #e1bee7 !important; }
        [data-bs-theme="dark"] .categoria-digitale { background-color: #1b3a1b !important; color: #a5d6a7 !important; }
        [data-bs-theme="dark"] .categoria-pdf { background-color: #4a1c1c !important; color: #ff8a80 !important; }
        [data-bs-theme="dark"] .categoria-materiale { background-color: #5d4037 !important; color: #ffcc80 !important; }
        [data-bs-theme="dark"] .categoria-altro { background-color: #424242 !important; color: #e0e0e0 !important; }
        
        .btn-categoria {
            transition: all 0.2s;
        }
        
        .btn-categoria:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="sticky-top bg-body border-bottom shadow-sm">
        <div class="container-fluid p-3">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Logo e pulsante indietro -->
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-link text-body p-0 me-3">
                        <i class="bi bi-arrow-left fs-4"></i>
                    </a>
                    <div>
                        <h1 class="h5 fw-bold mb-0">UniboMarket</h1>
                    </div>
                </div>

                <!-- Azioni -->
                <div class="d-flex align-items-center gap-2">
                    <?php if ($is_logged_in): ?>
                        <!-- Carrello -->
                        <a href="carrello.php" class="btn btn-link text-body p-1 p-sm-2 position-relative">
                            <i class="bi bi-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="badge rounded-pill bg-danger position-absolute top-0 end-0" 
                                      style="transform: translate(25%, -25%); font-size: 0.65rem;">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Profilo -->
                        <div class="d-none d-md-flex align-items-center">
                            <span class="me-3 text-muted">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['nome'] . ' ' . $_SESSION['cognome']); ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <!-- Login/Registrati -->
                        <a href="login.php" class="btn btn-outline-dark d-none d-md-flex align-items-center px-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4">
        <div class="row g-4">
            <!-- Immagine prodotto -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <img src="<?php echo !empty($annuncio['immagine_url']) ? htmlspecialchars($annuncio['immagine_url']) : 'images/placeholder-book.png'; ?>" 
                             class="product-image" 
                             alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>">
                    </div>
                </div>
                
                <!-- Descrizione -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Descrizione</h5>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($annuncio['descrizione'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dettagli prodotto -->
            <div class="col-lg-5">
                <div class="sticky-sidebar">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <!-- Titolo e categoria -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge badge-custom categoria-<?php echo strtolower($annuncio['nome_categoria']); ?> mb-2">
                                        <?php if ($annuncio['is_digitale']): ?>
                                            <i class="bi bi-file-earmark-text me-1"></i>
                                        <?php else: ?>
                                            <i class="bi bi-book me-1"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($annuncio['nome_categoria']); ?>
                                    </span>
                                    <h2 class="h4 fw-bold"><?php echo htmlspecialchars($annuncio['titolo']); ?></h2>
                                </div>
                            </div>

                            <!-- Prezzo -->
                            <div class="mb-4">
                                <div class="h2 fw-bold text-primary mb-1">
                                    €<?php echo number_format($annuncio['prezzo'], 2); ?>
                                </div>
                                <small class="text-muted">IVA inclusa</small>
                            </div>

                            <!-- Dettagli -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Dettagli prodotto</h6>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="text-muted small">Condizione</div>
                                        <div class="fw-semibold">
                                            <?php 
                                            $condizione_lower = strtolower($annuncio['nome_condizione']);
                                            $condizione_class = '';
                                            if ($condizione_lower == 'nuovo') $condizione_class = 'text-info';
                                            elseif ($condizione_lower == 'ottimo') $condizione_class = 'text-success';
                                            elseif ($condizione_lower == 'buono') $condizione_class = 'text-warning';
                                            else $condizione_class = 'text-secondary';
                                            ?>
                                            <span class="<?php echo $condizione_class; ?>">
                                                <?php echo htmlspecialchars($annuncio['nome_condizione']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="text-muted small">Tipo</div>
                                        <div class="fw-semibold">
                                            <?php echo $annuncio['is_digitale'] ? 'Digitale' : 'Fisico'; ?>
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

                            <!-- Venditore -->
                            <div class="seller-info mb-4">
                                <h6 class="fw-bold mb-3">Informazioni venditore</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="bi bi-person-circle fs-2"></i>
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

                            <!-- Azioni -->
                            <div class="d-grid gap-3">
                                <button class="btn btn-dark btn-lg py-3 fw-bold aggiungi-carrello" 
                                        data-id="<?php echo $annuncio['id_annuncio']; ?>">
                                    <i class="bi bi-cart-plus me-2"></i>Aggiungi al carrello
                                </button>
                                
                                <button class="btn btn-outline-dark btn-lg py-3 btn-preferiti" 
                                        data-id="<?php echo $annuncio['id_annuncio']; ?>">
                                    <i class="bi bi-heart me-2"></i>Aggiungi ai preferiti
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
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
        // Gestione tema
        (function() {
            const tema = localStorage.getItem('temaPreferito') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();

        // Funzione per mostrare toast
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

        // Aggiungi al carrello
        document.querySelector('.aggiungi-carrello').addEventListener('click', function(e) {
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

        // Gestione preferiti
        document.querySelector('.btn-preferiti').addEventListener('click', function(e) {
            e.preventDefault();
            const id = '<?php echo $annuncio_id; ?>';
            const icon = this.querySelector('i');
            let preferiti = JSON.parse(localStorage.getItem('mieiPreferiti')) || [];
            
            const annuncioData = {
                id: id,
                title: '<?php echo addslashes($annuncio['titolo']); ?>',
                prezzo: <?php echo $annuncio['prezzo']; ?>,
                categoria: '<?php echo addslashes($annuncio['nome_categoria']); ?>',
                img: '<?php echo addslashes(!empty($annuncio['immagine_url']) ? $annuncio['immagine_url'] : 'images/placeholder-book.png'); ?>'
            };
            
            const existingIndex = preferiti.findIndex(p => p.id === id);
            
            if (existingIndex === -1) {
                preferiti.push(annuncioData);
                icon.className = 'bi bi-heart-fill me-2 text-danger';
                showToast('Annuncio aggiunto ai preferiti!', true);
            } else {
                preferiti.splice(existingIndex, 1);
                icon.className = 'bi bi-heart me-2';
                showToast('Annuncio rimosso dai preferiti.', false);
            }
            
            localStorage.setItem('mieiPreferiti', JSON.stringify(preferiti));
        });

        // Invia messaggio al venditore
        document.getElementById('inviaMessaggio')?.addEventListener('click', function() {
            const messaggio = document.getElementById('messaggio').value.trim();
            
            if (!messaggio) {
                alert('Per favore, scrivi un messaggio.');
                return;
            }
            
            
            
            showToast('Messaggio inviato al venditore!', true);
            document.getElementById('messaggio').value = '';
        });

        // Verifica se l'annuncio è già nei preferiti
        document.addEventListener('DOMContentLoaded', () => {
            const id = '<?php echo $annuncio_id; ?>';
            const preferiti = JSON.parse(localStorage.getItem('mieiPreferiti')) || [];
            const btnPreferiti = document.querySelector('.btn-preferiti');
            const icon = btnPreferiti.querySelector('i');
            
            if (preferiti.some(p => p.id === id)) {
                icon.className = 'bi bi-heart-fill me-2 text-danger';
            }
        });
    </script>
    
</body>

</html>