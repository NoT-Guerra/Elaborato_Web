<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vendita_id']) && isset($_POST['annuncio_id'])) {
    $vendita_id = intval($_POST['vendita_id']);
    $annuncio_id = intval($_POST['annuncio_id']);
    $user_id = $_SESSION['user_id'];
    
    // Verifica che l'utente sia l'acquirente legittimo di questo PDF
    $sql = "SELECT v.id_vendita, ap.pdf_path, ap.original_filename
            FROM vendita v
            JOIN annuncio a ON v.annuncio_id = a.id_annuncio
            LEFT JOIN annuncio_pdf ap ON a.id_annuncio = ap.annuncio_id
            WHERE v.id_vendita = ? 
            AND v.acquirente_id = ?
            AND a.id_annuncio = ?
            AND ap.pdf_path IS NOT NULL";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $vendita_id, $user_id, $annuncio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $pdf_path = $row['pdf_path'];
        $original_filename = $row['original_filename'];
        
        // Verifica che il file esista
        if (file_exists($pdf_path)) {
            // Imposta gli header per il download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $original_filename . '"');
            header('Content-Length: ' . filesize($pdf_path));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Leggi e invia il file
            readfile($pdf_path);
            exit;
        } else {
            $_SESSION['error_message'] = "Il file PDF non è più disponibile. Contatta l'assistenza.";
            header('Location: miei_acquisti.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Non sei autorizzato a scaricare questo file.";
        header('Location: miei_acquisti.php');
        exit;
    }
} else {
    $_SESSION['error_message'] = "Richiesta non valida.";
    header('Location: miei_acquisti.php');
    exit;
}
?>