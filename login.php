<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$conn = $database->connect();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT id, email, password
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['loggedin'] = true;
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['email']    = $user['email'];

        // ADMIN = ID 1
        $_SESSION['is_admin'] = ($user['id'] == 1);

        header('Location: index.php');
        exit;
    } else {
        $error_message = 'Credenziali non valide.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        (function(){
            try{
                const tema = localStorage.getItem('temaPreferito') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', tema);
            }catch(e){console.warn('Impossibile applicare tema:', e)}
        })();
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/style/style.css">
    
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 p-0">
                <header class="p-3 border-bottom mb-2 bg-body">
                    <a href="index.php" class="text-body text-decoration-none d-flex align-items-center">
                        <i class="bi bi-arrow-left me-2"></i>Torna al marketplace
                    </a>
                </header>
            </div>
        </div>



        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-6 col-lg-4 p-3">

                <div class="card p-4 shadow">
                    <h1 class="h3 mb-3 fw-bold text-center">Accedi a UniMarket</h1>
                    <p class="text-muted mb-4 text-center">Benvenuto! Inserisci le tue credenziali per accedere</p>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <form action="login.php" method="post">
                        <div class="mb-3">
                            <label for="email">Email </label>
                            <input type="email" class="form-control border" id="email" name="email"
                                placeholder="nome.cognome@studio.unibo.it" autofocus required>
                        </div>

                        <div class="mb-3">
                            <label for="password">Password</label>
                            <input type="password" class="form-control border" placeholder="********" id="password" name="password" required>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label text-muted" for="remember">Ricordami</label>
                            </div>

                            <a href="#" class="text-decoration-none text-primary">Password dimenticata?</a>
                        </div>

                        <button type="submit" class="btn w-100 btn-dark mb-1">Accedi</button>
                    </form>
                    <p class="text-body mt-3 mb-0 text-center">Non hai un accacount? <a href="register.php" class="text-decoration-none text-improtant">Registrati ora</a></p>
                </div>
            </div>
    <!-- Bootstrap JS caricato in fondo per migliori prestazioni -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
