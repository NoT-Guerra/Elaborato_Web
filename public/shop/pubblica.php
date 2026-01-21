<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';

// Verifica se l'utente è loggato
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

// Genera CSRF token se non esiste
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = ''; // 'success' or 'danger'

// Carica dati per i dropdown
$categorie = [];
$result = $conn->query("SELECT * FROM categoria_prodotto ORDER BY nome_categoria");
while ($row = $result->fetch_assoc()) {
    $categorie[] = $row;
}

$condizioni = [];
$result = $conn->query("SELECT * FROM condizione_prodotto ORDER BY id_condizione");
while ($row = $result->fetch_assoc()) {
    $condizioni[] = $row;
}

$facolta_list = [];
$result = $conn->query("SELECT * FROM facolta ORDER BY nome_facolta");
while ($row = $result->fetch_assoc()) {
    $facolta_list[] = $row;
}

$corsi_list = [];
$result = $conn->query("SELECT * FROM corso_studio ORDER BY nome_corso");
while ($row = $result->fetch_assoc()) {
    $corsi_list[] = $row;
}

// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Token di sicurezza non valido.";
        $message_type = 'danger';
    } else {
        // Sanitizzazione input
        $titolo = htmlspecialchars(trim($_POST['titolo'] ?? ''), ENT_QUOTES, 'UTF-8');
        $descrizione = htmlspecialchars(trim($_POST['descrizione'] ?? ''), ENT_QUOTES, 'UTF-8');
        $prezzo = floatval($_POST['prezzo'] ?? 0);
        $categoria_id = intval($_POST['categoria'] ?? 0);
        $condizione_id = intval($_POST['condizioni'] ?? 0);
        $facolta_id = !empty($_POST['facolta']) ? intval($_POST['facolta']) : null;
        $corso_id = !empty($_POST['corso']) ? intval($_POST['corso']) : null;

        // Validazione lunghezza
        if (strlen($titolo) > 255) {
            $message = "Il titolo è troppo lungo (massimo 255 caratteri).";
            $message_type = 'danger';
        } elseif (strlen($descrizione) > 2000) {
            $message = "La descrizione è troppo lunga (massimo 2000 caratteri).";
            $message_type = 'danger';
        } else {
            // Per sapere se è una categoria PDF
            $categoria_nome = '';
            $is_pdf = false;
            foreach ($categorie as $cat) {
                if ($cat['id_categoria'] == $categoria_id) {
                    $categoria_nome = strtolower($cat['nome_categoria']);
                    $is_pdf = ($categoria_nome == 'pdf');
                    break;
                }
            }

            // Validazione base
            if (empty($titolo) || empty($descrizione) || $prezzo < 0 || empty($categoria_id) || empty($condizione_id)) {
                $message = "Compila tutti i campi obbligatori.";
                $message_type = 'danger';
            } else if ($is_pdf && empty($_FILES['pdf_file']['name'])) {
                // Se è PDF, il file è obbligatorio
                $message = "Per prodotti PDF è necessario caricare un file PDF.";
                $message_type = 'danger';
            } else {
                // Limiti dimensione file
                $maxImageSize = 5 * 1024 * 1024; // 5MB
                $maxPdfSize = 10 * 1024 * 1024; // 10MB

                // Gestione Immagine
                $immagine_url = null;
                if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
                    // Verifica dimensione
                    if ($_FILES['immagine']['size'] > $maxImageSize) {
                        $message = "L'immagine è troppo grande (massimo 5MB).";
                        $message_type = 'danger';
                    } else {
                        $uploadDir = '../assets/img/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $fileTmpPath = $_FILES['immagine']['tmp_name'];
                        $fileName = $_FILES['immagine']['name'];
                        $fileNameCmps = explode(".", $fileName);
                        $fileExtension = strtolower(end($fileNameCmps));
                        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');

                        if (in_array($fileExtension, $allowedfileExtensions)) {
                            // Controllo ulteriore sul tipo MIME
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $fileTmpPath);
                            finfo_close($finfo);

                            $allowedMimeTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');

                            if (in_array($mime, $allowedMimeTypes)) {
                                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                                $dest_path = $uploadDir . $newFileName;

                                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                                    $immagine_url = "assets/img/" . $newFileName;
                                } else {
                                    $message = 'Errore nel caricamento immagine.';
                                    $message_type = 'danger';
                                }
                            } else {
                                $message = 'Tipo di file non supportato.';
                                $message_type = 'danger';
                            }
                        } else {
                            $message = 'Estensione file non supportata.';
                            $message_type = 'danger';
                        }
                    }
                } elseif ($is_pdf) {
                    // Se è PDF e non è stata caricata un'immagine, usa immagine di default
                    $defaultImage = "../assets/img/pdf-default.png";
                    if (file_exists($defaultImage)) {
                        $immagine_url = "assets/img/pdf-default.png";
                    } else {
                        // Se il file non esiste, lascia null e la logica JS mostrerà un'anteprima SVG
                        $immagine_url = null;
                    }
                }

                // Gestione PDF
                $pdf_path = null;
                $original_pdf_name = null;
                if ($is_pdf && isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                    // Verifica dimensione
                    if ($_FILES['pdf_file']['size'] > $maxPdfSize) {
                        $message = "Il PDF è troppo grande (massimo 10MB).";
                        $message_type = 'danger';
                    } else {
                        $uploadDir = '../assets/pdf/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $fileTmpPath = $_FILES['pdf_file']['tmp_name'];
                        $fileName = $_FILES['pdf_file']['name'];
                        $fileNameCmps = explode(".", $fileName);
                        $fileExtension = strtolower(end($fileNameCmps));

                        // Verifica che sia un PDF
                        if ($fileExtension === 'pdf') {
                            // Controllo ulteriore sul tipo MIME
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $fileTmpPath);
                            finfo_close($finfo);

                            if ($mime === 'application/pdf') {
                                // Sanitizza il nome del file
                                $safeFileName = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fileName);
                                $newFileName = md5(time() . $fileName) . '.pdf';
                                $dest_path = $uploadDir . $newFileName;

                                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                                    $pdf_path = "assets/pdf/" . $newFileName;
                                    $original_pdf_name = $safeFileName;
                                } else {
                                    $message = 'Errore nel caricamento del PDF.';
                                    $message_type = 'danger';
                                }
                            } else {
                                $message = 'Il file deve essere un PDF valido.';
                                $message_type = 'danger';
                            }
                        } else {
                            $message = 'Il file deve avere estensione .pdf';
                            $message_type = 'danger';
                        }
                    }
                }

                if (empty($message)) {
                    // Inizia transazione
                    $conn->begin_transaction();

                    try {
                        $venditore_id = $_SESSION['user_id'];
                        $sql = "INSERT INTO annuncio (titolo, descrizione, prezzo, categoria_id, condizione_id, immagine_url, venditore_id, corso_id, facolta_id, is_digitale) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            // is_digitale = 1 se è PDF
                            $is_digitale = $is_pdf ? 1 : 0;
                            $stmt->bind_param("ssdiisiiii", $titolo, $descrizione, $prezzo, $categoria_id, $condizione_id, $immagine_url, $venditore_id, $corso_id, $facolta_id, $is_digitale);

                            if ($stmt->execute()) {
                                $annuncio_id = $stmt->insert_id;

                                // Se è PDF, salva il percorso del file
                                if ($is_pdf && $pdf_path) {
                                    $sql_pdf = "INSERT INTO annuncio_pdf (annuncio_id, pdf_path, original_filename) VALUES (?, ?, ?)";
                                    $stmt_pdf = $conn->prepare($sql_pdf);
                                    if ($stmt_pdf) {
                                        $stmt_pdf->bind_param("iss", $annuncio_id, $pdf_path, $original_pdf_name);
                                        if (!$stmt_pdf->execute()) {
                                            throw new Exception("Errore nel salvataggio del PDF: " . $stmt_pdf->error);
                                        }
                                        $stmt_pdf->close();
                                    } else {
                                        throw new Exception("Errore nella preparazione della query PDF.");
                                    }
                                }

                                $conn->commit();
                                $message = "Annuncio pubblicato con successo! Verrai reindirizzato alla home tra 3 secondi...";
                                $message_type = "success";

                                // Rigenera CSRF token dopo successo
                                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                                // Reindirizzamento dopo 3 secondi
                                header("refresh:3;url=../index.php");
                            } else {
                                throw new Exception("Errore database: " . $stmt->error);
                            }
                            $stmt->close();
                        } else {
                            throw new Exception("Errore connessione DB.");
                        }
                    } catch (Exception $e) {
                        $conn->rollback();
                        $message = "Errore: " . $e->getMessage();
                        $message_type = "danger";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pubblica Annuncio - UniboMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        (function () {
            try {
                const tema = localStorage.getItem('temaPreferito') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', tema);
            } catch (e) { console.warn('Impossibile applicare tema:', e) }
        })();
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        .img-preview {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 6px;
        }

        .preview-card {
            width: 140px;
        }

        .preview-list {
            gap: .75rem;
        }

        @media (max-width: 575.98px) {
            .preview-card {
                width: 100px;
            }

            /* Mobile adjustment */
            main.container {
                margin-top: 1rem !important;
            }
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-body-tertiary">

    <header class="container-fluid bg-body border-bottom sticky-top">
        <div class="container-fluid d-flex align-items-center gap-3 p-2">
            <a href="../index.php" class="btn btn-link text-body p-0" aria-label="Torna al menu">
                <span class="bi bi-arrow-left fs-4"></span>
            </a>
            <div>
                <h1 class="h5 mb-0 fw-bold">Pubblica un annuncio</h1>
                <small class="text-muted">Vendi i tuoi libri ed appunti universitari</small>
            </div>
        </div>
    </header>

    <main class="container my-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show mb-3"
                        role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form id="form-annuncio" action="pubblica.php" method="POST" enctype="multipart/form-data"
                    class="needs-validation card bg-body border p-4 shadow-sm" novalidate>

                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="mb-3">
                        <label for="titolo" class="form-label required">Titolo annuncio</label>
                        <input type="text" class="form-control" id="titolo" name="titolo" required
                            placeholder="Es. Libro Analisi 1" maxlength="255">
                        <div class="invalid-feedback">Inserisci il titolo dell'annuncio (max 255 caratteri).</div>
                        <div class="form-text">Massimo 255 caratteri</div>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label required">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" rows="5" required
                            placeholder="Descrivi le condizioni, l'anno..." maxlength="2000"></textarea>
                        <div class="invalid-feedback">Inserisci una descrizione.</div>
                        <div class="form-text">Massimo 2000 caratteri</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-4">
                            <label for="prezzo" class="form-label required">Prezzo (€)</label>
                            <input type="number" class="form-control" id="prezzo" name="prezzo" min="0" step="0.50"
                                required placeholder="0.00">
                            <div class="invalid-feedback">Inserisci un prezzo valido.</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="categoria" class="form-label required">Categoria</label>
                            <select id="categoria" name="categoria" class="form-select" required>
                                <option value="" selected disabled>Scegli...</option>
                                <?php foreach ($categorie as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['id_categoria']); ?>"
                                        data-name="<?php echo htmlspecialchars(strtolower($cat['nome_categoria'])); ?>">
                                        <?php echo htmlspecialchars($cat['nome_categoria']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleziona una categoria.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="condizioni" class="form-label required">Condizioni</label>
                            <select id="condizioni" name="condizioni" class="form-select" required>
                                <option value="" selected disabled>Scegli...</option>
                                <?php foreach ($condizioni as $cond): ?>
                                    <option value="<?php echo htmlspecialchars($cond['id_condizione']); ?>">
                                        <?php echo htmlspecialchars($cond['nome_condizione']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleziona le condizioni.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="facolta" class="form-label">Facoltà</label>
                            <select id="facolta" name="facolta" class="form-select">
                                <option value="">Tutte le facoltà</option>
                                <?php foreach ($facolta_list as $f): ?>
                                    <option value="<?php echo htmlspecialchars($f['id_facolta']); ?>">
                                        <?php echo htmlspecialchars($f['nome_facolta']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="corso" class="form-label">Corso di studio</label>
                            <select id="corso" name="corso" class="form-select" disabled>
                                <option value="">Seleziona prima una facoltà</option>
                                <?php foreach ($corsi_list as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['id_corso']); ?>"
                                        data-facolta="<?php echo htmlspecialchars($c['facolta_id']); ?>"
                                        style="display:none;">
                                        <?php echo htmlspecialchars($c['nome_corso']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <!-- Container per upload immagine -->
                    <div id="image-upload-container" class="mb-3">
                        <label for="immagine" class="form-label">Immagine prodotto</label>
                        <input type="file" class="form-control" id="immagine" name="immagine" accept="image/*">
                        <div class="form-text">Carica una foto del prodotto (JPG, PNG, WebP). Max 5MB</div>

                        <!-- Anteprima immagine -->
                        <div id="image-preview-container" class="mt-2 d-none">
                            <img id="image-preview" src="#" alt="Anteprima" class="img-preview"
                                style="max-width: 100%; height: 200px;">
                        </div>
                    </div>

                    <!-- Container per upload PDF (nascosto inizialmente) -->
                    <div id="pdf-upload-container" class="mb-3" style="display: none;">
                        <label for="pdf_file" class="form-label required">Carica file PDF</label>
                        <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf,.PDF">
                        <div class="form-text">
                            Dimensione massima: 10MB. Il file PDF sarà disponibile per il download dopo l'acquisto.
                        </div>

                        <!-- Anteprima nome PDF -->
                        <div id="pdf-preview" class="mt-2"></div>
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <a id="btn-annulla" href="../index.php" class="btn btn-outline-secondary w-100">Annulla</a>
                        <!-- Pulsante Blu come richiesto -->
                        <button id="btn-pubblica" type="submit" class="btn btn-primary w-100 fw-bold">Pubblica
                            annuncio</button>
                    </div>

                </form>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })();

        // Filtro Corsi
        const facoltaSelect = document.getElementById('facolta');
        const corsoSelect = document.getElementById('corso');
        const allCorsiOptions = Array.from(corsoSelect.options);

        facoltaSelect.addEventListener('change', function () {
            const selectedFacoltaId = this.value;
            corsoSelect.value = "";

            if (!selectedFacoltaId) {
                corsoSelect.disabled = true;
                corsoSelect.options[0].text = "Seleziona prima una facoltà";
                return;
            }

            corsoSelect.disabled = false;
            corsoSelect.options[0].text = "Seleziona un corso (opzionale)";

            let count = 0;
            allCorsiOptions.forEach(opt => {
                if (opt.value === "") return;
                const corsoFacoltaId = opt.getAttribute('data-facolta');
                if (corsoFacoltaId === selectedFacoltaId) {
                    opt.style.display = 'block';
                    count++;
                } else {
                    opt.style.display = 'none';
                }
            });

            if (count === 0) corsoSelect.options[0].text = "Nessun corso disponibile";
        });

        // Anteprima Immagine
        const imgInput = document.getElementById('immagine');
        const previewContainer = document.getElementById('image-preview-container');
        const previewImg = document.getElementById('image-preview');

        imgInput.onchange = evt => {
            const [file] = imgInput.files;
            if (file) {
                // Controllo dimensione
                if (file.size > 5 * 1024 * 1024) {
                    alert('L\'immagine non può superare 5MB');
                    imgInput.value = '';
                    previewContainer.classList.add('d-none');
                    return;
                }

                previewImg.src = URL.createObjectURL(file);
                previewContainer.classList.remove('d-none');
            } else {
                previewContainer.classList.add('d-none');
            }
        };

        // Gestione PDF vs Immagine con immagine di default
        const categoriaSelect = document.getElementById('categoria');
        const pdfContainer = document.getElementById('pdf-upload-container');
        const pdfInput = document.getElementById('pdf_file');
        const imageContainer = document.getElementById('image-upload-container');

        categoriaSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const categoriaName = selectedOption.getAttribute('data-name');

            if (categoriaName === 'pdf') {
                // Se è PDF
                pdfContainer.style.display = 'block';
                pdfInput.required = true;
                imageContainer.style.display = 'none';

                // Mostra immagine di default per PDF
                showDefaultPDFImage();
            } else {
                // Se non è PDF
                pdfContainer.style.display = 'none';
                pdfInput.required = false;
                imageContainer.style.display = 'block';

                // Resetta l'anteprima se non c'è immagine
                if (!imgInput.files || imgInput.files.length === 0) {
                    previewContainer.classList.add('d-none');
                }
            }
        });

        // Funzione per mostrare immagine di default per PDF
        function showDefaultPDFImage() {
            const svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 24 24"><rect width="100%" height="100%" fill="#f8f9fa"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2.5L18.5 9H13V4.5zM20 20H6V4h5v7h7v9z" fill="#dc3545"/><path d="M8 15h8v2H8zm0-4h8v2H8zm0-4h3v2H8z" fill="#dc3545"/><text x="12" y="170" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" fill="#6c757d">Documento PDF</text></svg>';
            const svgImage = `data:image/svg+xml;base64,${btoa(svgContent)}`;
            previewImg.src = svgImage;
            previewContainer.classList.remove('d-none');
        }

        // Anteprima nome PDF
        pdfInput.addEventListener('change', function () {
            const pdfPreview = document.getElementById('pdf-preview');
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileName = file.name;
                const fileSize = (file.size / (1024 * 1024)).toFixed(2); // MB

                // Controllo dimensione
                if (file.size > 10 * 1024 * 1024) {
                    alert('Il PDF non può superare 10MB');
                    this.value = '';
                    pdfPreview.innerHTML = '';
                    return;
                }

                // Controllo estensione
                if (!fileName.toLowerCase().endsWith('.pdf')) {
                    alert('Il file deve avere estensione .pdf');
                    this.value = '';
                    pdfPreview.innerHTML = '';
                    return;
                }

                pdfPreview.innerHTML = `
                    <div class="alert alert-info p-2">
                        <span class="bi bi-file-pdf me-2" aria-hidden="true"></span>
                        <strong>${fileName}</strong> (${fileSize} MB)
                        <br>
                        <small class="text-muted">Il file sarà disponibile per il download dopo l'acquisto</small>
                    </div>
                `;
            } else {
                pdfPreview.innerHTML = '';
            }
        });

        // Validazione form
        document.getElementById('form-annuncio').addEventListener('submit', function (e) {
            const selectedOption = categoriaSelect.options[categoriaSelect.selectedIndex];
            const categoriaName = selectedOption.getAttribute('data-name');

            if (categoriaName === 'pdf') {
                if (!pdfInput.files || pdfInput.files.length === 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Per prodotti PDF è necessario caricare un file PDF.');
                    pdfInput.focus();
                    return false;
                }
            }

            // Validazione prezzo
            const prezzoInput = document.getElementById('prezzo');
            const prezzo = parseFloat(prezzoInput.value);
            if (prezzo < 0) {
                e.preventDefault();
                e.stopPropagation();
                alert('Il prezzo non può essere negativo.');
                prezzoInput.focus();
                return false;
            }
        });

        // Mostra immagine di default se PDF è già selezionato al caricamento della pagina
        document.addEventListener('DOMContentLoaded', function () {
            const selectedOption = categoriaSelect.options[categoriaSelect.selectedIndex];
            if (selectedOption.value) {
                const categoriaName = selectedOption.getAttribute('data-name');
                if (categoriaName === 'pdf') {
                    showDefaultPDFImage();
                }
            }
        });
    </script>

</body>

</html>