<?php
// Inizia la sessione
session_start();

// Connessione al database
require_once 'config/database.php';

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header('Location: admin.php');
    exit;
}

// Query per ottenere gli annunci con le relative informazioni
$sql = "SELECT 
                a.id_annuncio,
                a.titolo,
                a.descrizione,
                a.prezzo,
                a.data_pubblicazione,
                a.is_digitale,
                a.immagine_url,
                a.is_attivo,
                a.is_venduto,
                cp.nome_categoria,
                cond.nome_condizione,
                f.nome_facolta,
                cs.nome_corso,
                u.nome as nome_venditore,
                u.cognome as cognome_venditore
            FROM annuncio a
            JOIN categoria_prodotto cp ON a.categoria_id = cp.id_categoria
            JOIN condizione_prodotto cond ON a.condizione_id = cond.id_condizione
            LEFT JOIN facolta f ON a.facolta_id = f.id_facolta
            LEFT JOIN corso_studio cs ON a.corso_id = cs.id_corso
            JOIN utenti u ON a.venditore_id = u.id_utente
            WHERE a.is_attivo = 1 AND a.is_venduto = 0
            ORDER BY a.data_pubblicazione DESC";

$result = $conn->query($sql);
$annunci = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $annunci[] = $row;
    }
}

// Ottieni categorie distinte per i filtri
$sql_categorie = "SELECT nome_categoria FROM categoria_prodotto";
$result_categorie = $conn->query($sql_categorie);
$categorie = [];
if ($result_categorie && $result_categorie->num_rows > 0) {
    while ($row = $result_categorie->fetch_assoc()) {
        $categorie[] = $row['nome_categoria'];
    }
}

// Ottieni facoltà distinte per i filtri
$sql_facolta = "SELECT nome_facolta FROM facolta";
$result_facolta = $conn->query($sql_facolta);
$facolta_list = [];
if ($result_facolta && $result_facolta->num_rows > 0) {
    while ($row = $result_facolta->fetch_assoc()) {
        $facolta_list[] = $row['nome_facolta'];
    }
}

// Ottieni condizioni distinte per i filtri
$sql_condizioni = "SELECT nome_condizione FROM condizione_prodotto";
$result_condizioni = $conn->query($sql_condizioni);
$condizioni_list = [];
if ($result_condizioni && $result_condizioni->num_rows > 0) {
    while ($row = $result_condizioni->fetch_assoc()) {
        $condizioni_list[] = $row['nome_condizione'];
    }
}

