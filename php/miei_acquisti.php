<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$user_id = $_SESSION['user_id'];

// Recupera gli acquisti dell'utente
$sql = "SELECT 
            v.id_vendita,
            v.prezzo_vendita,
            v.data_vendita,
            a.id_annuncio,
            a.titolo,
            a.descrizione,
            a.immagine_url,
            a.is_digitale,
            cp.nome_categoria,
            cond.nome_condizione,
            u.nome as venditore_nome,
            u.cognome as venditore_cognome,
            u.email as venditore_email,
            ap.pdf_path,
            ap.original_filename
        FROM vendita v
        JOIN annuncio a ON v.annuncio_id = a.id_annuncio
        JOIN categoria_prodotto cp ON a.categoria_id = cp.id_categoria
        JOIN condizione_prodotto cond ON a.condizione_id = cond.id_condizione
        JOIN utenti u ON v.venditore_id = u.id_utente
        LEFT JOIN annuncio_pdf ap ON a.id_annuncio = ap.annuncio_id
        WHERE v.acquirente_id = ?
        ORDER BY v.data_vendita DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$acquisti = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I miei acquisti - UniboMarket</title>
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

    <!-- Header simile a index.php -->
    <header class="sticky-top bg-body border-bottom shadow-sm">
        <div class="container-fluid p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-link text-body p-0 me-3">
                        <i class="bi bi-arrow-left fs-4"></i>
                    </a>
                    <div>
                        <h1 class="h5 fw-bold mb-0">I miei acquisti</h1>
                        <p class="text-muted small mb-0"><?php echo count($acquisti); ?> acquisti effettuati</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4">
        <?php if (count($acquisti) > 0): ?>
            <div class="row g-4">
                <?php foreach ($acquisti as $acquisto): 
                    $is_digitale = (strtolower($acquisto['nome_categoria']) === 'pdf' || $acquisto['is_digitale'] == 1);
                    $data_vendita = date('d/m/Y H:i', strtotime($acquisto['data_vendita']));
                    $has_pdf = !empty($acquisto['pdf_path']);
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <?php if (!empty($acquisto['immagine_url'])): ?>
                                <img src="<?php echo htmlspecialchars($acquisto['immagine_url']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($acquisto['titolo']); ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($acquisto['titolo']); ?></h6>
                                    <span class="badge bg-success">€<?php echo number_format($acquisto['prezzo_vendita'], 2); ?></span>
                                </div>
                                
                                <p class="small text-muted mb-2">
                                    <?php echo htmlspecialchars(substr($acquisto['descrizione'], 0, 100)); ?>...
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($acquisto['nome_categoria']); ?>
                                    </span>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($acquisto['nome_condizione']); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-person me-1"></i>
                                        Venditore: <?php echo htmlspecialchars($acquisto['venditore_nome'] . ' ' . $acquisto['venditore_cognome']); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar me-1"></i>
                                        Acquistato il: <?php echo $data_vendita; ?>
                                    </small>
                                </div>
                                
                                <?php if ($is_digitale): ?>
                                    <div class="alert alert-success p-2 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-cloud-download fs-5 me-2"></i>
                                            <div>
                                                <small class="fw-bold d-block">Prodotto digitale</small>
                                                <small>Disponibile per il download</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($has_pdf): ?>
                                        <form action="download_pdf.php" method="POST">
                                            <input type="hidden" name="vendita_id" value="<?php echo $acquisto['id_vendita']; ?>">
                                            <input type="hidden" name="annuncio_id" value="<?php echo $acquisto['id_annuncio']; ?>">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-download me-2"></i>
                                                Scarica "<?php echo htmlspecialchars($acquisto['original_filename']); ?>"
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-primary w-100" onclick="alert('PDF non disponibile. Contatta l\'assistenza.')">
                                            <i class="bi bi-download me-2"></i>Scarica PDF
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-info p-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-truck fs-5 me-2"></i>
                                            <div>
                                                <small class="fw-bold d-block">Prodotto fisico</small>
                                                <small>Contatta il venditore per la consegna</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <a href="mailto:<?php echo htmlspecialchars($acquisto['venditore_email']); ?>" 
                                       class="btn btn-outline-primary w-100">
                                        <i class="bi bi-envelope me-2"></i>Contatta venditore
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x fs-1 text-muted"></i>
                <h5 class="mt-3 text-muted">Nessun acquisto effettuato</h5>
                <p class="text-muted">Visita il marketplace per trovare prodotti interessanti!</p>
                <a href="index.php" class="btn btn-primary mt-2">
                    <i class="bi bi-shop me-2"></i>Vai allo shopping
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
