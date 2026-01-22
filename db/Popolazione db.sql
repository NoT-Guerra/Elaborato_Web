-- 1. POPOLAMENTO FACOLTA
INSERT INTO facolta (nome_facolta) VALUES 
('Ingegneria'),
('Giurisprudenza'),
('Scienze'),
('Informatica'),
('Farmacia'),
('Lettere e Filosofia'),
('Economia'),
('Medicina');

-- 2. POPOLAMENTO CATEGORIE PRODOTTI
INSERT INTO categoria_prodotto (nome_categoria) VALUES 
('Libro'),
('Appunti'),
('PDF');

-- 3. POPOLAMENTO CONDIZIONI PRODOTTO
INSERT INTO condizione_prodotto (nome_condizione) VALUES 
('Nuovo'),
('Come nuovo'),
('Buono'),
('Discreto'),
('Usato');

-- 4. POPOLAMENTO UTENTI 
INSERT INTO utenti (nome, cognome, email, password, facolta_id, isAdmin) VALUES 
('Marco', 'Bianchi', 'marco.bianchi@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, TRUE),
('Giulia', 'Rossi', 'giulia.rossi@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, FALSE),
('Luca', 'Verdi', 'luca.verdi@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, FALSE),
('Anna', 'Ferrari', 'anna.ferrari@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, FALSE),
('Paolo', 'Russo', 'paolo.russo@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, FALSE),
('Sofia', 'Romano', 'sofia.romano@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, FALSE),
('Francesco', 'Gallo', 'francesco.gallo@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 7, FALSE),
('Elena', 'Conti', 'elena.conti@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 8, FALSE);

-- 5. POPOLAMENTO CORSI DI STUDIO 
INSERT INTO corso_studio (nome_corso, facolta_id) VALUES 
-- Ingegneria (id_facolta = 1)
('Analisi Matematica 1', 1),
('Fisica Generale 1', 1),

-- Giurisprudenza (id_facolta = 2)
('Diritto Costituzionale', 2),

-- Scienze (id_facolta = 3)
('Chimica Organica', 3),

-- Informatica (id_facolta = 4)
('Programmazione Java', 4),

-- Lettere e Filosofia (id_facolta = 6)
('Storia Contemporanea', 6),

-- Economia (id_facolta = 7)
('Economia Aziendale', 7),

-- Medicina (id_facolta = 8)
('Anatomia Umana', 8);

-- 6. POPOLAMENTO ANNUNCI - MODIFICATO PER ADEGUARSI ALLA NUOVA STRUTTURA
INSERT INTO annuncio (titolo, descrizione, prezzo, categoria_id, condizione_id, 
                     is_digitale, immagine_url, venditore_id, corso_id, facolta_id) VALUES 
-- Annuncio 1: Analisi Matematica 1 - Bramanti (LIBRO FISICO)
('Analisi Matematica 1 - Bramanti', 
 'Libro di testo in ottime condizioni, usato solo per un semestre. Include tutti gli esercizi risolti e appunti a margine utili per la preparazione dell''esame.', 
 25.00, 1, 2, FALSE, 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e', 1, 1, 1),

-- Annuncio 2: Appunti Diritto Costituzionale (APPUNTI FISICI)
('Appunti Diritto Costituzionale', 
 'Appunti completi del corso 2024/2025, scritti a mano e molto dettagliati. Include tutte le lezioni, i riferimenti normativi e i casi studio discussi in aula.', 
 15.00, 2, 3, FALSE, 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85', 2, 3, 2),

-- Annuncio 3: Fisica Generale 1 - Mazzoldi Nigro Voci (LIBRO FISICO)
('Fisica Generale 1 - Mazzoldi Nigro Voci', 
 'Libro classico per il corso di Fisica 1. Qualche sottolineatura a matita ma in generale ottime condizioni. Copertina integra e pagine bianche.', 
 30.00, 1, 3, FALSE, 'https://images.unsplash.com/photo-1532094349884-543bc11b234d', 3, 2, 1),

-- Annuncio 4: Appunti Programmazione Java (PDF DIGITALE)
('Appunti Programmazione Java', 
 'Appunti digitali in PDF dal corso completo, con esempi di codice ed esercizi risolti. Formato organizzato per capitoli, perfetto per lo studio autonomo.', 
 12.00, 3, 1, TRUE, 'https://images.unsplash.com/photo-1516116216624-53e697fedbea', 5, 5, 4),

-- Annuncio 5: Chimica Organica - Bruice (LIBRO FISICO)
('Chimica Organica - Bruice', 
 'Libro in editrice italiana, mai usato. Regalo perché ho cambiato corso di laurea. Ancora nella plastica originale, completamente nuovo.', 
 45.00, 1, 1, FALSE, 'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69', 4, 4, 3),

-- Annuncio 6: Appunti Storia Contemporanea (APPUNTI FISICI)
('Appunti Storia Contemporanea', 
 'Appunti delle lezioni integrati con il libro. Riassunti completi per ogni capitolo, linee temporali e schemi concettuali per memorizzare gli eventi storici.', 
 10.00, 2, 3, FALSE, 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173', 2, 6, 6),

-- Annuncio 7: Economia Aziendale - Airoldi Brunetti Coda (LIBRO FISICO)
('Economia Aziendale - Airoldi Brunetti Coda', 
 'Libro ben tenuto con alcuni appunti a margine. Ottimo per preparare l''esame, include esempi pratici e casi aziendali reali.', 
 28.00, 1, 3, FALSE, 'https://images.unsplash.com/photo-1554224155-6726b3ff858f', 7, 7, 7),

-- Annuncio 8: Appunti Anatomia Umana (APPUNTI FISICI)
('Appunti Anatomia Umana', 
 'Appunti completi con illustrazioni e schemi anatomici. Perfetti per lo studio della materia, organizzati per sistemi (scheletrico, muscolare, nervoso).', 
 20.00, 2, 2, FALSE, 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56', 8, 8, 8),

-- Annuncio 9: PDF Advanced Java Programming (PDF DIGITALE - MULTIPLI ACQUISTI POSSIBILI)
('Advanced Java Programming', 
 'PDF con esempi avanzati di programmazione Java, design patterns e best practices. Aggiornato alle ultime versioni del linguaggio.', 
 18.50, 3, 1, TRUE, 'https://images.unsplash.com/photo-1555066931-4365d14bab8c', 5, 5, 4),

-- Annuncio 10: PDF Analisi Matematica Esercizi (PDF DIGITALE)
('Eserciziario Analisi Matematica', 
 'PDF con oltre 300 esercizi risolti e commentati. Perfetto per la preparazione degli esami di Analisi 1 e 2.', 
 14.99, 3, 1, TRUE, 'https://images.unsplash.com/photo-1509228468518-180dd4864904', 1, 1, 1);

-- 7. POPOLAMENTO ANNUNCIO_PDF PER I PDF DIGITALI
INSERT INTO annuncio_pdf (annuncio_id, pdf_path, original_filename) VALUES 
(4, 'pdfs/appunti_java.pdf', 'Appunti_Programmazione_Java_Completo.pdf'),
(9, 'pdfs/advanced_java.pdf', 'Advanced_Java_Programming_2024.pdf'),
(10, 'pdfs/esercizi_analisi.pdf', 'Eserciziario_Analisi_Matematica.pdf');

-- 8. POPOLAMENTO VENDITE (ESEMPI DI ACQUISTI)
-- Nota: ora possono esserci più vendite per lo stesso annuncio se è digitale
INSERT INTO vendita (annuncio_id, acquirente_id, venditore_id, prezzo_vendita, data_vendita) VALUES 
-- Vendita 1: Utente 2 acquista PDF Java (annuncio 4)
(4, 2, 5, 12.00, '2024-01-15 10:30:00'),

-- Vendita 2: Utente 3 acquista lo stesso PDF Java (annuncio 4) - ORA POSSIBILE!
(4, 3, 5, 12.00, '2024-01-16 14:20:00'),

-- Vendita 3: Utente 4 acquista PDF Java (annuncio 4) - TERZO ACQUIRENTE!
(4, 4, 5, 12.00, '2024-01-17 09:15:00'),

-- Vendita 4: Utente 6 acquista PDF Advanced Java (annuncio 9)
(9, 6, 5, 18.50, '2024-01-18 11:45:00'),

-- Vendita 5: Utente 7 acquista PDF Advanced Java (annuncio 9)
(9, 7, 5, 18.50, '2024-01-19 16:30:00'),

-- Vendita 6: Utente 8 acquista libro fisico Analisi (annuncio 1) - SOLO UNA VOLTA
(1, 8, 1, 25.00, '2024-01-20 13:10:00'),

-- Vendita 7: Utente 2 acquista PDF Analisi (annuncio 10)
(10, 2, 1, 14.99, '2024-01-21 15:00:00'),

-- Vendita 8: Utente 3 acquista PDF Analisi (annuncio 10)
(10, 3, 1, 14.99, '2024-01-21 17:30:00'),

-- Vendita 9: Utente 4 acquista libro fisico Fisica (annuncio 3)
(3, 4, 3, 30.00, '2024-01-22 10:00:00');

-- 9. AGGIORNAMENTO ANNUNCI VENDUTI (solo per prodotti fisici)
-- I prodotti digitali (is_digitale = TRUE) rimangono is_venduto = FALSE
UPDATE annuncio SET is_venduto = TRUE WHERE id_annuncio IN (1, 3);

-- 10. POPOLAMENTO CARRELLO (ESEMPI)
INSERT INTO carrello (utente_id, annuncio_id) VALUES 
(2, 2),  -- Utente 2 ha appunti diritto nel carrello
(3, 5),  -- Utente 3 ha chimica nel carrello
(4, 7),  -- Utente 4 ha economia nel carrello
(5, 8);  -- Utente 5 ha anatomia nel carrello

-- 11. POPOLAMENTO PREFERITI (ESEMPI)
INSERT INTO preferiti (utente_id, annuncio_id) VALUES 
(1, 2),  -- Admin mette tra preferiti appunti diritto
(2, 1),  -- Utente 2 mette tra preferiti analisi
(2, 9),  -- Utente 2 mette tra preferiti advanced java
(3, 4),  -- Utente 3 mette tra preferiti pdf java
(3, 10), -- Utente 3 mette tra preferiti pdf analisi
(4, 6),  -- Utente 4 mette tra preferiti storia
(5, 3);  -- Utente 5 mette tra preferiti fisica