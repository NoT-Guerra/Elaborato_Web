<?php
session_start();
require_once 'config/database.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {

        // 1. Query aggiornata con i nomi corretti delle colonne (id_utente)
        // Recuperiamo l'hash della password salvato nel DB
        $stmt = $conn->prepare("
            SELECT u.id_utente, u.nome, u.cognome, u.email, u.password, f.nome_facolta
            FROM utenti u
            LEFT JOIN facolta f ON u.facolta_id = f.id_facolta
            WHERE u.email = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            // 2. VERIFICA DELL'HASH:
            // password_verify confronta la stringa in chiaro col l'hash memorizzato
            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                // Salviamo i dati corretti in sessione
                $_SESSION['loggedin']    = true;
                $_SESSION['user_id']     = $user['id_utente'];
                $_SESSION['email']       = $user['email'];
                $_SESSION['nome']        = $user['nome'];
                $_SESSION['cognome']     = $user['cognome'];
                $_SESSION['nome_facolta'] = $user['nome_facolta'] ?? 'Nessuna';

                // ADMIN = ID 1 (riferito a id_utente)
                $_SESSION['is_admin'] = ($user['id_utente'] == 1);

                header('Location: index.php');
                exit;
            } else {
                // Per sicurezza, usiamo un messaggio generico
                $error_message = 'Email o password errati.';
            }
        } else {
            $error_message = 'Errore interno del server.';
        }
    } else {
        $error_message = 'Per favore, compila tutti i campi.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - UniMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        (function(){
            try{
                const tema = localStorage.getItem('temaPreferito') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', tema);
            }catch(e){console.warn('Tema non caricato:', e)}
        })();
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/style/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <header class="p-3 border-bottom mb-2 bg-body">
                <a href="index.php" class="text-body text-decoration-none d-flex align-items-center">
                    <i class="bi bi-arrow-left me-2"></i>Torna al marketplace
                </a>
            </header>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-6 col-lg-4 p-3">
                <div class="card p-4 shadow">
                    <h1 class="h3 mb-3 fw-bold text-center">Accedi a UniMarket</h1>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <form action="login.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="nome.cognome@studio.unibo.it" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="********" required>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a href="#" class="text-decoration-none small">Password dimenticata?</a>
                        </div>

                        <button type="submit" class="btn btn-dark w-100">Accedi</button>
                    </form>
                    
                    <p class="mt-4 text-center mb-0">
                        Non hai un account? <a href="register.php" class="fw-bold text-decoration-none">Registrati</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>