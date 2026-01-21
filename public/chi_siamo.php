<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';

// Verifica se l'utente è loggato
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'];

// Conta articoli nel carrello
$cart_count = 0;
if ($is_logged_in && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM carrello WHERE utente_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($cart_count);
    $stmt->fetch();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Siamo - UniboMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
        }

        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--primary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .card {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            border-radius: 15px;
        }

        .card:hover {
            transform: translateY(-10px);
        }

        [data-bs-theme="dark"] .card {
            background-color: #2d3748;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        [data-bs-theme="dark"] .feature-icon {
            background-color: rgba(13, 110, 253, 0.2);
            color: #90caf9;
            /* Migliore contrasto su sfondo scuro */
        }

        #cart-counter-header {
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
    <!-- Header (Simple version, consistent with index.php) -->
    <header class="sticky-top bg-body border-bottom shadow-sm">
        <div class="container py-2">
            <div class="d-flex align-items-center justify-content-between">
                <a href="index.php" class="d-flex align-items-center text-decoration-none text-body">
                    <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center me-3"
                        style="width: 40px; height: 40px;">
                        <span class="bi bi-book text-white fs-4" aria-hidden="true"></span>
                    </div>
                    <h1 class="h5 fw-bold mb-0">UniboMarket</h1>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">Torna alla Home</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Benvenuti su UniboMarket</h1>
            <p class="lead mb-0">La piattaforma nata dagli studenti, per gli studenti di Bologna.</p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mb-5">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-4">Chi Siamo</h2>
                <p class="fs-5 text-muted">
                    UniboMarket è il marketplace universitario definitivo dedicato a chi vive l'Università di Bologna
                    ogni giorno.
                    Sappiamo quanto possa essere costoso e complicato reperire il materiale giusto per gli esami,
                    ed è per questo che abbiamo creato uno spazio sicuro e semplice dove scambiare libri, appunti e
                    risorse digitali.
                </p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="feature-icon" aria-hidden="true">
                        <span class="bi bi-shop"></span>
                    </div>
                    <h3 class="fw-bold h4">Commercio Circolare</h3>
                    <p class="text-muted">Dai una seconda vita ai tuoi libri. Vendere il materiale che non usi più aiuta
                        altri studenti a risparmiare e riduce gli sprechi.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="feature-icon" aria-hidden="true">
                        <span class="bi bi-file-earmark-pdf"></span>
                    </div>
                    <h3 class="fw-bold h4">Risorse Digitali</h3>
                    <p class="text-muted">Accesso immediato a PDF e appunti digitali. Acquista e scarica istantaneamente
                        il materiale per iniziare subito a studiare.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="feature-icon" aria-hidden="true">
                        <span class="bi bi-shield-check"></span>
                    </div>
                    <h3 class="fw-bold h4">Sicurezza Studenti</h3>
                    <p class="text-muted">Una piattaforma pensata per la comunità Unibo. Transazioni trasparenti e
                        gestione semplificata degli annunci.</p>
                </div>
            </div>
        </div>

        <hr class="my-5 opacity-10">

        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800"
                    alt="Gruppo di studenti universitari che collaborano seduti a un tavolo"
                    class="img-fluid rounded-4 shadow">
            </div>
            <div class="col-lg-6 ps-lg-5">
                <h3 class="fw-bold mb-4">La Nostra Missione</h3>
                <p class="mb-4">
                    Crediamo che il sapere debba circolare liberamente e a costi accessibili.
                    UniboMarket non è solo un sito di vendite, ma uno strumento per supportare il successo accademico di
                    ogni studente.
                    Che tu stia cercando il manuale di Analisi 1 o voglia vendere i tuoi preziosi appunti di Diritto
                    Romano,
                    sei nel posto giusto.
                </p>
                <div class="d-grid gap-2 d-md-flex">
                    <a href="shop/pubblica.php" class="btn btn-primary px-4 py-2 rounded-pill">Inizia a Vendere</a>
                    <a href="index.php" class="btn btn-outline-dark px-4 py-2 rounded-pill">Sfoglia Annunci</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer (Simplified) -->
    <footer class="bg-body-tertiary py-4 border-top">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy;
                <?php echo date('Y'); ?> UniboMarket. Creato con passione per la comunità universitaria.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestione Tema (riutilizzata da index.php se necessario)
        const temaSalvato = localStorage.getItem('temaPreferito') || 'light';
        document.documentElement.setAttribute('data-bs-theme', temaSalvato);
    </script>
</body>

</html>