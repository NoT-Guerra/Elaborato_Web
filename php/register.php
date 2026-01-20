<?php
// register.php

session_start();
require_once 'config/database.php'; // Il tuo file di configurazione del database

// Inizializza le variabili
$nome = $cognome = $email = $facolta = $password = $confirm_password = '';
$errors = [];
$success = false;

// Controlla se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pulisci e valida i dati
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $facolta_id = intval($_POST['facolta'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms_accepted = isset($_POST['terms']);

    // Validazione
    if (empty($nome)) {
        $errors['nome'] = 'Il nome è obbligatorio';
    } elseif (strlen($nome) < 2) {
        $errors['nome'] = 'Il nome deve contenere almeno 2 caratteri';
    }

    if (empty($cognome)) {
        $errors['cognome'] = 'Il cognome è obbligatorio';
    } elseif (strlen($cognome) < 2) {
        $errors['cognome'] = 'Il cognome deve contenere almeno 2 caratteri';
    }

    if (empty($email)) {
        $errors['email'] = 'L\'email è obbligatoria';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Inserisci un indirizzo email valido';
    } elseif (!preg_match('/@studio\.unibo\.it$/i', $email)) {
        $errors['email'] = 'Devi usare un\'email istituzionale (@studio.unibo.it)';
    }

    if ($facolta_id <= 0) {
        $errors['facolta'] = 'Seleziona una facoltà';
    }

    if (empty($password)) {
        $errors['password'] = 'La password è obbligatoria';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'La password deve contenere almeno 8 caratteri';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Le password non coincidono';
    }

    if (!$terms_accepted) {
        $errors['terms'] = 'Devi accettare i termini e condizioni';
    }

    // Se non ci sono errori, procedi con la registrazione
    if (empty($errors)) {
        // Controlla se l'email esiste già
        $check_email_sql = "SELECT id_utente FROM utenti WHERE email = ?";
        $stmt = $conn->prepare($check_email_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors['email'] = 'Questa email è già registrata';
        } else {
            // Hash della password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Inserisci il nuovo utente
            $insert_sql = "INSERT INTO utenti (nome, cognome, email, password, facolta_id, data_registrazione) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssi", $nome, $cognome, $email, $password_hash, $facolta_id);
            
            if ($stmt->execute()) {
                // Successo!
                $success = true;
                
                // Pulisci il form
                $nome = $cognome = $email = $facolta = '';
                
                // Reindirizza al login dopo 3 secondi
                header("refresh:3;url=login.php");
            } else {
                $errors['database'] = 'Errore durante la registrazione. Riprova più tardi.';
                // Per debug: $errors['database'] = $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Recupera la lista delle facoltà dal database
$facolta_list = [];
$sql = "SELECT id_facolta, nome_facolta FROM facolta ORDER BY nome_facolta";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $facolta_list[] = $row;
    }
    $result->free();
} else {
    $errors['facolta_fetch'] = 'Errore nel caricamento delle facoltà';
}
?>
<!DOCTYPE html>
<html lang="it" data-bs-theme="<?php echo $_SESSION['tema'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        }
    </style>
</head>

<body class="bg-body">
    <div class="container-fluid p-0 min-vh-100 d-flex flex-column">

        <!-- HEADER -->
        <header class="d-flex align-items-center bg-body p-3 border-bottom sticky-top">
            <a href="index.php" class="btn btn-link text-body p-0 me-3" aria-label="Torna indietro">
                <i class="bi bi-arrow-left fs-4"></i>
            </a>

            <div class="d-flex align-items-center">
                <span class="bg-primary p-2 rounded-3 me-3 fs-5">
                    <i class="bi bi-person-plus-fill text-white"></i>
                </span>
                <div>
                    <div class="fw-bold">Registrati</div>
                    <div class="text-muted small">Crea il tuo account</div>
                </div>
            </div>
        </header>

        <!-- FORM -->
        <main class="flex-grow-1 d-flex align-items-center justify-content-center px-3">
            <div class="card shadow-sm w-100" style="max-width: 420px;">
                <div class="card-body p-4">
                    
                    <?php if ($success): ?>
                        <div class="success-message text-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Registrazione completata con successo!</strong>
                            <p class="mb-0 mt-2">Verrai reindirizzato alla pagina di login tra 3 secondi...</p>
                            <a href="login.php" class="btn btn-sm btn-outline-success mt-2">Vai al login ora</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($errors['database']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                        
                        <!-- Nome e Cognome -->
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control <?php echo isset($errors['nome']) ? 'is-invalid' : ''; ?>" 
                                       name="nome" placeholder="Mario" 
                                       value="<?php echo htmlspecialchars($nome); ?>" required>
                                <?php if (isset($errors['nome'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['nome']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Cognome</label>
                                <input type="text" class="form-control <?php echo isset($errors['cognome']) ? 'is-invalid' : ''; ?>" 
                                       name="cognome" placeholder="Rossi" 
                                       value="<?php echo htmlspecialchars($cognome); ?>" required>
                                <?php if (isset($errors['cognome'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['cognome']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label">Email istituzionale</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   name="email" placeholder="nome.cognome@studio.unibo.it" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                            <div class="form-text">Devi utilizzare la tua email istituzionale @studio.unibo.it</div>
                        </div>

                        <!-- Facoltà -->
                        <div class="mb-3">
                            <label class="form-label">Facoltà</label>
                            <select class="form-select <?php echo isset($errors['facolta']) ? 'is-invalid' : ''; ?>" 
                                    name="facolta" required>
                                <option value="" selected disabled>Seleziona facoltà</option>
                                <?php foreach ($facolta_list as $facolta_item): ?>
                                    <option value="<?php echo $facolta_item['id_facolta']; ?>" 
                                        <?php echo (isset($facolta_id) && $facolta_id == $facolta_item['id_facolta']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($facolta_item['nome_facolta']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['facolta'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['facolta']); ?></div>
                            <?php endif; ?>
                            <?php if (isset($errors['facolta_fetch'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['facolta_fetch']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" id="password" 
                                       class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                       name="password" placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswords(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                            <div class="form-text">Minimo 8 caratteri</div>
                        </div>

                        <!-- Conferma Password -->
                        <div class="mb-3">
                            <label class="form-label">Conferma password</label>
                            <input type="password" id="confirmPassword" 
                                   class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   name="confirm_password" placeholder="••••••••" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Termini e condizioni -->
                        <div class="form-check mb-4">
                            <input class="form-check-input <?php echo isset($errors['terms']) ? 'is-invalid' : ''; ?>" 
                                   type="checkbox" name="terms" id="terms" required>
                            <label class="form-check-label small" for="terms">
                                Accetto i <a href="#" class="link-primary">termini e condizioni d'uso</a> e
                                l'<a href="#" class="link-primary">informativa sulla privacy</a>
                            </label>
                            <?php if (isset($errors['terms'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['terms']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            Registrati
                        </button>

                    </form>

                    <div class="text-center mt-4">
                        <span class="text-muted small">Hai già un account?</span>
                        <a href="login.php" class="fw-bold ms-1">Accedi</a>
                    </div>

                </div>
            </div>
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
        
        function togglePasswords(btn) {
            const password = document.getElementById("password");
            const confirm = document.getElementById("confirmPassword");
            const icon = btn.querySelector("i");

            const show = password.type === "password";

            password.type = show ? "text" : "password";
            confirm.type = show ? "text" : "password";

            icon.classList.toggle("bi-eye", !show);
            icon.classList.toggle("bi-eye-slash", show);
        }
        
        // Tema
        (function () {
            try {
                const tema = localStorage.getItem('temaPreferito') ||
                    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', tema);
            } catch (e) { }
        })();
    </script>
</body>
</html> 