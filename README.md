# Marketplace Universitario

Un marketplace web dedicato agli studenti per la compravendita di libri, appunti e materiale didattico (sia fisico che digitale).

## Autori
- Botteghi Matteo 0001129907
- Cristian Qorri 0001129476
- Nicholas Guerra 0001125129

## Funzionalità Principali

### Autenticazione e Profilo
- Registrazione e Login differenziati (User/Admin).
- Recupero password.
- Gestione profilo utente con preferenze di facoltà.

### Gestione Annunci
- Inserimento annunci per libri (fisici) e appunti (digitali PDF).
- Caricamento e anteprima PDF per gli appunti.
- Filtri avanzati per categoria, condizione, facoltà e corso di studi.
- Gestione carrello e lista dei desideri (Preferiti).

### Processo d'Acquisto
- Carrello persistente.
- Checkout simulato con riepilogo ordine.
- Download immediato per i contenuti digitali acquistati.

### Pannello Admin
- Gestione utenti e moderazione annunci.
- Statistiche generali del marketplace.

## Tech Stack
- **Backend**: PHP 
- **Database**: MySQL normalizzato in 3NF
- **Frontend**: HTML5, CSS, JavaScript
- **Web Server**: Apache (XAMPP)

## Database
Il database modella un marketplace universitario garantendo normalizzazione (3NF), integrità dei dati e supporto alla vendita multipla di contenuti digitali.
- VIene separato il concetto di **oggetto** e **annuncio**: un oggetto rappresenta il bene, mentre l’annuncio rappresenta l’atto di vendita.
- Gli appunti digitali non hanno quantità limitata. Ogni vendita viene tracciata, senza eliminare l’annuncio o l’oggetto.

## Installazione
1. Clonare la repository.
2. Importare il database utilizzando i file in `/db`:
   - Eseguire prima `Creazione db.sql`.
   - Eseguire `Popolazione db.sql` per dati di test.
3. Configurare la connessione al database in `app/db_connection.php` (o file equivalente).
4. Avviare un server locale e puntare alla directory `public/index.php`.

## Struttura del Progetto
- `app/`: Logica core e configurazioni.
- `db/`: Script SQL per schema e dati.
- `public/`: Entry point dell'applicazione, asset e pagine front-facing.
- `pdfs/`: Storage per i file digitali caricati.
- `images/`: Risorse statiche e immagini degli annunci.
