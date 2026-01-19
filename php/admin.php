<?php
session_start();
require_once 'config/database.php';

// accesso non eseguito
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header('Location: login.php');
    exit;
}

// accesso eseguito ma non è admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != true) {
    header('Location: index.php');
    exit;
}

// Verifica che la connessione sia stata stabilita
if (!$conn || $conn->connect_error) {
    die("Errore di connessione al database");
}

// Recupera dati dal database usando MySQLi
$stats = ['total_users' => 0, 'active_announcements' => 0, 'completed_sales' => 0];
$users = [];
$announcements = [];
$subjects = [];
$faculties = [];

// Statistiche
$query = "
    SELECT 
        (SELECT COUNT(*) FROM utenti) as total_users,
        (SELECT COUNT(*) FROM annuncio WHERE is_attivo = 1 AND is_venduto = 0) as active_announcements,
        (SELECT COUNT(*) FROM vendita) as completed_sales
";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats = $row;
}
if ($result)
    $result->free();

// Utenti
$query = "
    SELECT 
        u.id_utente as id,
        u.nome as firstName,
        u.cognome as lastName,
        u.email,
        f.nome_facolta as university
    FROM utenti u
    LEFT JOIN facolta f ON u.facolta_id = f.id_facolta
    ORDER BY u.nome, u.cognome
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

// Annunci
$query = "
    SELECT 
        a.id_annuncio as id,
        a.titolo as title,
        a.prezzo as price,
        CONCAT(u.nome, ' ', LEFT(u.cognome, 1), '.') as seller,
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
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    $result->free();
}

// Materie (Corsi di studio)
$query = "SELECT DISTINCT nome_corso FROM corso_studio ORDER BY nome_corso";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['nome_corso'];
    }
    $result->free();
}

// Facoltà
$query = "SELECT nome_facolta FROM facolta ORDER BY nome_facolta";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $faculties[] = $row['nome_facolta'];
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Admin - UniMarket</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style/style.css">
</head>

