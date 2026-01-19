[file name]: pubblica.php
[file content begin]
<?php
session_start();
require_once 'config/database.php';

// Verifica se l'utente è loggato
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = ''; // 'success' or 'danger'

// Carica dati per i dropdown
$categorie = [];
$result = $conn->query("SELECT * FROM categoria_prodotto ORDER BY nome_categoria");
while ($row = $result->fetch_assoc())
    $categorie[] = $row;

$condizioni = [];
$result = $conn->query("SELECT * FROM condizione_prodotto ORDER BY id_condizione");
while ($row = $result->fetch_assoc())
    $condizioni[] = $row;

$facolta_list = [];
$result = $conn->query("SELECT * FROM facolta ORDER BY nome_facolta");
while ($row = $result->fetch_assoc())
    $facolta_list[] = $row;

$corsi_list = [];
$result = $conn->query("SELECT * FROM corso_studio ORDER BY nome_corso");
while ($row = $result->fetch_assoc())
    $corsi_list[] = $row;


// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo = trim($_POST['titolo'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $prezzo = floatval($_POST['prezzo'] ?? 0);
    $categoria_id = intval($_POST['categoria'] ?? 0);
    $condizione_id = intval($_POST['condizioni'] ?? 0); // Attenzione al name="condizioni"
    $facolta_id = !empty($_POST['facolta']) ? intval($_POST['facolta']) : null;
    $corso_id = !empty($_POST['corso']) ? intval($_POST['corso']) : null;
    
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
        // Gestione Immagine
        $immagine_url = null;
        if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmpPath = $_FILES['immagine']['tmp_name'];
            $fileName = $_FILES['immagine']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');

            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $immagine_url = "../images/" . $newFileName;
                } else {
                    $message = 'Errore nel caricamento immagine.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Estensione file non supportata.';
                $message_type = 'danger';
            }
        }
        
        // Gestione PDF
        $pdf_path = null;
        $original_pdf_name = null;
        if ($is_pdf && isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../pdfs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmpPath = $_FILES['pdf_file']['tmp_name'];
            $fileName = $_FILES['pdf_file']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            // Verifica che sia un PDF
            if ($fileExtension === 'pdf') {
                // Usa un nome univoco per il file
                $newFileName = md5(time() . $fileName) . '.pdf';
                $dest_path = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $pdf_path = "../pdfs/" . $newFileName;
                    $original_pdf_name = $fileName; // Conserviamo il nome originale
                } else {
                    $message = 'Errore nel caricamento del PDF.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Il file deve essere un PDF.';
                $message_type = 'danger';
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
                            $stmt_pdf->bind_param("iss", $annuncio_id, $pdf_path, $original_pdf_name);
                            if (!$stmt_pdf->execute()) {
                                throw new Exception("Errore nel salvataggio del PDF.");
                            }
                            $stmt_pdf->close();
                        }
                        
                        $conn->commit();
                        $message = "Annuncio pubblicato con successo! Verrai reindirizzato alla home tra 3 secondi...";
                        $message_type = "success";
                        header("refresh:3;url=index.php");
                    } else {
                        throw new Exception("Errore database: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Errore connessione DB.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $message = $e->getMessage();
                $message_type = "danger";
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
    <title>Pubblica Annuncio - UniMarket</title>
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
    </style>
</head>

<body class="bg-body-tertiary">

    <header class="container-fluid bg-body border-bottom sticky-top">
        <div class="container-fluid d-flex align-items-center gap-3 p-2">
            <a href="index.php" class="btn btn-link text-body p-0" aria-label="Torna al menu">
                <i class="bi bi-arrow-left fs-4"></i>
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
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-3" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form id="form-annuncio" action="pubblica.php" method="POST" enctype="multipart/form-data"
                    class="needs-validation card bg-body border p-4 shadow-sm" novalidate>

                    <div class="mb-3">
                        <label for="titolo" class="form-label">Titolo annuncio</label>
                        <input type="text" class="form-control" id="titolo" name="titolo" required
                            placeholder="Es. Libro Analisi 1">
                        <div class="invalid-feedback">Inserisci il titolo dell'annuncio.</div>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" rows="5" required
                            placeholder="Descrivi le condizioni, l'anno..."></textarea>
                        <div class="invalid-feedback">Inserisci una descrizione.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-4">
                            <label for="prezzo" class="form-label">Prezzo (€)</label>
                            <input type="number" class="form-control" id="prezzo" name="prezzo" min="0" step="0.50"
                                required placeholder="0.00">
                            <div class="invalid-feedback">Inserisci un prezzo valido.</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="categoria" class="form-label">Categoria</label>
                            <select id="categoria" name="categoria" class="form-select" required>
                                <option value="" selected disabled>Scegli...</option>
                                <?php foreach ($categorie as $cat): ?>
                                    <option value="<?php echo $cat['id_categoria']; ?>" 
                                            data-name="<?php echo strtolower($cat['nome_categoria']); ?>">
                                        <?php echo htmlspecialchars($cat['nome_categoria']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleziona una categoria.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="condizioni" class="form-label">Condizioni</label>
                            <select id="condizioni" name="condizioni" class="form-select" required>
                                <option value="" selected disabled>Scegli...</option>
                                <?php foreach ($condizioni as $cond): ?>
                                    <option value="<?php echo $cond['id_condizione']; ?>">
                                        <?php echo htmlspecialchars($cond['nome_condizione']); ?></option>
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
                                    <option value="<?php echo $f['id_facolta']; ?>">
                                        <?php echo htmlspecialchars($f['nome_facolta']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="corso" class="form-label">Corso di studio</label>
                            <select id="corso" name="corso" class="form-select" disabled>
                                <option value="">Seleziona prima una facoltà</option>
                                <?php foreach ($corsi_list as $c): ?>
                                    <option value="<?php echo $c['id_corso']; ?>"
                                        data-facolta="<?php echo $c['facolta_id']; ?>" style="display:none;">
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
                        <div class="form-text">Carica una foto del prodotto (JPG, PNG).</div>
                        
                        <!-- Anteprima immagine -->
                        <div id="image-preview-container" class="mt-2 d-none">
                            <img id="image-preview" src="#" alt="Anteprima" class="img-preview" style="max-width: 100%; height: 200px;">
                        </div>
                    </div>
                    
                    <!-- Container per upload PDF (nascosto inizialmente) -->
                    <div id="pdf-upload-container" class="mb-3" style="display: none;">
                        <label for="pdf_file" class="form-label">Carica file PDF <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf,.PDF">
                        <div class="form-text">
                            Dimensione massima: 10MB. Il file PDF sarà disponibile per il download dopo l'acquisto.
                        </div>
                        
                        <!-- Anteprima nome PDF -->
                        <div id="pdf-preview" class="mt-2"></div>
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <a id="btn-annulla" href="index.php" class="btn btn-outline-secondary w-100">Annulla</a>
                        <!-- Pulsante Blu come richiesto -->
                        <button id="btn-pubblica" type="submit" class="btn btn-primary w-100 fw-bold">Pubblica annuncio</button>
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

            if (count === 0) cursoSelect.options[0].text = "Nessun corso disponibile";
        });

        // Anteprima Immagine
        const imgInput = document.getElementById('immagine');
        const previewContainer = document.getElementById('image-preview-container');
        const previewImg = document.getElementById('image-preview');

        imgInput.onchange = evt => {
            const [file] = imgInput.files;
            if (file) {
                previewImg.src = URL.createObjectURL(file);
                previewContainer.classList.remove('d-none');
            } else {
                previewContainer.classList.add('d-none');
            }
        };

        // Gestione PDF vs Immagine
        const categoriaSelect = document.getElementById('categoria');
        const pdfContainer = document.getElementById('pdf-upload-container');
        const pdfInput = document.getElementById('pdf_file');
        const imageContainer = document.getElementById('image-upload-container');

        categoriaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const categoriaName = selectedOption.getAttribute('data-name');
            
            if (categoriaName === 'pdf') {
                // Se è PDF
                pdfContainer.style.display = 'block';
                pdfInput.required = true;
                imageContainer.style.display = 'none';
            } else {
                // Se non è PDF
                pdfContainer.style.display = 'none';
                pdfInput.required = false;
                imageContainer.style.display = 'block';
            }
        });

        // Anteprima nome PDF
        pdfInput.addEventListener('change', function() {
            const pdfPreview = document.getElementById('pdf-preview');
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const fileSize = (this.files[0].size / (1024 * 1024)).toFixed(2); // MB
                pdfPreview.innerHTML = `
                    <div class="alert alert-info p-2">
                        <i class="bi bi-file-pdf me-2"></i>
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
        document.getElementById('form-annuncio').addEventListener('submit', function(e) {
            const selectedOption = categoriaSelect.options[categoriaSelect.selectedIndex];
            const categoriaName = selectedOption.getAttribute('data-name');
            
            if (categoriaName === 'pdf') {
                if (!pdfInput.files || pdfInput.files.length === 0) {
                    e.preventDefault();
                    alert('Per prodotti PDF è necessario caricare un file PDF.');
                    pdfInput.focus();
                    return false;
                }
                
                // Controlla dimensione massima (10MB)
                const maxSize = 10 * 1024 * 1024;
                if (pdfInput.files[0].size > maxSize) {
                    e.preventDefault();
                    alert('Il file PDF non può superare 10MB.');
                    return false;
                }
                
                // Controlla estensione
                const fileName = pdfInput.files[0].name.toLowerCase();
                if (!fileName.endsWith('.pdf')) {
                    e.preventDefault();
                    alert('Il file deve avere estensione .pdf');
                    return false;
                }
            }
        });
    </script>

</body>

</html>
[file content end]