<?php
// Configurazione database MySQL locale
$servername = "localhost";
$username = "root"; // Inserisci il tuo username MySQL
$password = ""; // Inserisci la tua password MySQL
$database = "marketplace_universitario";

// Crea connessione
$conn = new mysqli($servername, $username, $password, $database);

// Verifica connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Imposta il charset, per gestire anche le emoji
$conn->set_charset("utf8mb4");
?>