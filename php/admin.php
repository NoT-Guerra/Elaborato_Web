<?php
session_start();
require_once 'config/database.php';

// Array per messaggi flash
$flash = [];

// Accesso non eseguito
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Accesso eseguito ma non è admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Verifica connessione
if (!$conn || $conn->connect_error) {
    die("Errore di connessione al database");
}

// ==========================
// GESTIONE POST (CRUD)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ELIMINA UTENTE
    if (isset($_POST['delete_user_id'])) {
        $id = (int) $_POST['delete_user_id'];
        $stmt = $conn->prepare("DELETE FROM utenti WHERE id_utente = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin.php');
        exit;
    }

    // RESET PASSWORD UTENTE
    if (isset($_POST['reset_user_id'], $_POST['new_password'])) {
        $id = (int) $_POST['reset_user_id'];
        $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE utenti SET password = ? WHERE id_utente = ?");
        $stmt->bind_param("si", $password, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin.php');
        exit;
    }

    // ELIMINA ANNUNCIO
    if (isset($_POST['delete_announcement_id'])) {
        $id = (int) $_POST['delete_announcement_id'];
        $stmt = $conn->prepare("DELETE FROM annuncio WHERE id_annuncio = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin.php');
        exit;
    }

    // AGGIUNGI MATERIA
    if (isset($_POST['new_subject'])) {
        $subject = trim($_POST['new_subject']);
        if ($subject !== '') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM corso_studio WHERE nome_corso = ?");
            $stmt->bind_param("s", $subject);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            if ($count == 0) {
                $stmt = $conn->prepare("INSERT INTO corso_studio (nome_corso) VALUES (?)");
                $stmt->bind_param("s", $subject);
                $stmt->execute();
                $stmt->close();
            }
        }
        header('Location: admin.php');
        exit;
    }

    // ELIMINA MATERIA
    if (isset($_POST['delete_subject'])) {
        $subject = trim($_POST['delete_subject']);
        $stmt = $conn->prepare("DELETE FROM corso_studio WHERE nome_corso = ?");
        $stmt->bind_param("s", $subject);
        $stmt->execute();
        $stmt->close();
        header('Location: admin.php');
        exit;
    }

    // AGGIUNGI FACOLTÀ
    if (isset($_POST['new_faculty'])) {
        $faculty = trim($_POST['new_faculty']);
        if ($faculty !== '') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM facolta WHERE nome_facolta = ?");
            $stmt->bind_param("s", $faculty);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            if ($count == 0) {
                $stmt = $conn->prepare("INSERT INTO facolta (nome_facolta) VALUES (?)");
                $stmt->bind_param("s", $faculty);
                $stmt->execute();
                $stmt->close();
            }
        }
        header('Location: admin.php');
        exit;
    }

    // ELIMINA FACOLTÀ
    if (isset($_POST['delete_faculty'])) {
        $faculty = trim($_POST['delete_faculty']);
        $stmt = $conn->prepare("DELETE FROM facolta WHERE nome_facolta = ?");
        $stmt->bind_param("s", $faculty);
        $stmt->execute();
        $stmt->close();
        header('Location: admin.php');
        exit;
    }
}

// ==========================
// CARICAMENTO DATI PER PAGINA
// ==========================

// STATISTICHE
$stats = ['total_users' => 0, 'active_announcements' => 0, 'completed_sales' => 0];
$result = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM utenti) as total_users,
        (SELECT COUNT(*) FROM annuncio WHERE is_attivo = 1 AND is_venduto = 0) as active_announcements,
        (SELECT COUNT(*) FROM vendita) as completed_sales
");
if ($result && $row = $result->fetch_assoc())
    $stats = $row;
if ($result)
    $result->free();