<body>
    <!-- Header -->
    <header class="sticky-top border-bottom py-3">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php" class="btn btn-link p-0">
                        <i class="bi bi-arrow-left fs-4"></i>
                    </a>
                    <div>
                        <h1 class="h4 fw-bold mb-0">Pannello Admin</h1>
                        <p class="small mb-0">Gestisci utenti, annunci e categorie</p>
                    </div>
                </div>
                <!-- Bottone tema rimosso -->
            </div>
        </div>
    </header>

    <main class="container-fluid py-4">
        <!-- Statistiche -->
        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded me-3">
                            <i class="fas fa-users fs-4"></i>
                        </div>
                        <div>
                            <p class="small mb-1">Utenti Totali</p>
                            <h3 class="mb-0 fw-bold" id="totalUsers"><?php echo $stats['total_users'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded me-3">
                            <i class="fas fa-file-alt fs-4"></i>
                        </div>
                        <div>
                            <p class="small mb-1">Annunci Attivi</p>
                            <h3 class="mb-0 fw-bold" id="activeAnnouncements">
                                <?php echo $stats['active_announcements'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded me-3">
                            <i class="fas fa-check-circle fs-4"></i>
                        </div>
                        <div>
                            <p class="small mb-1">Vendite Concluse</p>
                            <h3 class="mb-0 fw-bold" id="completedSales"><?php echo $stats['completed_sales'] ?? 0; ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="adminTabs">
            <li class="nav-item"><button class="nav-link active fw-semibold" data-bs-target="#users"
                    data-bs-toggle="tab"><i class="fas fa-users me-2"></i>Utenti</button></li>
            <li class="nav-item"><button class="nav-link fw-semibold" data-bs-target="#announcements"
                    data-bs-toggle="tab"><i class="fas fa-file-alt me-2"></i>Annunci</button></li>
            <li class="nav-item"><button class="nav-link fw-semibold" data-bs-target="#categories"
                    data-bs-toggle="tab"><i class="fas fa-layer-group me-2"></i>Categorie</button></li>
        </ul>

        <!-- Contenuto Tabs -->
        <div class="tab-content">
            <!-- Utenti -->
            <div class="tab-pane fade show active" id="users">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-users me-2"></i>Gestione Utenti</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Università</th>
                                        <th class="text-end pe-4">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['university'] ?? 'Non specificata'); ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-outline-primary btn-sm me-2"
                                                    onclick="openResetPassword(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-key me-1"></i>Reset
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm"
                                                    onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-trash-alt me-1"></i>Elimina
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Annunci -->
            <div class="tab-pane fade" id="announcements">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold"><i class="fas fa-file-alt me-2"></i>Moderazione Annunci</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Titolo</th>
                                        <th>Tipo</th>
                                        <th>Prezzo</th>
                                        <th>Venditore</th>
                                        <th>Data</th>
                                        <th class="text-end pe-4">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="announcementsTableBody">
                                    <?php foreach ($announcements as $ann): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span
                                                    class="badge <?php echo $ann['status'] === 'venduto' ? 'bg-success' : 'bg-primary'; ?> me-2">
                                                    <?php echo $ann['status'] === 'venduto' ? 'Venduto' : 'Attivo'; ?>
                                                </span>
                                                <span class="text-truncate d-inline-block" style="max-width:200px"
                                                    title="<?php echo htmlspecialchars($ann['title']); ?>">
                                                    <?php echo htmlspecialchars($ann['title']); ?>
                                                </span>
                                            </td>
                                            <td><span
                                                    class="badge bg-secondary"><?php echo htmlspecialchars($ann['type']); ?></span>
                                            </td>
                                            <td class="fw-semibold">€<?php echo number_format($ann['price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($ann['seller']); ?></td>
                                            <td><?php echo htmlspecialchars($ann['publishedDate']); ?></td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-outline-danger btn-sm"
                                                    onclick="deleteAnnouncement(<?php echo $ann['id']; ?>)">
                                                    <i class="fas fa-trash-alt me-1"></i>Elimina
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categorie -->
            <div class="tab-pane fade" id="categories">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold"><i class="fas fa-book me-2"></i>Gestione Materie
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-4">
                                    <div class="col"><input type="text" class="form-control" id="newSubject"
                                            placeholder="Nuova materia"></div>
                                    <div class="col-auto d-flex align-items-end"><button class="btn btn-primary"
                                            id="addSubjectBtn"><i class="fas fa-plus me-2"></i>Aggiungi</button></div>
                                </div>
                                <div class="scrollable-content" id="subjectsList">
                                    <?php foreach ($subjects as $index => $subject): ?>
                                        <div
                                            class="category-item d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <span class="fw-medium"><?php echo htmlspecialchars($subject); ?></span>
                                            <button class="btn btn-link text-danger p-0"
                                                onclick="deleteSubject(<?php echo $index; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold"><i class="fas fa-graduation-cap me-2"></i>Gestione
                                    Facoltà</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-4">
                                    <div class="col"><input type="text" class="form-control" id="newFaculty"
                                            placeholder="Nuova facoltà"></div>
                                    <div class="col-auto d-flex align-items-end"><button class="btn btn-primary"
                                            id="addFacultyBtn"><i class="fas fa-plus me-2"></i>Aggiungi</button></div>
                                </div>
                                <div class="scrollable-content" id="facultiesList">
                                    <?php foreach ($faculties as $index => $faculty): ?>
                                        <div
                                            class="category-item d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <span class="fw-medium"><?php echo htmlspecialchars($faculty); ?></span>
                                            <button class="btn btn-link text-danger p-0"
                                                onclick="deleteFaculty(<?php echo $index; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div class="modal fade" id="resetPasswordModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Reset Password</h5><button class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="resetPasswordUserInfo"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuova Password</label>
                        <input type="password" class="form-control" id="newPassword" placeholder="Minimo 6 caratteri"
                            minlength="6" required>
                        <div class="form-text">La password deve essere di almeno 6 caratteri</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button class="btn btn-primary" id="confirmResetBtn">Conferma Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Passa i dati PHP a JavaScript
        const users = <?php echo json_encode($users); ?>;
        const announcements = <?php echo json_encode($announcements); ?>;
        let subjects = <?php echo json_encode($subjects); ?>;
        let faculties = <?php echo json_encode($faculties); ?>;
        let selectedUser = null;

        function init() {
            // Carica le funzioni JavaScript
            loadUsers();
            loadAnnouncements();
            loadSubjects();
            loadFaculties();
            updateStats();

            // Aggiungi event listeners
            document.getElementById('addSubjectBtn').addEventListener('click', addSubject);
            document.getElementById('addFacultyBtn').addEventListener('click', addFaculty);
            document.getElementById('confirmResetBtn').addEventListener('click', confirmReset);

            // Applica il tema salvato
            applyTheme();

            // Ascolta i cambiamenti del tema da altre pagine
            window.addEventListener('storage', function (e) {
                if (e.key === 'temaPreferito') {
                    applyTheme();
                }
            });
        }

        function applyTheme() {
            // Leggi il tema dalle altre pagine o usa default
            const tema = localStorage.getItem('temaPreferito') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            if (tema === 'dark') {
                document.body.classList.add('bg-dark', 'text-white');
                document.body.classList.remove('bg-body');
                document.documentElement.setAttribute('data-bs-theme', 'dark');

                // Applica classi per header e card
                document.querySelector('header').classList.add('bg-dark', 'border-secondary');
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.add('bg-dark', 'text-white');
                });
                document.querySelectorAll('.card-header').forEach(header => {
                    header.classList.add('bg-dark', 'border-secondary');
                });
                document.querySelectorAll('.table').forEach(table => {
                    table.classList.add('table-dark');
                });
                document.querySelectorAll('.nav-tabs .nav-link').forEach(link => {
                    if (link.classList.contains('active')) {
                        link.classList.add('bg-dark', 'border-dark', 'text-white');
                    }
                });
                document.querySelectorAll('.btn-link').forEach(btn => {
                    btn.classList.add('text-white');
                });
            } else {
                document.body.classList.remove('bg-dark', 'text-white');
                document.body.classList.add('bg-body');
                document.documentElement.setAttribute('data-bs-theme', 'light');

                // Ripristina classi light
                document.querySelector('header').classList.remove('bg-dark', 'border-secondary');
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.remove('bg-dark', 'text-white');
                });
                document.querySelectorAll('.card-header').forEach(header => {
                    header.classList.remove('bg-dark', 'border-secondary');
                });
                document.querySelectorAll('.table').forEach(table => {
                    table.classList.remove('table-dark');
                });
                document.querySelectorAll('.nav-tabs .nav-link').forEach(link => {
                    if (link.classList.contains('active')) {
                        link.classList.remove('bg-dark', 'border-dark', 'text-white');
                    }
                });
                document.querySelectorAll('.btn-link').forEach(btn => {
                    btn.classList.remove('text-white');
                });
            }
        }

        function loadUsers() {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = users.map(user => `
                <tr>
                    <td class="ps-4 fw-semibold">${user.id}</td>
                    <td>${user.firstName} ${user.lastName}</td>
                    <td>${user.email}</td>
                    <td>${user.university || 'Non specificata'}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-outline-primary btn-sm me-2" onclick="openResetPassword(${user.id})">
                            <i class="fas fa-key me-1"></i>Reset
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteUser(${user.id})">
                            <i class="fas fa-trash-alt me-1"></i>Elimina
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function loadAnnouncements() {
            const tbody = document.getElementById('announcementsTableBody');
            tbody.innerHTML = announcements.map(a => `
                <tr>
                    <td class="ps-4">
                        <span class="badge ${a.status === 'venduto' ? 'bg-success' : 'bg-primary'} me-2">${a.status === 'venduto' ? 'Venduto' : 'Attivo'}</span>
                        <span class="text-truncate d-inline-block" style="max-width:200px" title="${a.title}">${a.title}</span>
                    </td>
                    <td><span class="badge bg-secondary">${a.type}</span></td>
                    <td class="fw-semibold">€${parseFloat(a.price).toFixed(2)}</td>
                    <td>${a.seller}</td>
                    <td>${a.publishedDate}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteAnnouncement(${a.id})">
                            <i class="fas fa-trash-alt me-1"></i>Elimina
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function loadSubjects() {
            const container = document.getElementById('subjectsList');
            container.innerHTML = subjects.map((s, i) => `
                <div class="category-item d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-medium">${s}</span>
                    <button class="btn btn-link text-danger p-0" onclick="deleteSubject(${i})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
        }

        function loadFaculties() {
            const container = document.getElementById('facultiesList');
            container.innerHTML = faculties.map((f, i) => `
                <div class="category-item d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-medium">${f}</span>
                    <button class="btn btn-link text-danger p-0" onclick="deleteFaculty(${i})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
        }

        function updateStats() {
            // Le statistiche sono già calcolate dal PHP e mostrate staticamente
            // Questa funzione può essere usata per aggiornamenti in tempo reale se necessario
        }

        function addSubject() {
            const input = document.getElementById('newSubject');
            const subject = input.value.trim();
            if (!subject) return alert('Inserisci il nome della materia');
            if (subjects.includes(subject)) return alert('Materia già esistente');

            // Invia richiesta AJAX per aggiungere la materia al database
            fetch('api/add_subject.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'subject=' + encodeURIComponent(subject)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        subjects.push(subject);
                        loadSubjects();
                        input.value = '';
                        showToast('Materia aggiunta');
                    } else {
                        alert('Errore: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    alert('Errore di connessione');
                });
        }

        function addFaculty() {
            const input = document.getElementById('newFaculty');
            const faculty = input.value.trim();
            if (!faculty) return alert('Inserisci il nome della facoltà');
            if (faculties.includes(faculty)) return alert('Facoltà già esistente');

            // Invia richiesta AJAX per aggiungere la facoltà al database
            fetch('api/add_faculty.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'faculty=' + encodeURIComponent(faculty)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        faculties.push(faculty);
                        loadFaculties();
                        input.value = '';
                        showToast('Facoltà aggiunta');
                    } else {
                        alert('Errore: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    alert('Errore di connessione');
                });
        }

        function confirmReset() {
            const newPassword = document.getElementById('newPassword').value;
            if (newPassword.length < 6) return alert('Password di almeno 6 caratteri');
            if (selectedUser) {
                // Invia richiesta AJAX per resettare la password
                fetch('api/reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=' + selectedUser.id + '&new_password=' + encodeURIComponent(newPassword)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
                            showToast(`Password aggiornata per ${selectedUser.email}`);
                            selectedUser = null;
                            document.getElementById('newPassword').value = '';
                        } else {
                            alert('Errore: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Errore:', error);
                        alert('Errore di connessione');
                    });
            }
        }

        function showToast(message) {
            const toast = new bootstrap.Toast(document.createElement('div'));
            toast._element.className = 'toast bg-success text-white';
            toast._element.innerHTML = `<div class="toast-body">${message}</div>`;
            document.body.appendChild(toast._element);
            toast.show();
            setTimeout(() => toast._element.remove(), 3000);
        }

        window.openResetPassword = function (userId) {
            selectedUser = users.find(u => u.id == userId);
            if (selectedUser) {
                document.getElementById('resetPasswordUserInfo').innerHTML = `
                    Inserisci la nuova password per <strong>${selectedUser.firstName} ${selectedUser.lastName}</strong>
                    (<strong>${selectedUser.email}</strong>)
                `;
                new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
            }
        };

        window.deleteUser = function (userId) {
            if (confirm('Eliminare questo utente?')) {
                // Invia richiesta AJAX per eliminare l'utente
                fetch('api/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=' + userId
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const index = users.findIndex(u => u.id == userId);
                            if (index > -1) {
                                users.splice(index, 1);
                                loadUsers();
                                // Aggiorna statistiche
                                document.getElementById('totalUsers').textContent = users.length;
                                showToast('Utente eliminato');
                            }
                        } else {
                            alert('Errore: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Errore:', error);
                        alert('Errore di connessione');
                    });
            }
        };

        window.deleteAnnouncement = function (announcementId) {
            if (confirm('Eliminare questo annuncio?')) {
                // Invia richiesta AJAX per eliminare l'annuncio
                fetch('api/delete_announcement.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'announcement_id=' + announcementId
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const index = announcements.findIndex(a => a.id == announcementId);
                            if (index > -1) {
                                announcements.splice(index, 1);
                                loadAnnouncements();
                                // Aggiorna statistiche
                                const activeCount = announcements.filter(a => a.status === 'attivo').length;
                                document.getElementById('activeAnnouncements').textContent = activeCount;
                                showToast('Annuncio eliminato');
                            }
                        } else {
                            alert('Errore: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Errore:', error);
                        alert('Errore di connessione');
                    });
            }
        };

        window.deleteSubject = function (index) {
            const subject = subjects[index];
            if (confirm(`Eliminare la materia "${subject}"?`)) {
                // Invia richiesta AJAX per eliminare la materia
                fetch('api/delete_subject.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'subject=' + encodeURIComponent(subject)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            subjects.splice(index, 1);
                            loadSubjects();
                            showToast('Materia rimossa');
                        } else {
                            alert('Errore: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Errore:', error);
                        alert('Errore di connessione');
                    });
            }
        };

        window.deleteFaculty = function (index) {
            const faculty = faculties[index];
            if (confirm(`Eliminare la facoltà "${faculty}"?`)) {
                // Invia richiesta AJAX per eliminare la facoltà
                fetch('api/delete_faculty.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'faculty=' + encodeURIComponent(faculty)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            faculties.splice(index, 1);
                            loadFaculties();
                            showToast('Facoltà rimossa');
                        } else {
                            alert('Errore: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Errore:', error);
                        alert('Errore di connessione');
                    });
            }
        };

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>

</html>