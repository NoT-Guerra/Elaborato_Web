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
('Giulia', 'Rossi', 'giulia.rossi@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, FALSE),
('Luca', 'Verdi', 'luca.verdi@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, FALSE),
('Anna', 'Ferrari', 'anna.ferrari@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, FALSE),
('Paolo', 'Russo', 'paolo.russo@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, FALSE),
('Sofia', 'Romano', 'sofia.romano@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, FALSE),
('Francesco', 'Gallo', 'francesco.gallo@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 7, FALSE),
('Elena', 'Conti', 'elena.conti@university.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 8, FALSE);

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

-- 6. POPOLAMENTO ANNUNCI
INSERT INTO annuncio (titolo, descrizione, prezzo, categoria_id, condizione_id, 
                     immagine_url, venditore_id, corso_id, facolta_id) VALUES 
-- Annuncio 1: Analisi Matematica 1 - Bramanti
('Analisi Matematica 1 - Bramanti', 
 'Libro di testo in ottime condizioni, usato solo per un semestre. Include tutti gli esercizi risolti e appunti a margine utili per la preparazione dell\'esame.', 
 25.00, 1, 2, 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e', 1, 1, 1),

-- Annuncio 2: Appunti Diritto Costituzionale
('Appunti Diritto Costituzionale', 
 'Appunti completi del corso 2024/2025, scritti a mano e molto dettagliati. Include tutte le lezioni, i riferimenti normativi e i casi studio discussi in aula.', 
 15.00, 2, 3, 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85', 2, 3, 2),

-- Annuncio 3: Fisica Generale 1 - Mazzoldi Nigro Voci
('Fisica Generale 1 - Mazzoldi Nigro Voci', 
 'Libro classico per il corso di Fisica 1. Qualche sottolineatura a matita ma in generale ottime condizioni. Copertina integra e pagine bianche.', 
 30.00, 1, 3, 'https://images.unsplash.com/photo-1532094349884-543bc11b234d', 3, 2, 1),

-- Annuncio 4: Appunti Programmazione Java (PDF)
('Appunti Programmazione Java', 
 'Appunti digitali in PDF dal corso completo, con esempi di codice ed esercizi risolti. Formato organizzato per capitoli, perfetto per lo studio autonomo.', 
 12.00, 3, 1, 'https://images.unsplash.com/photo-1516116216624-53e697fedbea', 5, 5, 4),

-- Annuncio 5: Chimica Organica - Bruice
('Chimica Organica - Bruice', 
 'Libro in editrice italiana, mai usato. Regalo perché ho cambiato corso di laurea. Ancora nella plastica originale, completamente nuovo.', 
 45.00, 1, 1, 'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69', 4, 4, 3),

-- Annuncio 6: Appunti Storia Contemporanea
('Appunti Storia Contemporanea', 
 'Appunti delle lezioni integrati con il libro. Riassunti completi per ogni capitolo, linee temporali e schemi concettuali per memorizzare gli eventi storici.', 
 10.00, 2, 3, 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173', 2, 6, 6),

-- Annuncio 7: Economia Aziendale - Airoldi Brunetti Coda
('Economia Aziendale - Airoldi Brunetti Coda', 
 'Libro ben tenuto con alcuni appunti a margine. Ottimo per preparare l\'esame, include esempi pratici e casi aziendali reali.', 
 28.00, 1, 3, 'https://images.unsplash.com/photo-1554224155-6726b3ff858f', 7, 7, 7),

-- Annuncio 8: Appunti Anatomia Umana
('Appunti Anatomia Umana', 
 'Appunti completi con illustrazioni e schemi anatomici. Perfetti per lo studio della materia, organizzati per sistemi (scheletrico, muscolare, nervoso).', 
 20.00, 2, 2, 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56', 8, 8, 8);