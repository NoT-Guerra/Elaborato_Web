<?php
session_start();

// Verifica se l'utente arriva da un acquisto valido
if (!isset($_SESSION['purchase_success']) || !$_SESSION['purchase_success']) {
    header('Location: carrello.php');
    exit;
}

// Recupera i dati dalla sessione
$items = $_SESSION['purchase_items'];
$total = $_SESSION['purchase_total'];
$date = $_SESSION['purchase_date'];

// Pulisci la sessione dopo aver recuperato i dati
unset($_SESSION['purchase_success']);
unset($_SESSION['purchase_items']);
unset($_SESSION['purchase_total']);
unset($_SESSION['purchase_date']);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acquisto Confermato - UniboMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .success-icon {
            width: 80px;
            height: 80px;
            background: #d4edda;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .success-icon i {
            font-size: 40px;
            color: #28a745;
        }

        .receipt-item {
            border-bottom: 1px dashed #dee2e6;
            padding: 15px 0;
        }

        .receipt-item:last-child {
            border-bottom: none;
        }

        .digital-badge {
            background: #e3f2fd;
            color: #0d6efd;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                font-size: 12pt;
            }
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <!-- Header -->
                <div class="text-center mb-5">
                    <div class="success-icon mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h1 class="fw-bold text-success">Acquisto Confermato!</h1>
                    <p class="text-muted">Grazie per il tuo acquisto. Riceverai una email di conferma con i dettagli.
                    </p>
                </div>

                <!-- Riepilogo acquisto -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 pt-4">
                        <h4 class="fw-bold mb-0">
                            <i class="bi bi-receipt me-2"></i>Riepilogo Ordine
                        </h4>
                        <small class="text-muted">Ordine effettuato il <?php echo $date; ?></small>
                    </div>
                    <div class="card-body">
                        <!-- Lista prodotti -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Prodotti acquistati:</h6>
                            <?php foreach ($items as $item): ?>
                                <div class="receipt-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['titolo']); ?></h6>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span
                                                    class="badge bg-primary"><?php echo htmlspecialchars($item['nome_categoria']); ?></span>
                                                <?php if (strtolower($item['nome_categoria']) === 'pdf' || $item['is_digitale'] == 1): ?>
                                                    <span class="digital-badge">
                                                        <i class="bi bi-cloud-download me-1"></i>Digitale
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (strtolower($item['nome_categoria']) === 'pdf' || $item['is_digitale'] == 1): ?>
                                                <small class="text-success">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Questo prodotto digitale è disponibile per il download nel tuo profilo.
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Il venditore ti contatterà per concordare la consegna.
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold">€<?php echo number_format($item['prezzo'], 2); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Totale -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">Totale pagato:</h5>
                                <h4 class="fw-bold text-success mb-0">€<?php echo number_format($total, 2); ?></h4>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-shield-check me-1"></i>
                                Pagamento sicuro elaborato con successo
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Azioni -->
                <div class="d-grid gap-3 d-md-flex justify-content-md-center no-print">
                    <a href="../index.php" class="btn btn-outline-primary px-4">
                        <i class="bi bi-house me-2"></i>Torna alla Home
                    </a>
                    <a href="../user/miei_acquisti.php" class="btn btn-primary px-4">
                        <i class="bi bi-bag-check me-2"></i>Vedi i miei acquisti
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-printer me-2"></i>Stampa ricevuta
                    </button>
                </div>

                <!-- Info per prodotti digitali -->
                <?php
                $has_digital = false;
                foreach ($items as $item) {
                    if (strtolower($item['nome_categoria']) === 'pdf' || $item['is_digitale'] == 1) {
                        $has_digital = true;
                        break;
                    }
                }
                ?>

                <?php if ($has_digital): ?>
                    <div class="alert alert-info mt-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cloud-download fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Prodotti digitali acquistati</h6>
                                <p class="mb-0">I tuoi file digitali (PDF) sono disponibili nella sezione <a
                                        href="../user/miei_acquisti.php" class="fw-bold">"I miei acquisti"</a> del tuo
                                    profilo. Puoi scaricarli in qualsiasi momento.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Info per prodotti fisici -->
                <?php
                $has_physical = false;
                foreach ($items as $item) {
                    if (strtolower($item['nome_categoria']) !== 'pdf' && $item['is_digitale'] != 1) {
                        $has_physical = true;
                        break;
                    }
                }
                ?>

                <?php if ($has_physical): ?>
                    <div class="alert alert-warning mt-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-truck fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Prodotti fisici</h6>
                                <p class="mb-0">Per i prodotti fisici, il venditore ti contatterà tramite email entro 48 ore
                                    per concordare le modalità di consegna. Assicurati di controllare la tua casella di
                                    posta.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Disabilita il pulsante indietro per evitare doppi acquisti
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</body>

</html>