// Verifica se l'utente è loggato
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'];
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniboMarket - Home</title>
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

        .filter-btn {
            transition: all 0.2s ease;
        }

        .filter-btn.active {
            background-color: var(--bs-primary) !important;
            color: white !important;
            border-color: var(--bs-primary) !important;
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

        [data-bs-theme="dark"] .btn-outline-dark:hover {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }

        [data-bs-theme="dark"] #btn-tema {
            color: #f8f9fa;
            border-color: #4a5568;
        }

        [data-bs-theme="dark"] .btn-outline-dark:hover i {
            color: #212529 !important;
        }

        .bi-list {
            vertical-align: middle;
            line-height: 1;
        }

        [data-bs-theme="dark"] .offcanvas {
            background-color: #1a202c;
            color: #f8f9fa;
        }

        [data-bs-theme="dark"] .list-group-item {
            background-color: transparent;
            color: #cbd5e0;
        }

        [data-bs-theme="dark"] .list-group-item:hover {
            background-color: #2d3748;
            color: white;
        }

        .offcanvas {
            z-index: 2000 !important;
        }

        .offcanvas-backdrop {
            z-index: 1999 !important;
        }

        .position-relative {
            position: relative;
        }

        #cart-counter,
        #cart-counter-header {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(25%, -25%);
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
            border-radius: 50%;
        }

        /* Stili per categorie */
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

        /* Stili per tema scuro */
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

        /* Classe generica per categorie non specificate */
        .badge[class*="categoria-"]:not(.categoria-libro):not(.categoria-appunti):not(.categoria-digitale):not(.categoria-pdf):not(.categoria-materiale):not(.categoria-altro) {
            background-color: #e8e8e8 !important;
            color: #333 !important;
        }

        [data-bs-theme="dark"] .badge[class*="categoria-"]:not(.categoria-libro):not(.categoria-appunti):not(.categoria-digitale):not(.categoria-pdf):not(.categoria-materiale):not(.categoria-altro) {
            background-color: #4a5568 !important;
            color: #f8f9fa !important;
        }

        /* --- RIMUOVI SCORRIMENTO ORIZZONTALE SU SCHERMI PICCOLI --- */
        @media (max-width: 767.98px) {
            .container-fluid {
                overflow-x: hidden !important;
            }

            /* Rimuovi overflow-auto dalle categorie su mobile */
            .d-flex.gap-2.overflow-auto {
                overflow-x: auto !important;
                /* Mantieni lo scorrimento solo per i bottoni categorie */
                flex-wrap: nowrap;
                padding-bottom: 5px;
                /* Spazio per scrollbar */
            }

            body {
                overflow-x: hidden !important;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header class="sticky-top bg-body border-bottom shadow-sm">
        <div class="container-fluid p-2 p-sm-3">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Logo -->
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center me-2 me-sm-3"
                        style="width: 48px; height: 48px;">
                        <i class="bi bi-book text-white fs-3"></i>
                    </div>
                    <div>
                        <h1 class="h5 fw-bold mb-0">UniboMarket</h1>
                        <p class="text-muted small mb-0 d-none d-md-block">Marketplace per studenti</p>
                    </div>
                </div>

                <!-- Azioni -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Bottone Preferiti - visibile solo su schermi medi e grandi -->
                    <a href="preferiti.php"
                        class="btn btn-link text-body p-1 p-sm-2 position-relative d-none d-sm-flex">
                        <i class="bi bi-suit-heart"></i>
                        <span id="cart-counter" class="badge rounded-pill bg-danger d-none">0</span>
                    </a>

                    <!-- Bottone Carrello - visibile solo su schermi medi e grandi -->
                    <a href="carrello.php" class="btn btn-link text-body p-1 p-sm-2 position-relative d-none d-sm-flex">
                        <i class="bi bi-cart"></i>
                        <span id="cart-counter-header" class="badge rounded-pill bg-danger d-none">0</span>
                    </a>

                    <?php if ($is_logged_in): ?>
                        <!-- Se l'utente è loggato, mostra Logout e Nome utente -->
                        <div class="d-none d-md-flex align-items-center">
                            <span class="me-3 text-muted">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['nome'] . ' ' . $_SESSION['cognome']); ?>
                            </span>
                            <a href="logout.php" class="btn btn-outline-dark d-flex align-items-center px-3">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Se l'utente NON è loggato, mostra Login e Registrati -->
                        <a href="login.php" class="btn btn-outline-dark d-none d-md-flex align-items-center px-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                        <a id="btn-register" href="register.php"
                            class="btn btn-outline-dark d-none d-md-flex align-items-center gap-2 px-2 px-sm-3">
                            <i class="bi bi-person-add"></i>
                            <span class="ms-1">Registrati</span>
                        </a>
                    <?php endif; ?>

                    <!-- Bottone Pubblica - già responsive -->
                    <a href="pubblica.php" class="btn btn-dark d-flex align-items-center justify-content-center px-3">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-md-inline ms-2">Pubblica</span>
                    </a>

                    <!-- Bottone Tema - rimane sempre visibile -->
                    <button id="btn-tema" class="btn btn-outline-secondary">
                        <i id="icona-luna" class="bi bi-moon"></i>
                        <i id="icona-sole" class="bi bi-sun d-none"></i>
                    </button>

                    <!-- Bottone menu a tendina - rimane sempre visibile -->
                    <button class="btn btn-link text-body p-0 ms-2" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#menuMobile">
                        <i class="bi bi-list fs-2"></i>
                    </button>
                </div>
            </div>

            <!-- Menù a tendina -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="menuMobile" aria-labelledby="menuMobileLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title fw-bold" id="menuMobileLabel">UniboMarket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div class="p-3 d-grid gap-2">
                        <?php if ($is_logged_in): ?>
                            <!-- Se loggato, mostra Logout e nome utente nel menu mobile -->
                            <div class="text-center mb-2">
                                <i class="bi bi-person-circle fs-2 mb-2"></i>
                                <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['nome'] . ' ' . $_SESSION['cognome']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($_SESSION['email']); ?></small>
                            </div>
                            <a href="logout.php"
                                class="btn btn-dark w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        <?php else: ?>
                            <!-- Se NON loggato, mostra Login e Registrati nel menu mobile -->
                            <a href="login.php"
                                class="btn btn-dark w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                            <a href="register.php"
                                class="btn btn-outline-dark w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-person-add"></i> Registrati
                            </a>
                        <?php endif; ?>
                    </div>

                    <hr class="my-0 opacity-10">

                    <div class="list-group list-group-flush">
                        <a href="preferiti.php" class="list-group-item list-group-item-action border-0 py-3 px-4">
                            <i class="bi bi-suit-heart me-3"></i> Preferiti
                        </a>
                        <a href="carrello.php" class="list-group-item list-group-item-action border-0 py-3 px-4">
                            <i class="bi bi-cart me-3"></i> Carrello
                        </a>
                        <hr class="my-0 opacity-10">
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <a href="admin.php" class="list-group-item list-group-item-action border-0 py-3 px-4">
                                <i class="bi bi-shield-lock me-3"></i> Pannello Admin
                            </a>
                        <?php endif; ?>
                        <a href="#" class="list-group-item list-group-item-action border-0 py-3 px-4">
                            <i class="bi bi-question-circle me-3"></i> Aiuto e Supporto
                        </a>
                    </div>
                </div>
            </div>

            <!-- Categorie -->
            <div class="mt-3 pt-2 border-top">
                <div class="d-flex gap-2 overflow-auto" id="category-filters">
                    <button class="btn btn-sm btn-primary rounded-pill px-3 filter-btn active" data-category="tutti"
                        id="filter-all">Tutti</button>
                    <?php foreach ($categorie as $categoria): ?>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn"
                            data-category="<?php echo strtolower($categoria); ?>">
                            <?php echo htmlspecialchars($categoria); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ricerca -->
            <div class="mt-3">
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Cerca libri o appunti...">
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4">
        <div class="row g-4">
            <!-- Filtri -->
            <aside class="col-lg-3 col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-2"></i>Filtri</h6>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Facoltà</label>
                            <select id="filterFacolta" class="form-select form-select-sm">
                                <option value="">Tutte le facoltà</option>
                                <?php foreach ($facolta_list as $facolta): ?>
                                    <option value="<?php echo strtolower($facolta); ?>">
                                        <?php echo htmlspecialchars($facolta); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Condizione</label>
                            <select id="filterCondizioni" class="form-select form-select-sm">
                                <option value="">Tutte le condizioni</option>
                                <?php foreach ($condizioni_list as $condizione): ?>
                                    <option value="<?php echo strtolower($condizione); ?>">
                                        <?php echo htmlspecialchars($condizione); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Prezzo max: <span id="prezzoValore"
                                    class="text-primary">100€</span></label>
                            <input type="range" id="filterPrezzo" class="form-range" min="0" max="150" step="5"
                                value="100">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>0€</span>
                                <span>150€</span>
                            </div>
                        </div>

                        <button class="btn btn-outline-secondary w-100 mt-2" onclick="resetFiltri()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset filtri
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Annunci -->
            <section class="col-lg-9 col-md-8">
                <div class="row g-4" id="lista-annunci">
                    <?php if (count($annunci) > 0): ?>
                        <?php foreach ($annunci as $annuncio):
                            $categoria_lower = strtolower($annuncio['nome_categoria']);
                            $facolta_lower = strtolower($annuncio['nome_facolta'] ?? '');
                            $condizione_lower = strtolower($annuncio['nome_condizione']);
                            $classe_categoria = 'categoria-' . $categoria_lower;

                            // Formatta la data
                            $data_pubblicazione = date('d/m/Y', strtotime($annuncio['data_pubblicazione']));
                            $oggi = date('Y-m-d');
                            $data_pub = date('Y-m-d', strtotime($annuncio['data_pubblicazione']));

                            if ($data_pub == $oggi) {
                                $tempo_pubblicazione = 'Oggi';
                            } elseif ($data_pub == date('Y-m-d', strtotime('-1 day'))) {
                                $tempo_pubblicazione = 'Ieri';
                            } else {
                                $differenza = (strtotime($oggi) - strtotime($data_pub)) / (60 * 60 * 24);
                                if ($differenza < 7) {
                                    $tempo_pubblicazione = floor($differenza) . ' giorni fa';
                                } else {
                                    $tempo_pubblicazione = $data_pubblicazione;
                                }
                            }

                            // URL immagine di default se non presente
                            $immagine_url = $annuncio['immagine_url'] ?? 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=600';
                            ?>
                            <div class="col-xl-4 col-lg-6 annuncio"
                                data-title="<?php echo htmlspecialchars($annuncio['titolo']); ?>"
                                data-facolta="<?php echo $facolta_lower; ?>" data-condizione="<?php echo $condizione_lower; ?>"
                                data-prezzo="<?php echo $annuncio['prezzo']; ?>"
                                data-categoria="<?php echo $categoria_lower; ?>">
                                <div class="card h-100 border-0 shadow-sm card-annuncio">
                                    <button class="btn-preferiti" data-id="<?php echo $annuncio['id_annuncio']; ?>">
                                        <i class="bi bi-suit-heart"></i>
                                    </button>
                                    <div class="img-wrapper">
                                        <img src="<?php echo $immagine_url; ?>"
                                            alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>">
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-0 text-truncate">
                                                <?php echo htmlspecialchars($annuncio['titolo']); ?>
                                            </h6>
                                            <span class="badge bg-primary-subtle text-primary price-badge">
                                                €<?php echo number_format($annuncio['prezzo'], 2); ?>
                                            </span>
                                        </div>
                                        <p class="small text-muted mb-2">
                                            <?php echo htmlspecialchars(substr($annuncio['descrizione'], 0, 100) . (strlen($annuncio['descrizione']) > 100 ? '...' : '')); ?>
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
                                        <div class="d-flex justify-content-between align-items-center">
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
                                                <small
                                                    class="text-muted d-block mt-1"><?php echo $tempo_pubblicazione; ?></small>
                                            </div>
                                            <button class="btn btn-dark btn-sm px-3 rounded-pill aggiungi-carrello"
                                                data-id="<?php echo $annuncio['id_annuncio']; ?>">
                                                <i class="bi bi-cart-plus me-1"></i>Aggiungi
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-binoculars fs-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted">Nessun annuncio disponibile</h5>
                            <p class="text-muted">Sii il primo a pubblicare un annuncio!</p>
                            <a href="pubblica.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Pubblica annuncio
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-body pt-3 pb-3 border-top">
        <div class="container-fluid px-3 px-lg-5">
            <div class="row">
                <div class="col-12 text-start mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center me-2 me-sm-3"
                            style="width: 48px; height: 48px;">
                            <i class="bi bi-book text-white fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-body">UniMarket</h5>
                    </div>
                    <div class="text-body ms-1">Il marketplace dedicato agli studenti universitari per comprare e
                        vendere libri e appunti in modo semplice e sicuro.</div>
                </div>

                <div class="col-md-4 col-12 mb-3 mb-md-0 text-md-center">
                    <h5 class="fw-bold mb-3 text-start text-md-center">Link Rapidi</h5>
                    <ul class="list-unstyled text-start text-md-center p-0">
                        <li class="mb-2"><a href="#" class="text-body text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="#" class="text-body text-decoration-none">Chi Siamo</a></li>
                        <li class="mb-2"><a href="#" class="text-body text-decoration-none">FAQ</a></li>
                    </ul>
                </div>

                <div class="col-md-4 col-12 mb-3 mb-md-0 text-md-center">
                    <h5 class="fw-bold mb-3 text-start text-md-center">Contatti</h5>
                    <ul class="list-unstyled text-start text-md-center p-0">
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:info@unimarket.com"
                                class="text-body text-decoration-none">info@unimarket.com</a></li>
                        <li class="mb-2"><i class="bi bi-phone me-2"></i><a href="tel:+391234567890"
                                class="text-body text-decoration-none">+39 123 4567890</a></li>
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Via Cesare Pavese, 50, 47521 Cesena FC</li>
                    </ul>
                </div>

                <div class="col-md-4 col-12 mb-3 mb-md-0 text-md-center">
                    <h5 class="fw-bold mb-3 text-center text-md-center">Seguici</h5>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="text-body fs-4"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-body fs-4"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-body fs-4"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-body fs-4"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>

            <hr class="my-4 border-secondary">

            <div class="row">
                <div class="col-12 text-center small text-muted">
                    &copy; <?php echo date('Y'); ?> UniMarket. Tutti i diritti riservati.
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // --- Gestione Tema ---
        const btnTema = document.getElementById('btn-tema');
        const iconaLuna = document.getElementById('icona-luna');
        const iconaSole = document.getElementById('icona-sole');

        function applicaTema(tema) {
            document.documentElement.setAttribute('data-bs-theme', tema);
            localStorage.setItem('temaPreferito', tema);
            if (tema === 'dark') {
                iconaLuna.classList.add('d-none');
                iconaSole.classList.remove('d-none');
            } else {
                iconaLuna.classList.remove('d-none');
                iconaSole.classList.add('d-none');
            }
        }

        btnTema.addEventListener('click', () => {
            const nuovoTema = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            applicaTema(nuovoTema);
        });

        // --- Filtri ---
        const searchInput = document.getElementById('searchInput');
        const filterFacolta = document.getElementById('filterFacolta');
        const filterCondizioni = document.getElementById('filterCondizioni');
        const filterPrezzo = document.getElementById('filterPrezzo');
        const prezzoValore = document.getElementById('prezzoValore');
        const filterBtns = document.querySelectorAll('.filter-btn');

        function filtraAnnunci() {
            const query = searchInput.value.toLowerCase();
            const facolta = filterFacolta.value;
            const condizione = filterCondizioni.value;
            const prezzoMax = parseInt(filterPrezzo.value);
            const categoriaAttiva = document.querySelector('.filter-btn.active')?.dataset.category || 'tutti';
            prezzoValore.textContent = prezzoMax + "€";

            document.querySelectorAll('.annuncio').forEach(annuncio => {
                const titolo = annuncio.dataset.title.toLowerCase();
                const annFacolta = annuncio.dataset.facolta;
                const annCondizione = annuncio.dataset.condizione;
                const prezzo = parseInt(annuncio.dataset.prezzo);
                const categoria = annuncio.dataset.categoria;

                const matchTitolo = !query || titolo.includes(query);
                const matchFacolta = !facolta || facolta === annFacolta;
                const matchCondizione = !condizione || condizione === annCondizione;
                const matchPrezzo = prezzo <= prezzoMax;
                const matchCategoria = categoriaAttiva === 'tutti' || categoriaAttiva === categoria;

                annuncio.classList.toggle('nascosto', !(matchTitolo && matchFacolta && matchCondizione && matchPrezzo && matchCategoria));
            });
        }

        // Gestione dei bottoni di filtro categorie
        const categoryContainer = document.getElementById('category-filters');
        const filterAllBtn = document.getElementById('filter-all');

        // Event delegation per i bottoni di categoria
        categoryContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('filter-btn')) {
                const clickedCategory = e.target.getAttribute('data-category');

                // Rimuove le classi attive da tutti i bottoni
                filterBtns.forEach(btn => {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-outline-secondary');
                });

                // Aggiunge le classi attive al bottone cliccato
                e.target.classList.remove('btn-outline-secondary');
                e.target.classList.add('active', 'btn-primary');

                // Applica il filtro
                filtraAnnunci();
            }
        });

        function resetFiltri() {
            searchInput.value = '';
            filterFacolta.value = '';
            filterCondizioni.value = '';
            filterPrezzo.value = 100;
            prezzoValore.textContent = '100€';

            // Ripristina solo il bottone "Tutti" come attivo
            filterBtns.forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
                btn.classList.add('btn-outline-secondary');
                if (btn.dataset.category === 'tutti') {
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('active', 'btn-primary');
                }
            });

            filtraAnnunci();
        }

        // Listener per i filtri
        searchInput.addEventListener('input', filtraAnnunci);
        filterFacolta.addEventListener('change', filtraAnnunci);
        filterCondizioni.addEventListener('change', filtraAnnunci);
        filterPrezzo.addEventListener('input', filtraAnnunci);

        // --- Preferiti ---
        function aggiornaContatorePreferiti() {
            const preferiti = JSON.parse(localStorage.getItem('mieiPreferiti')) || [];
            const counter = document.getElementById('cart-counter');
            if (preferiti.length > 0) {
                counter.textContent = preferiti.length;
                counter.classList.remove('d-none');
            } else {
                counter.classList.add('d-none');
            }
        }

        document.querySelectorAll('.btn-preferiti').forEach(btn => {
            const annuncioEl = btn.closest('.annuncio');
            const id = annuncioEl.dataset.title;
            let preferiti = JSON.parse(localStorage.getItem('mieiPreferiti')) || [];

            if (preferiti.some(p => p.title === id)) {
                btn.querySelector('i').className = 'bi bi-suit-heart-fill text-danger';
            }

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const icon = this.querySelector('i');
                const annuncio = this.closest('.annuncio');

                let preferitiAttuali = JSON.parse(localStorage.getItem('mieiPreferiti')) || [];
                const annuncioData = {
                    id: annuncio.dataset.id || annuncio.querySelector('.btn-preferiti').dataset.id,
                    title: annuncio.dataset.title,
                    facolta: annuncio.dataset.facolta,
                    condizione: annuncio.dataset.condizione,
                    prezzo: annuncio.dataset.prezzo,
                    categoria: annuncio.dataset.categoria,
                    img: annuncio.querySelector('img').src,
                    desc: annuncio.querySelector('p.small').textContent
                };

                if (icon.classList.contains('bi-suit-heart')) {
                    icon.className = 'bi bi-suit-heart-fill text-danger';
                    preferitiAttuali.push(annuncioData);
                } else {
                    icon.className = 'bi bi-suit-heart';
                    preferitiAttuali = preferitiAttuali.filter(p => p.title !== annuncioData.title);
                }

                localStorage.setItem('mieiPreferiti', JSON.stringify(preferitiAttuali));
                aggiornaContatorePreferiti();
            });
        });

        // --- Carrello ---
        function aggiornaContatoreCarrello() {
            const carrello = JSON.parse(localStorage.getItem('mioCarrello')) || [];
            const counter = document.getElementById('cart-counter-header');

            if (carrello.length > 0) {
                counter.textContent = carrello.length;
                counter.classList.remove('d-none');
            } else {
                counter.classList.add('d-none');
            }
        }

        document.querySelectorAll('.aggiungi-carrello').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const annuncio = this.closest('.annuncio');
                const annuncioData = {
                    id: this.dataset.id,
                    title: annuncio.dataset.title,
                    prezzo: annuncio.dataset.prezzo,
                    img: annuncio.querySelector('img').src,
                    categoria: annuncio.dataset.categoria,
                    desc: annuncio.querySelector('p.small').textContent
                };

                let carrello = JSON.parse(localStorage.getItem('mioCarrello')) || [];
                // Verifica se l'annuncio è già nel carrello
                const esiste = carrello.some(item => item.id === annuncioData.id);
                if (!esiste) {
                    carrello.push(annuncioData);
                    localStorage.setItem('mioCarrello', JSON.stringify(carrello));
                    aggiornaContatoreCarrello();
                    alert('Prodotto aggiunto al carrello!');
                } else {
                    alert('Questo prodotto è già nel tuo carrello!');
                }
            });
        });

        // --- Inizializzazione ---
        document.addEventListener('DOMContentLoaded', () => {
            const temaSalvato = localStorage.getItem('temaPreferito') || 'light';
            applicaTema(temaSalvato);
            aggiornaContatorePreferiti();
            aggiornaContatoreCarrello();
        });
    </script>
</body>

</html>