// UTENTI
$users = [];
$result = $conn->query("
    SELECT u.id_utente as id, u.nome as firstName, u.cognome as lastName, u.email,
           f.nome_facolta as university
    FROM utenti u
    LEFT JOIN facolta f ON u.facolta_id = f.id_facolta
    ORDER BY u.nome, u.cognome
");
if ($result)
    while ($row = $result->fetch_assoc())
        $users[] = $row;

// ANNUNCI
$announcements = [];
$result = $conn->query("
    SELECT a.id_annuncio as id, a.titolo as title, a.prezzo as price,
           CONCAT(u.nome, ' ', LEFT(u.cognome,1), '.') as seller,
           DATE(a.data_pubblicazione) as publishedDate,
           cp.nome_categoria as type,
           CASE 
               WHEN a.is_attivo = 1 AND a.is_venduto = 0 THEN 'attivo'
               WHEN a.is_venduto = 1 THEN 'venduto'
               ELSE 'non attivo'
           END as status
    FROM annuncio a
    JOIN utenti u ON a.venditore_id = u.id_utente
    JOIN categoria_prodotto cp ON a.categoria_id = cp.id_categoria
    WHERE a.is_attivo = 1
    ORDER BY a.data_pubblicazione DESC
");
if ($result)
    while ($row = $result->fetch_assoc())
        $announcements[] = $row;

// MATERIE
$subjects = [];
$result = $conn->query("SELECT DISTINCT nome_corso FROM corso_studio ORDER BY nome_corso");
if ($result)
    while ($row = $result->fetch_assoc())
        $subjects[] = $row['nome_corso'];

// FACOLTÀ
$faculties = [];
$result = $conn->query("SELECT nome_facolta FROM facolta ORDER BY nome_facolta");
if ($result)
    while ($row = $result->fetch_assoc())
        $faculties[] = $row['nome_facolta'];

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Admin - UniMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Script per applicare il tema light/dark preso da login.php -->
    <script>
        (function () {
            try {
                const tema = localStorage.getItem('temaPreferito') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', tema);
            } catch (e) { console.warn('Tema non caricato:', e) }
        })();
    </script>
</head>

<body>
    <header class="sticky-top border-bottom py-3 bg-body-tertiary">
        <div class="container-fluid d-flex align-items-center gap-3">
            <a href="index.php" class="btn btn-link p-0 me-3 text-body-emphasis" data-bs-toggle="tooltip"
                data-bs-placement="bottom" title="Torna alla Home">
                <i class="bi bi-arrow-left fs-4"></i>
            </a>
            <div>
                <h1 class="h4 fw-bold mb-0 text-body-emphasis">Pannello Admin</h1>
                <p class="small mb-0 text-body-secondary">Gestisci utenti, annunci e categorie</p>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4">
        <!-- STATISTICHE -->
        <div class="row mb-4 g-3">
            <?php
            $cards = [
                ['color' => 'primary', 'icon' => 'users', 'label' => 'Utenti Totali', 'value' => $stats['total_users']],
                ['color' => 'success', 'icon' => 'file-alt', 'label' => 'Annunci Attivi', 'value' => $stats['active_announcements']],
                ['color' => 'info', 'icon' => 'check-circle', 'label' => 'Vendite Concluse', 'value' => $stats['completed_sales']],
            ];
            foreach ($cards as $c): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div
                                class="bg-<?php echo $c['color']; ?> bg-opacity-10 text-<?php echo $c['color']; ?> p-3 rounded me-3">
                                <i class="fas fa-<?php echo $c['icon']; ?> fs-4"></i>
                            </div>
                            <div>
                                <p class="small mb-1"><?php echo $c['label']; ?></p>
                                <h3 class="mb-0 fw-bold"><?php echo $c['value']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><button class="nav-link active fw-semibold" data-bs-toggle="tab"
                    data-bs-target="#users"><i class="fas fa-users me-2"></i>Utenti</button></li>
            <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab"
                    data-bs-target="#announcements"><i class="fas fa-file-alt me-2"></i>Annunci</button></li>
            <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab"
                    data-bs-target="#categories"><i class="fas fa-layer-group me-2"></i>Categorie</button></li>
        </ul>
        <div class="tab-content">

            <!-- UTENTI -->
            <div class="tab-pane fade show active" id="users">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom py-3 fw-bold">Gestione Utenti</div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Università</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <?php echo $user['id']; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['university'] ?? 'Non specificata'); ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="reset_user_id"
                                                    value="<?php echo $user['id']; ?>">
                                                <input type="password" name="new_password" placeholder="Nuova password"
                                                    required class="form-control d-inline-block w-auto">
                                                <button class="btn btn-outline-primary btn-sm">Reset</button>
                                            </form>
                                            <form method="POST" class="d-inline"
                                                onsubmit="return confirm('Eliminare questo utente?');">
                                                <input type="hidden" name="delete_user_id"
                                                    value="<?php echo $user['id']; ?>">
                                                <button class="btn btn-outline-danger btn-sm">Elimina</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ANNUNCI -->
            <div class="tab-pane fade" id="announcements">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom py-3 fw-bold">Moderazione Annunci</div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Titolo</th>
                                    <th>Tipo</th>
                                    <th>Prezzo</th>
                                    <th>Venditore</th>
                                    <th>Data</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($announcements as $ann): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($ann['title']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($ann['type']); ?>
                                        </td>
                                        <td>€
                                            <?php echo number_format($ann['price'], 2); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($ann['seller']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($ann['publishedDate']); ?>
                                        </td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Eliminare questo annuncio?');">
                                                <input type="hidden" name="delete_announcement_id"
                                                    value="<?php echo $ann['id']; ?>">
                                                <button class="btn btn-outline-danger btn-sm">Elimina</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- CATEGORIE -->
            <div class="tab-pane fade" id="categories">
                <div class="row g-4">
                    <!-- MATERIE -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-bottom py-3 fw-bold">Gestione Materie</div>
                            <div class="card-body">
                                <form method="POST" class="d-flex mb-3">
                                    <input type="text" name="new_subject" class="form-control me-2"
                                        placeholder="Nuova materia" required>
                                    <button class="btn btn-primary">Aggiungi</button>
                                </form>
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                        <span>
                                            <?php echo htmlspecialchars($subject); ?>
                                        </span>
                                        <form method="POST" onsubmit="return confirm('Eliminare questa materia?');">
                                            <input type="hidden" name="delete_subject"
                                                value="<?php echo htmlspecialchars($subject); ?>">
                                            <button class="btn btn-link text-danger p-0"><i
                                                    class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <!-- FACOLTÀ -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-bottom py-3 fw-bold">Gestione Facoltà</div>
                            <div class="card-body">
                                <form method="POST" class="d-flex mb-3">
                                    <input type="text" name="new_faculty" class="form-control me-2"
                                        placeholder="Nuova facoltà" required>
                                    <button class="btn btn-primary">Aggiungi</button>
                                </form>
                                <?php foreach ($faculties as $faculty): ?>
                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                        <span>
                                            <?php echo htmlspecialchars($faculty); ?>
                                        </span>
                                        <form method="POST" onsubmit="return confirm('Eliminare questa facoltà?');">
                                            <input type="hidden" name="delete_faculty"
                                                value="<?php echo htmlspecialchars($faculty); ?>">
                                            <button class="btn btn-link text-danger p-0"><i
                                                    class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Applica il tema al caricamento
        (function () {
            try {
                const tema = localStorage.getItem('temaPreferito') ||
                    (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', tema);
            } catch (e) {
                console.warn('Tema non caricato:', e)
            }
        })();
    </script>
</body>

</html>