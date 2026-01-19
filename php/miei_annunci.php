<?php
session_start();
require_once 'config/database.php';

// Controlla se l'utente è loggato
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Gestione eliminazione annuncio
if (isset($_GET['elimina']) && is_numeric($_GET['elimina'])) {
    $annuncio_id = $_GET['elimina'];

    // Verifica che l'annuncio appartenga all'utente
    $check_stmt = $conn->prepare("SELECT venditore_id FROM annuncio WHERE id_annuncio = ?");
    $check_stmt->bind_param("i", $annuncio_id);
    $check_stmt->execute();
    $check_stmt->bind_result($venditore_id);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($venditore_id == $user_id) {
        // Elimina l'annuncio
        $delete_stmt = $conn->prepare("DELETE FROM annuncio WHERE id_annuncio = ?");
        $delete_stmt->bind_param("i", $annuncio_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        $success_message = "Annuncio eliminato con successo!";
    } else {
        $error_message = "Non hai i permessi per eliminare questo annuncio.";
    }
}

// Query per ottenere gli annunci dell'utente
$sql = "SELECT 
            a.id_annuncio,
            a.titolo,
            a.descrizione,
            a.prezzo,
            a.data_pubblicazione,
            a.data_modifica,
            a.data_pubblicazione,
            a.data_modifica,
            a.immagine_url,
            a.is_attivo,
            a.is_venduto,
            cp.nome_categoria,
            cond.nome_condizione,
            f.nome_facolta,
            cs.nome_corso
        FROM annuncio a
        JOIN categoria_prodotto cp ON a.categoria_id = cp.id_categoria
        JOIN condizione_prodotto cond ON a.condizione_id = cond.id_condizione
        LEFT JOIN facolta f ON a.facolta_id = f.id_facolta
        LEFT JOIN corso_studio cs ON a.corso_id = cs.id_corso
        WHERE a.venditore_id = ?
        ORDER BY a.data_pubblicazione DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$annunci = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $annunci[] = $row;
    }
}
$stmt->close();

// Ottieni numero di articoli nel carrello
$cart_count = 0;
$cart_stmt = $conn->prepare("SELECT COUNT(*) FROM carrello WHERE utente_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_stmt->bind_result($cart_count);
$cart_stmt->fetch();
$cart_stmt->fetch();
$cart_stmt->close();

// Ottieni numero di articoli nei preferiti
$fav_count = 0;
$fav_stmt = $conn->prepare("SELECT COUNT(*) FROM preferiti WHERE utente_id = ?");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$fav_stmt->bind_result($fav_count);
$fav_stmt->fetch();
$fav_stmt->close();

$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'];
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniboMarket - I miei annunci</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
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

        .status-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }

        .action-buttons {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            display: flex;
            gap: 5px;
        }

        .btn-action {
            background: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border: none;
        }

        [data-bs-theme="dark"] .btn-action {
            background-color: #374151;
            color: #e9ecef;
        }

        .empty-state {
            padding: 4rem 1rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            opacity: 0.3;
        }

        .price-badge {
            font-size: 0.9rem;
            padding: 0.25rem 0.75rem;
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

        /* Modal di conferma */
        .modal-confirm {
            color: #636363;
            width: 400px;
        }

        .modal-confirm .modal-content {
            padding: 20px;
            border-radius: 5px;
            border: none;
        }

        .modal-confirm .modal-header {
            border-bottom: none;
            position: relative;
        }

        .modal-confirm h4 {
            text-align: center;
            font-size: 26px;
            margin: 30px 0 -10px;
        }

        .modal-confirm .modal-body {
            color: #999;
        }

        .modal-confirm .modal-footer {
            border: none;
            text-align: center;
            border-radius: 5px;
            font-size: 13px;
            padding: 10px 15px 25px;
        }

        .modal-confirm .icon-box {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            z-index: 9;
            text-align: center;
            border: 3px solid #f15e5e;
        }

        .modal-confirm .icon-box i {
            color: #f15e5e;
            font-size: 46px;
            display: inline-block;
            margin-top: 13px;
        }

        .modal-confirm .btn,
        .modal-confirm .btn:active {
            color: #fff;
            border-radius: 4px;
            background: #60c7c1;
            text-decoration: none;
            transition: all 0.4s;
            line-height: normal;
            min-width: 120px;
            border: none;
            min-height: 40px;
        }

        .modal-confirm .btn-secondary {
            background: #c1c1c1;
        }

        .modal-confirm .btn-secondary:hover,
        .modal-confirm .btn-secondary:focus {
            background: #a8a8a8;
        }

        .modal-confirm .btn-danger {
            background: #f15e5e;
        }

        .modal-confirm .btn-danger:hover,
        .modal-confirm .btn-danger:focus {
            background: #ee3535;
        }

        /* Dark Mode Contrast Improvements */
        [data-bs-theme="dark"] body {
            background-color: #17191c !important;
        }

        [data-bs-theme="dark"] .card {
            background-color: #1a202c;
            border-color: #2d3748;
        }

        [data-bs-theme="dark"] .img-wrapper {
            background-color: #2d3748;
        }

        [data-bs-theme="dark"] .modal-content {
            background-color: #1a202c;
            border-color: #2d3748;
        }

        /* Header Buttons Dark Mode */
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

        /* Cart/Fav Badge Style */
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

        /* Theme Toggle Button Dark Mode */
        [data-bs-theme="dark"] #btn-tema {
            color: #fff !important;
            border-color: #fff !important;
        }
    </style>
