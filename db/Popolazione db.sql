INSERT INTO facolta (nome_facolta) VALUES 
('Ingegneria'),
('Giurisprudenza'),
('Scienze'),
('Informatica'),
('Farmacia'),
('Lettere e Filosofia'),
('Economia'),
('Medicina');

INSERT INTO categoria_prodotto (nome_categoria) VALUES 
('Libro'),
('Appunti'),
('PDF');

INSERT INTO condizione_prodotto (nome_condizione) VALUES 
('Nuovo'),
('Come nuovo'),
('Buono'),
('Discreto'),
('Usato');

INSERT INTO utenti (nome, cognome, email, password, facolta_id, isAdmin) VALUES 
('Marco', 'Bianchi', 'marco.bianchi@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, TRUE),
('Giulia', 'Rossi', 'giulia.rossi@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, FALSE),
('Luca', 'Verdi', 'luca.verdi@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, FALSE),
('Anna', 'Ferrari', 'anna.ferrari@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, FALSE),
('Paolo', 'Russo', 'paolo.russo@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, FALSE),
('Sofia', 'Romano', 'sofia.romano@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, FALSE),
('Francesco', 'Gallo', 'francesco.gallo@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 7, FALSE),
('Elena', 'Conti', 'elena.conti@studio.unibo.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 8, FALSE);

INSERT INTO corso_studio (nome_corso, facolta_id) VALUES 
('Analisi Matematica 1', 1),
('Fisica Generale 1', 1),

('Diritto Costituzionale', 2),

('Chimica Organica', 3),

('Programmazione Java', 4),

('Storia Contemporanea', 6),

('Economia Aziendale', 7),

('Anatomia Umana', 8);

INSERT INTO annuncio (titolo, descrizione, prezzo, categoria_id, condizione_id, 
                     is_digitale, immagine_url, venditore_id, corso_id, facolta_id) VALUES 
('Analisi Matematica 1 - Bramanti', 
 'Libro di testo in ottime condizioni, usato solo per un semestre. Include tutti gli esercizi risolti e appunti a margine utili per la preparazione dell''esame.', 
 25.00, 1, 2, FALSE, 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e', 1, 1, 1),

('Appunti Diritto Costituzionale', 
 'Appunti completi del corso 2024/2025, scritti a mano e molto dettagliati. Include tutte le lezioni, i riferimenti normativi e i casi studio discussi in aula.', 
 15.00, 2, 3, FALSE, 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85', 2, 3, 2),

('Fisica Generale 1 - Mazzoldi Nigro Voci', 
 'Libro classico per il corso di Fisica 1. Qualche sottolineatura a matita ma in generale ottime condizioni. Copertina integra e pagine bianche.', 
 30.00, 1, 3, FALSE, 'https://images.unsplash.com/photo-1532094349884-543bc11b234d', 3, 2, 1),

('Appunti Programmazione Java', 
 'Appunti digitali in PDF dal corso completo, con esempi di codice ed esercizi risolti. Formato organizzato per capitoli, perfetto per lo studio autonomo.', 
 12.00, 3, 1, TRUE, 'https://images.unsplash.com/photo-1516116216624-53e697fedbea', 5, 5, 4),

('Chimica Organica - Bruice', 
 'Libro in editrice italiana, mai usato. Regalo perché ho cambiato corso di laurea. Ancora nella plastica originale, completamente nuovo.', 
 45.00, 1, 1, FALSE, 'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69', 4, 4, 3),

('Appunti Storia Contemporanea', 
 'Appunti delle lezioni integrati con il libro. Riassunti completi per ogni capitolo, linee temporali e schemi concettuali per memorizzare gli eventi storici.', 
 10.00, 2, 3, FALSE, 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173', 2, 6, 6),

('Economia Aziendale - Airoldi Brunetti Coda', 
 'Libro ben tenuto con alcuni appunti a margine. Ottimo per preparare l''esame, include esempi pratici e casi aziendali reali.', 
 28.00, 1, 3, FALSE, 'https://images.unsplash.com/photo-1554224155-6726b3ff858f', 7, 7, 7),

('Appunti Anatomia Umana', 
 'Appunti completi con illustrazioni e schemi anatomici. Perfetti per lo studio della materia, organizzati per sistemi (scheletrico, muscolare, nervoso).', 
 20.00, 2, 2, FALSE, 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56', 8, 8, 8),

('Advanced Java Programming', 
 'PDF con esempi avanzati di programmazione Java, design patterns e best practices. Aggiornato alle ultime versioni del linguaggio.', 
 18.50, 3, 1, TRUE, 'https://images.unsplash.com/photo-1555066931-4365d14bab8c', 5, 5, 4),

('Eserciziario Analisi Matematica', 
 'PDF con oltre 300 esercizi risolti e commentati. Perfetto per la preparazione degli esami di Analisi 1 e 2.', 
 14.99, 3, 1, TRUE, 'https://images.unsplash.com/photo-1509228468518-180dd4864904', 1, 1, 1);

INSERT INTO annuncio_pdf (annuncio_id, pdf_path, original_filename) VALUES 
(4, 'pdfs/appunti_java.pdf', 'Appunti_Programmazione_Java_Completo.pdf'),
(9, 'pdfs/advanced_java.pdf', 'Advanced_Java_Programming_2024.pdf'),
(10, 'pdfs/esercizi_analisi.pdf', 'Eserciziario_Analisi_Matematica.pdf');

INSERT INTO vendita (annuncio_id, acquirente_id, venditore_id, prezzo_vendita, data_vendita) VALUES 
(4, 2, 5, 12.00, '2024-01-15 10:30:00'),

(4, 3, 5, 12.00, '2024-01-16 14:20:00'),

(4, 4, 5, 12.00, '2024-01-17 09:15:00'),

(9, 6, 5, 18.50, '2024-01-18 11:45:00'),

(9, 7, 5, 18.50, '2024-01-19 16:30:00'),

(1, 8, 1, 25.00, '2024-01-20 13:10:00'),

(10, 2, 1, 14.99, '2024-01-21 15:00:00'),

(10, 3, 1, 14.99, '2024-01-21 17:30:00'),

(3, 4, 3, 30.00, '2024-01-22 10:00:00');

UPDATE annuncio SET is_venduto = TRUE WHERE id_annuncio IN (1, 3);

INSERT INTO carrello (utente_id, annuncio_id) VALUES 
(2, 2),
(3, 5),
(4, 7),
(5, 8);

INSERT INTO preferiti (utente_id, annuncio_id) VALUES 
(1, 2),  
(2, 1),
(2, 9),
(3, 4),
(3, 10),
(4, 6), 
(5, 3);