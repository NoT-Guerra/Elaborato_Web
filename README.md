# Elaborato_Web
I componenti del gruppo sono:
- Botteghi Matteo
- Cristian Qorri
- Nicholas Guerra 

Database {
Il database modella un marketplace universitario per la vendita di libri e appunti tra studenti, garantendo normalizzazione, integrità dei dati e supporto alla vendita multipla di contenuti digitali.
Vengono separati il concetto di oggetto e annuncio perchè un oggetto rappresenta il bene, mentre l’annuncio rappresenta l’atto di vendita.
Gli appunti digitali non hanno quantità limitata. Ogni vendita viene tracciata nella tabella vendita, senza eliminare l’annuncio o l’oggetto.
Il database è normalizzato in 3NF, garantisce integrità referenziale e riduce ridondanza.
}