</head>

<body>
    <!-- Header identico a index.php -->
    <header class="sticky-top bg-body border-bottom shadow-sm">
        <div class="container-fluid p-2 p-sm-3">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Logo -->
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center me-2 me-sm-3"
                        style="width: 48px; height: 48px;">
                        <i class="bi bi-book text-white fs-3"></i>
                    </div>
                    <a href="index.php" class="btn btn-link text-body p-0 me-3"><i
                            class="bi bi-arrow-left fs-4"></i></a>
                    <div>
                        <h1 class="h5 fw-bold mb-0">UniboMarket</h1>
                        <p class="text-muted small mb-0 d-none d-md-block">I miei annunci</p>
                    </div>
                </div>

                <!-- Azioni -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Bottone Preferiti -->
                    <a href="preferiti.php"
                        class="btn btn-link text-body p-1 p-sm-2 position-relative d-none d-sm-flex">
                        <i class="bi bi-suit-heart"></i>
                        <span id="fav-counter-header"
                            class="badge rounded-pill bg-danger <?php echo ($fav_count > 0) ? '' : 'd-none'; ?>">
                            <?php echo $fav_count; ?>
                        </span>
                    </a>

                    <!-- Bottone Carrello -->
                    <a href="carrello.php" class="btn btn-link text-body p-1 p-sm-2 position-relative d-none d-sm-flex">
                        <i class="bi bi-cart"></i>
                        <span id="cart-counter-header"
                            class="badge rounded-pill bg-danger <?php echo ($cart_count > 0) ? '' : 'd-none'; ?>">
                            <?php echo $cart_count; ?>
                        </span>
                    </a>

                    <!-- Nome utente e Logout -->
                    <div class="d-none d-md-flex align-items-center">
                        <span class="me-3 text-muted">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['nome'] . ' ' . $_SESSION['cognome']); ?>
                        </span>
                        <a href="logout.php" class="btn btn-outline-dark d-flex align-items-center px-3">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </div>

                    <!-- Bottone Pubblica -->
                    <a href="pubblica.php" class="btn btn-dark d-flex align-items-center justify-content-center px-3">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-md-inline ms-2">Pubblica</span>
                    </a>

                    <!-- Bottone Tema -->
                    <button id="btn-tema" class="btn btn-outline-secondary">
                        <i id="icona-luna" class="bi bi-moon"></i>
                        <i id="icona-sole" class="bi bi-sun d-none"></i>
                    </button>

                    <!-- Bottone menu a tendina -->
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
                        <div class="text-center mb-2">
                            <i class="bi bi-person-circle fs-2 mb-2"></i>
                            <h6 class="mb-0">
                                <?php echo htmlspecialchars($_SESSION['nome'] . ' ' . $_SESSION['cognome']); ?>
                            </h6>
                            <small class="text-muted"><?php echo htmlspecialchars($_SESSION['email']); ?></small>
                        </div>
                        <a href="logout.php"
                            class="btn btn-dark w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>

                    <hr class="my-0 opacity-10">

                    <div class="list-group list-group-flush">
                        <a href="preferiti.php" class="list-group-item list-group-item-action border-0 py-3 px-4">
                            <i class="bi bi-suit-heart me-3"></i> Preferiti
                        </a>
                        <a href="carrello.php" class="list-group-item list-group-item-action border-0 py-3 px-4">
                            <i class="bi bi-cart me-3"></i> Carrello
                        </a>
                        <a href="miei_annunci.php" class="list-group-item list-group-item-action border-0 py-3 px-4">
                            <i class="bi bi-collection me-3"></i> I miei annunci
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
        </div>
    </header>

    <main class="container-fluid py-4">
        <!-- Messaggi di successo/errore -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold">I miei annunci</h1>
            <div>
                <span class="badge bg-primary rounded-pill"><?php echo count($annunci); ?> annunci</span>
            </div>
        </div>

        <?php if (count($annunci) > 0): ?>
            <div class="row g-4">
                <?php foreach ($annunci as $annuncio):
                    $categoria_lower = strtolower($annuncio['nome_categoria']);
                    $classe_categoria = 'categoria-' . $categoria_lower;

                    // Formatta la data
                    $data_pubblicazione = date('d/m/Y H:i', strtotime($annuncio['data_pubblicazione']));
                    $data_modifica = $annuncio['data_modifica'] ? date('d/m/Y H:i', strtotime($annuncio['data_modifica'])) : null;

                    // URL immagine di default
                    $immagine_url = $annuncio['immagine_url'] ?? 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=600';
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <div class="card h-100 border-0 shadow-sm card-annuncio">
                            <!-- Badge stato -->
                            <div class="status-badge">
                                <?php if ($annuncio['is_venduto']): ?>
                                    <span class="badge bg-danger">Venduto</span>
                                <?php elseif (!$annuncio['is_attivo']): ?>
                                    <span class="badge bg-secondary">Non attivo</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Attivo</span>
                                <?php endif; ?>
                            </div>

                            <!-- Pulsanti azione -->
                            <div class="action-buttons">
                                <button class="btn-action"
                                    onclick="confirmDelete(<?php echo $annuncio['id_annuncio']; ?>, '<?php echo htmlspecialchars(addslashes($annuncio['titolo'])); ?>')"
                                    title="Elimina annuncio" <?php echo $annuncio['is_venduto'] ? 'disabled' : ''; ?>>
                                    <i
                                        class="bi bi-trash <?php echo $annuncio['is_venduto'] ? 'text-muted' : 'text-danger'; ?>"></i>
                                </button>

                            </div>

                            <!-- Immagine -->
                            <div class="img-wrapper">
                                <img src="<?php echo $immagine_url; ?>"
                                    alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>">
                            </div>

                            <!-- Contenuto -->
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0 text-truncate">
                                        <?php echo htmlspecialchars($annuncio['titolo']); ?>
                                    </h6>
                                    <span class="badge bg-primary-subtle text-primary price-badge">
                                        €<?php echo number_format($annuncio['prezzo'], 2); ?>
                                    </span>
                                </div>

                                <!-- Descrizione completa -->
                                <p class="small text-muted mb-3">
                                    <?php echo nl2br(htmlspecialchars($annuncio['descrizione'])); ?>
                                </p>

                                <!-- Informazioni -->
                                <div class="mb-3">
                                    <span class="badge <?php echo $classe_categoria; ?> border me-2">
                                        <i class="bi bi-book me-1"></i>
                                        <?php echo htmlspecialchars($annuncio['nome_categoria']); ?>
                                    </span>
                                    <span class="badge bg-info-subtle text-info">
                                        <?php echo htmlspecialchars($annuncio['nome_condizione']); ?>
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?php echo htmlspecialchars($annuncio['nome_facolta'] ?? 'N/A'); ?>
                                    </small>
                                    <?php if ($annuncio['nome_corso']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-journal me-1"></i>
                                            <?php echo htmlspecialchars($annuncio['nome_corso']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <!-- Date -->
                                <div class="border-top pt-2">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar me-1"></i>
                                        Pubblicato: <?php echo $data_pubblicazione; ?>
                                    </small>
                                    <?php if ($data_modifica): ?>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-pencil me-1"></i>
                                            Modificato: <?php echo $data_modifica; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Stato vuoto -->
            <div class="empty-state text-center">
                <div class="mb-3">
                    <i class="bi bi-collection empty-state-icon text-muted"></i>
                </div>
                <h3 class="h4 text-muted mb-2">Nessun annuncio pubblicato</h3>
                <p class="text-muted mb-4">Inizia a vendere i tuoi libri e appunti universitari!</p>
                <a href="pubblica.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Pubblica il tuo primo annuncio
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal di conferma eliminazione -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Conferma eliminazione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Sei sicuro di voler eliminare l'annuncio "<span id="annuncioTitolo"></span>"?</p>
                    <p class="text-danger"><small>Questa azione non può essere annullata.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Elimina</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Gestione Tema (stessa di index.php)
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

        // Funzione per conferma eliminazione
        function confirmDelete(id, titolo) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('annuncioTitolo').textContent = titolo;
            document.getElementById('confirmDeleteBtn').href = 'miei_annunci.php?elimina=' + id;
            deleteModal.show();
        }

        // Inizializzazione tema
        document.addEventListener('DOMContentLoaded', () => {
            const temaSalvato = localStorage.getItem('temaPreferito') || 'light';
            applicaTema(temaSalvato);
        });
    </script>


</body>

</html>