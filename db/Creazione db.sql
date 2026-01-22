CREATE DATABASE marketplace_universitario;
USE marketplace_universitario;

CREATE TABLE facolta (
    id_facolta INT AUTO_INCREMENT PRIMARY KEY,
    nome_facolta VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE utenti (
    id_utente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    facolta_id INT,
    isAdmin BOOLEAN DEFAULT FALSE,
    data_registrazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facolta_id) REFERENCES facolta(id_facolta)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE corso_studio (
    id_corso INT AUTO_INCREMENT PRIMARY KEY,
    nome_corso VARCHAR(100) NOT NULL,
    facolta_id INT NOT NULL,
    FOREIGN KEY (facolta_id) REFERENCES facolta(id_facolta)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categoria_prodotto (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE condizione_prodotto (
    id_condizione INT AUTO_INCREMENT PRIMARY KEY,
    nome_condizione VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE annuncio (
    id_annuncio INT AUTO_INCREMENT PRIMARY KEY,
    titolo VARCHAR(100) NOT NULL,
    descrizione TEXT,
    prezzo DECIMAL(8,2) NOT NULL,
    data_pubblicazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_modifica DATETIME ON UPDATE CURRENT_TIMESTAMP,

    categoria_id INT NOT NULL,
    condizione_id INT NOT NULL,
    is_digitale BOOLEAN DEFAULT FALSE,
    immagine_url VARCHAR(500),

    venditore_id INT NOT NULL,
    corso_id INT,
    facolta_id INT,

    is_attivo BOOLEAN DEFAULT TRUE,
    is_venduto BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (categoria_id) REFERENCES categoria_prodotto(id_categoria),
    FOREIGN KEY (condizione_id) REFERENCES condizione_prodotto(id_condizione),
    FOREIGN KEY (venditore_id) REFERENCES utenti(id_utente)
        ON DELETE CASCADE,
    FOREIGN KEY (corso_id) REFERENCES corso_studio(id_corso)
        ON DELETE SET NULL,
    FOREIGN KEY (facolta_id) REFERENCES facolta(id_facolta)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE carrello (
    id_carrello INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    annuncio_id INT NOT NULL,

    FOREIGN KEY (utente_id) REFERENCES utenti(id_utente)
        ON DELETE CASCADE,
    FOREIGN KEY (annuncio_id) REFERENCES annuncio(id_annuncio)
        ON DELETE CASCADE,

    UNIQUE (utente_id, annuncio_id)
) ENGINE=InnoDB;

CREATE TABLE preferiti (
    id_preferito INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    annuncio_id INT NOT NULL,

    FOREIGN KEY (utente_id) REFERENCES utenti(id_utente)
        ON DELETE CASCADE,
    FOREIGN KEY (annuncio_id) REFERENCES annuncio(id_annuncio)
        ON DELETE CASCADE,

    UNIQUE (utente_id, annuncio_id)
) ENGINE=InnoDB;

CREATE TABLE vendita (
    id_vendita INT AUTO_INCREMENT PRIMARY KEY,
    annuncio_id INT NOT NULL,
    acquirente_id INT NOT NULL,
    venditore_id INT NOT NULL,
    prezzo_vendita DECIMAL(8,2) NOT NULL,
    data_vendita DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (annuncio_id) REFERENCES annuncio(id_annuncio),
    FOREIGN KEY (acquirente_id) REFERENCES utenti(id_utente),
    FOREIGN KEY (venditore_id) REFERENCES utenti(id_utente),
    UNIQUE KEY vendita_unica (annuncio_id, acquirente_id)
) ENGINE=InnoDB;

CREATE TABLE annuncio_pdf (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annuncio_id INT NOT NULL,
    pdf_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (annuncio_id) REFERENCES annuncio(id_annuncio) ON DELETE CASCADE
);