﻿# CMS DIARI SCOLASTICI

## Documento di Progetto - Fase 1

**Versione:** 1.5
**Data:** 13 Febbraio 2026
**Cliente:** Gruppo Spaggiari Parma / La Fabbrica
**Stack Tecnologico:** Laravel 12 | Filament 5 | MySQL | Vite | Tailwind CSS

---

## 1. EXECUTIVE SUMMARY

Il progetto CMS Diarî è un'Applicazione gestionale che automatizza il processo di raccolta, gestione e produzione di diarî scolastici personalizzati. Attualmente, la raccolta dati avviene tramite e-mail e contatti diretti con le scuole, creando un carico operativo significativo per il team di redazione (2 persone di La Fabbrica). Il Gruppo Spaggiari fornisce questo software a La Fabbrica, per gestire le Campagne di produzione diarî che coinvolgono ogni anno migliaia di scuole. La Campagna è la gestione della produzione dei diarî per un certo anno scolastico, e si compone di Ordini di diarî da stampare raccolti dal Gestionale Ordini Eta Beta (ETB). La Scuola sceglie un Template, anch'esso acquistato su ETB, e lo applica al proprio Ordine di produzione diarî per l'anno scolastico della Campagna; poi, compila il Template in ogni sua parte. I dati del Template compilato vengono estratti in JSON/XML/CSV ed inviati ad un software esterno (InDesign) utilizzato da La Fabbrica per generare la Bozza, ovvero un file .pdf. La Bozza viene iterativamente corretta dalla Scuola (con annotazioni direttamente sul file .pdf) e rigenerata da La Fabbrica con la Versione incrementata fino ad ottenere una Bozza approvata dalla Scuola, da mandare in stampa.

**Obiettivo primario:** Centralizzare il flusso di lavoro in un sistema web unico, trasformando da processo cartaceo/e-mail a workflow digitale tracciabile e automatizzato.

**Benefici attesi:**

- Riduzione del 70% della comunicazione telefonica/e-mail
- Standardizzazione dei dati raccolti
- Tracciabilità completa del ciclo di vita dell'Ordine
- Raccolta dati strutturata e validata in tempo reale
- Workflow di correzione (ciclo correttivo) digitalizzato
- Export automatico per InDesign (XML/CSV)

---

## 2. STAKEHOLDER E RUOLI

| Ruolo                                      | Responsabilità                                             | Accesso Aree                                     |
|--------------------------------------------|------------------------------------------------------------|--------------------------------------------------|
| **Admin** (Utente interno, Spaggiari)      | Gestione Templates, Tipologie Pagine, Ordini, monitoraggio | Area Amministrativa Completa                     |
| **Redattore** (Utente interno, La Fabbrica)| Gestione Ordini, Correzioni, generazione Bozze             | Area Amministrativa Ordini, Correzioni           |
| **Grafico** (Utente interno, La Fabbrica)  | Visualizzazione Ordini, feedback qualità                   | Area Amministrativa (sola lettura), Correzioni   |
| **Referente** (Utente esterno, Scuola)     | Raccolta dati del Diario                                   | Area Utente/Redattore (raccolta dati)            |
| **Collaboratore** (Utente esterno, Scuola) | Collaborazione alla raccolta dati                          | Area Utente/Redattore (raccolta dati)            |

---

## 3. OBIETTIVI DEL PROGETTO

### Obiettivi Strategici

1. **Gestionale Condiviso**: Creare una piattaforma unica accessibile per i processi di redazione, grafica, produzione, logistica
2. **Standardizzazione Dati**: Raccolta strutturata con limiti volumetrici predefiniti (caratteri, righe tabelle, pagine)
3. **Riduzione Contatti Diretti**: Eliminare comunicazione parallela via e-mail/telefono
4. **Sincronizzazione Gestionale Ordini**: Integrare flusso con Gestionale Ordini principale
5. **Automazione Produzione**: Export dati in formati compatibili con InDesign (XML/CSV)

### Obiettivi Operativi (Fase 1)

1. Implementazione Area Amministrativa (Dashboard, Ordini, Utenti)
2. Sistema di Templates e Tipologie Pagine
3. Implementazione Area Utente (profilo Account)
4. Workflow Ordini ("Nuovo"-->"In Raccolta"-->"Bozza"-->"In Correzione"-->"Approvato"-->"In Produzione"-->"Completato")
5. Integrazione API con Gestionale Ordini

---

## 4. ANALISI DEI REQUISITI

### 4.1 AREA AMMINISTRATIVA

#### 4.1.1 Dashboard Riepilogativa

**Descrizione**: Visualizzazione centralizzata dello stato complessivo della Campagna.

**Componenti**:

- **Grafici Ordini**: Conteggio Ordini per Stato ("Nuovo", "In Raccolta", "Bozza", "In Correzione", "Approvato", "In Produzione", "Completato", "Annullato")
- **Filtro Temporale**: Ultimi 7 giorni, 30 giorni, mese corrente, custom
- **KPI Principali**:
  - Numero Ordini totali
  - Numero Scuole coinvolte
  - Numero Utenti attivi (interni + esterni)
  - Scadenze prossime (prossimi 7 giorni)
  - Tasso completamento raccolta dati (%)
  - Tasso approvazione Bozze (%)
- **Grafici Aggiuntivi**:
  - Distribuzione Ordini per Scuola (top 10)
  - Trend raccolta dati (curva temporale)
  - Alert Scadenze superate

**Autorizzazioni**: Admin, Redattore, Grafico (sola lettura per Grafico)

---

#### 4.1.2 Gestione Ordini

**Descrizione**: Visualizzazione, filtro e gestione completa della lista Ordini. L'acquisto di un Ordine su ETB è unito all'acquisto di un Template su ETB e contiene il suo Codice Articolo. L'acquisto di un Ordine di produzione Diarî genera la creazione di un Ordine nell'Applicazione e potenzialmente anche di un Template (modello) se è la prima volta che appare quel Codice Articolo. Quando l'Ordine viene avviato, il Sistema vi associa un clone del Template (istanza).

**Funzionalità**:

- **Lista Ordini Tabulare**:
  - Colonne: ID Ordine, Codice Articolo, Scuola, Data Creazione, Stato, Scadenza Raccolta, Scadenza Correzioni, Numero Invitati, % Compilazione, Data Ultimo Aggiornamento
  - Ordinamento: per Stato, data, Scuola, Scadenza

- **Filtri Avanzati**:
  - Per Stato Ordine ("Nuovo", "In Raccolta", "Bozza", "In Correzione", "Approvato", "In Produzione", "Completato", "Annullato")
  - Per Scuola
  - Per Data Creazione (periodo da inizio a fine)
  - Per % Compilazione (0-25%, 25-50%, 50-75%, 75-100%)
  - Per Scadenza raccolta dati (scaduto, in scadenza, ok)
  - Per Scadenza ciclo correttivo (scaduto, in scadenza, ok)
  - Per Numero Utenti con accesso all'Ordine (1-5, 5-10, 10+)
  - Export Ordini filtrati (CSV)

- **Azioni Bulk**:
  - Cambio Stato multiplo
  - Bulk Inviti Utenti
  - Bulk cambio Scadenze

- **Dettaglio Ordine** (click su riga):
  - Informazioni Ordine (ID, Articolo, Scuola, Template)
  - Stato attuale con timeline
  - Lista Utenti con accesso all'Ordine
  - % Compilazione raccolta dati
  - Date raccolta dati (inizio/fine)
  - Data Scadenza raccolta
  - Date ciclo correttivo dati (inizio/fine)
  - Data Scadenza ciclo correttivo
  - Azioni disponibili per Stato

**Database**:

```schema
campaigns
- id (PK)
- description (text)
- year (varchar)
- status enum("started", "completed", "planned", "deleted"),
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)

orders
- id (PK)
- external_id (varchar) -- id dell'Ordine in ETB
- campaign_id (FK)
- school_id (FK)
- template_id (FK)
- deadline_collection (datetime)
- deadline_annotation (datetime)
- status enum("new", "collection", "draft", "annotation", "approved", "production", "completed", "deleted"),
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)
```

---

#### 4.1.3 Invito Utenti Esterni

**Descrizione**: Gestione degli Inviti per gli Utenti.

**Workflow**:

1. **Invio Invito**:
   - Seleziona Ordine
   - Inserisci e-mail/s (singola, o multipla / da CSV se Invio Bulk)
   - Personalizza messaggio di Invito
   - Invia e-mail con link Invito

2. **Opzioni Accesso**:
   - **Accesso con Credenziali**: Email + Password
   - **Registrazione Esterna**: L'invitato si registra autonomamente (compila un form, scegliendo la Password) e riceve il link di accesso tokenizzato
   - **Accesso SSO** (fase futura): Accesso SSO con Credenziali Spaggiari, Classe Viva, Soluzioni, altri provider...

3. **Tracking Inviti**:
   - Status Invito / Utente ("Inviato", "Scaduto", "Registrato", "Attivo", "Annullato", "Bloccato")
   - Data invio
   - Data registrazione
   - Data ultimo accesso
   - Data blocco Account
   - Numero Inviti inviati

4. **Gestione Scadenze Inviti**:
   - Default: 30 giorni dalla creazione Ordine
   - Definibile: override Scadenze Inviti globali dal Pannello Impostazioni
   - Personalizzabile: override Scadenza Invito per un singolo Invito Utente durante il suo invio

**Database**:

```schema
school
- id (PK)
- description (text)
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)

users
- id (PK)
- school_id (FK)
- email (varchar) -- è anche lo username
- password (varchar)
- first_name (varchar)
- last_name (varchar)
- born_at (datetime)
- role enum("admin|admin", "internal|redattore", "internal|grafico", "external|referente", "external|collaboratore")
- company ENUM('Spaggiari','La Fabbrica','Scuola'),
- status (active, deleted, blocked)
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)

invitations
- id (PK)
- school_id (FK)           -- null se l'Invito è per un Ruolo non Esterno
- user_id (FK)             -- null se non ancora registrato
- email
- subject (text)
- message (text)
- token (unique)
- role enum("admin|admin", "internal|redattore", "internal|grafico", "external|referente", "external|collaboratore")
- status ("ready", "invited", "received", "expired", "registered", "active", "deleted")
- sent_at (datetime)       -- data invio Invito
- expires_at (datetime)    -- data scadenza Invito
- registered_at (datetime) -- data registrazione Utente, quando avverrà
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)
```

---

#### 4.1.4 Gestione Scadenze

**Descrizione**: Le Scadenze sono coppie di date inizio/fine: una sola Scadenza è attiva per volta. Una Scadenza si attiva/inizia quando la sua data di inizio è inferiore alla data attuale, e termina quando la sua data di fine è inferiore alla data attuale. Durante una Scadenza una o più attività devono essere completate dagli Utenti: non è possibile completare queste attività al di fuori del periodo previsto, ovvero al di fuori di una certa Scadenza. Il Sistema tiene traccia del completamento delle attività e gestisce graficamente un flag colorato sull'Ordine per mostrare agli Utenti lo stato della Scadenza attiva. Nel database le Scadenze di trovano all'interno della tabella Ordini. La correlazione tra le Scadenze e le attività da completare non è gestita a livello di database, ma solo di codice/grafica. Al momento gestiamo 2 Scadenze:

- Scadenza raccolta dati, in cui gli Utenti Esterni devono compilare al 100% il Template dell'Ordine
  - Attività che gli Utenti Esterni devono svolgere:
    - Decidere quali/quante Pagine istanziare di ciascuna Tipologia
    - Compilare ogni Pagina (il Sistema tiene traccia dei dati compilati nel campo struttura dell'istanza del Template)
- Scadenza del ciclo correttivo in cui gli Utenti Esterni possono indicare le Correzioni da fare alla Bozza corrente e gli Utenti Interni possono produrre nuove Versioni (file .pdf) della Bozza, fino all'Approvazione (flag su Bozza) di 1 Versione della Bozza
  - Attività che gli Utenti Esterni devono svolgere:
    - Visionare il file .pdf prodotto da La Fabbrica e potenzialmente approvarlo (mettere la Versione corrente della Bozza in Stato "Approvata")
    - Annotare Correzioni su di esso usando gli Strumenti nell'Editor PDF
    - Marcare la fine delle Correzioni premendo il bottone "Invia Correzioni" (il Sistema porrà la Versione corrente della Bozza in "Rifiutata", in quanto è necessario generarne un'altra)

**Nota bene**: le scadenze degli Inviti Utente non sono gestite in questo modo. Semplicemente, se l'Utente Esterno prova a registrarsi dopo la data di scadenza dell'Invito, non potrà farlo.

**Logica**:

- **Stato della Scadenza attiva**:
  - Flag di colore verde: non c'è una Scadenza attiva al momento (le date di inizio di tutte le Scadenze sono maggiori della data attuale)
  - Flag di colore blu: tutte le attività previste per la Scadenza attiva sono state completate
  - Flag di colore giallo: c'è una Scadenza attiva, ma non tutte le attività previste sono state completate
  - Flag di colore arancione: c'è una Scadenza attiva, non tutte le attività previste sono state completate, mancano 7 giorni o meno alla data di fine della Scadenza
  - Flag di colore rosso: non c'è una Scadenza attiva, ma non tutte le attività previste per la Scadenza precedente sono state completate. Attenzione: si valuta solo la Scadenza precedente più prossima alla data attuale, non quelle antecedenti ad essa.

- **Date delle Scadenze**:
  - Data inizio Scadenza raccolta dati = data creazione dell'Ordine.
  - Data fine Scadenza raccolta dati = data inizio Scadenza raccolta dati + 30 giorni
  - Data inizio Scadenza ciclo correttivo = data fine Scadenza raccolta dati
  - Data fine Scadenza ciclo correttivo = data inizio Scadenza ciclo correttivo + 30 giorni
  - Tutte le date delle Scadenze sono personalizzabili globalmente nel Pannello Impostazioni dagli Utenti Admin

- **Personalizzazione per Ordine**:
  - Solo per Utenti Interni/Admin: override Scadenza raccolta dati per singolo Ordine/Scuola nella scheda Dettaglio dell'Ordine
  - Solo per Utenti Interni/Admin: override Scadenza ciclo correttivo per singolo Ordine/Scuola nella scheda Dettaglio dell'Ordine

- **Notifiche Automatiche**:
  - Mostra countdown visuale nei Dettagli dell'Ordine
  - Alert via email quando fine Scadenza entro 7 giorni
  - Alert via email quando Scadenza superata, con rapporto esito (attività completate o meno)

---

#### 4.1.5 Raccolta Dati e Generazione Bozza

**Descrizione**: Processo di raccolta ed estrazione dati dal Template per poi generare la Bozza v1.0 (il file .pdf) dopo il termine della Scadenza raccolta dati.

**Workflow**:

1. **Avvio Ordine**: Il Sistema avvia la prima Scadenza, mettendo l'Ordine dallo stato "Nuovo" a "In Raccolta".
2. **Istanza Template**: Il Sistema clona il Template (modello) assegnando il clone all'Ordine. Il clone è il Template (istanza) che gli Utenti Esterni devono compilare per questo Ordine/Scuola/Campagna.
3. **Compilazione Pagine**: L'Utente Esterno seleziona dal Template (modello) le Pagine da personalizzare (potenzialmente non tutte, potenzialmente alcune più volte), e le compila nel proprio Template (istanza). Il Sistema altera sia la Struttura che il contenuto del Template (istanza) in base a ciò che l'Utente ha selezionato nel Template (modello) e a ciò che l'Utente ha inserito nei vari form delle varie Pagine.
4. **Passaggio Scadenza**: Data attuale ha superato Scadenza raccolta dati di 3 giorni
5. **Attivazione**: Utente Redattore/Admin clicca "Estrai dati" e sceglie il formato
6. **Logica**:
   - Sistema raccoglie dati dal Template (istanza), li converte nel formato richiesto, e compila la colonna corrispondente se formato != JSON
   - Sistema genera file di testo con dati in formato JSON, XML o CSV e lo fa scaricare all'Utente Redattore
   - Utente Redattore utilizza i dati estratti in InDesign per generare la Bozza V1.0 in .pdf e la carica nell'Applicazione
   - Sistema salva il file in una cartella dedicata all'Ordine/Scuola e crea nuova riga per la Bozza in `drafts` in Stato Vuota (empty)
   - Sistema assegna file .pdf e numero di Versione 1.0 alla riga corrispondente in `drafts`
   - Sistema cambia Stato Ordine in "Bozza"

**Database**:

```schema
drafts
- id (PK)
- order_id (FK)
- version (int)
- file_path (URL)
- data_json (json)
- data_xml (text)
- data_csv (text)
- status enum("empty", "collecting", "collected", "published", "annotating", "approved", "rejected")
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)
```

---

#### 4.1.6 Pubblicazione Bozze e Invio Correzione

**Descrizione**: Condivisione Bozza PDF con Utenti Esterni per Correzioni.

**Workflow**:

1. **Pubblicazione Bozza**:
   - Redattore/Admin genera file .pdf per la Bozza e lo carica nell'Applicazione
   - Redattore/Admin imposta la Bozza in Stato "Pubblicata" (published) per rendere il file .pdf visibile agli Utenti Esterni
   - Sistema invia notifica e-mail agli Utenti Esterni: "Bozza pronta per revisione"

2. **Visualizzazione Bozza da Utente Esterno**:
   - Utente vede area "Bozze" nella sua Dashboard, con tutte le Bozze di tutti gli Ordini della sua Scuola
   - Visualizzatore PDF in sola lettura per ciascuna Bozza, oppure interattivo solo se:
       - la Bozza è in stato "Pubblicata" ed è l'ultima Versione delle Bozze del proprio Ordine
       - l'Ordine non è in Stato "Completato" o "Annullato".
   - Strumenti di correzione interattiva:
     - **Sticky Note**: Aggiungi note e commenti per pagina/elemento
     - **Evidenziazione**: Seleziona aree/testo
     - **Disegno**: Disegna cerchi e frecce per indicare Correzioni spiegate nelle Sticky Notes
   - Limiti: Max 20 megabyte per file, zoom fino a 200%

3. **Invio Correzioni**:
   - Utente Esterno valuta se la Versione attuale (ultima in "Pubblicata") della Bozza ha bisogno di Correzioni: se non ne ha, la pone in Stato "Approvata"
   - Se ne ha bisogno, Utente Esterno applica le Correzioni attraverso l'Editor PDF specificando la priorità di ciascuna
   - Sistema registra le Correzioni in `annotations` ponendo la Bozza in Stato "In Correzione" (annotating)
   - Utente Esterno finisce di correggere e Clicca "Invia Correzioni"; se vuole, aggiunge messaggio testuale
   - Sistema registra timestamp e pone la Bozza in Rifiutata (rejected) in quanto si necessita della generazione di una nuova Versione della Bozza da parte di La Fabbrica
   - Sistema crea nuova riga in `drafts` per la nuova Versione della Bozza in Stato "Vuota" (empty) ed il ciclo riparte dal punto 1 con una nuova Versione

**Database**:

```schema
annotations
- id (PK)
- draft_id (FK)
- user_id (FK)
- page_number
- type (enum: "text", "highlight", "drawing")
- content (JSON con coordinate/testo)
- priority (low, medium, high)
- status (pending, fixed, deleted)
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)
```

---

#### 4.1.7 Gestione Cicli Correzione

**Descrizione**: Tracciamento e limitazione dei cicli di correzione.

**Configurazione**:

- **Limite Correzioni**:
  - Default: ottenere l'Apprivazione entri 3 cicli correttivi, quindi entro la Bozza v4 (configurabile globalmente in Pannello Impostazioni)
  - Override: per singolo Ordine/Scuola, in Dettaglio Ordine

- **Esempio di Workflow**:
  1. Bozza v1 pubblicata --> Utente Esterno effettua Correzioni --> Utente Esterno segnala termine Correzioni (ciclo 1)
  2. Utente Interno implementa Correzioni in InDesign --> Genera Bozza v2 --> carica e poi pubblica Bozza v2
  3. Bozza v2 pubblicata --> Utente Esterno effettua Correzioni --> Utente Esterno segnala termine Correzioni (ciclo 2)
  4. Utente Interno implementa Correzioni in InDesign --> Genera Bozza v3 --> carica e poi pubblica Bozza v3
  5. Bozza v3 pubblicata --> Utente Esterno effettua Correzioni --> Utente Esterno segnala termine Correzioni (ciclo 3)
  6. Utente Interno implementa Correzioni in InDesign --> Genera Bozza v4 --> carica e poi pubblica Bozza v4
  7. Bozza v4 pubblicata ---> Utente Esterno Approva Bozza v4
  8. Sistema blocca ulteriori Correzioni e sposta Ordine in "Approvato"
  9. Quando le macchine per la stampa sono pronte e configurate, Utente Interno sposta l'Ordine in Stato "In Produzione"

- **Visualizzazione**:
  - Counter: "Ciclo correzione #/n"
  - Barra progressione cicli
  - Data Scadenza fase di Correzione

**Notifiche**:

- Email Utenti Interni quando Utente Esterno segnala termine Correzioni, es: "Nuove correzioni per Ordine #XYZ (ciclo 2/3)"
- Email Utenti Interni quando Utente Esterno approva Versione attuale Bozza
- Email Utenti Esterni dopo pubblicazione nuova Versione Bozza
- Email Utenti Esterni quando Ordine passa a "In Produzione"

---

#### 4.1.8 Approvazione e Invio Produzione

**Descrizione**: Workflow finale di approvazione e passaggio a produzione.

**Workflow**:

1. **Bozza Approvata**:
   - Utente Esterno approva Bozza finale
   - Clicca "Conferma e Approva"
   - Cambio Stato Ordine in "Approvato"

2. **Passaggio In Produzione**:
   - Utente Admin/Redattore seleziona un Ordine in "Approvato" e clicca su "Invia a Produzione"
   - Sistema:
     - Genera export finale in tutti i formati disponibili (JSON/XML/CSV) per InDesign
     - Sincronizza con ETB via API
     - Cambio Stato Ordine a "In Produzione"

3. **Tracking Produzione**:
   - Dashboard mostra stato Produzione e spedizione
   - Aggiornamenti automatici da ETB su stato Produzione e spedizione
   - Notifiche milestone via e-mail: Produzione avviata, Stampa completata, Diarî spediti

---

#### 4.1.9 Pannello Impostazioni

**Descrizione**: Area riservata per configurare parametri globali dell’Applicazione (come Scadenze, limiti, job di sincronizzazione ETB, autosalvataggio, limiti file).
Tutte le Impostazioni hanno un valore di default; la modifica è tracciata.

**Autorizzazioni**:

- Admin / Redattore: lettura/scrittura
- Grafico: sola lettura

**Funzionalità**:

- Lista Impostazioni: Nome, Descrizione, Valore, Tipo, Ultimo Aggiornamento, Aggiornato da
- Modifica singola Impostazione
- Ripristino valori default (per singola Impostazione o tutte)
- Ricerca per nome/descrizione
- Logging/Audit: ogni modifica crea una riga nello storico (log) con source=manual e note “Setting changed: nomeSetting”

**Impostazioni previste (estendibili)**:

- ETB_SYNC_INTERVAL_MINUTES (int, default 60): ogni quanti minuti eseguire la sincronizzazione ETB
- INVITATION_EXPIRY_DAYS (int, default 30): Scadenza Inviti Collaboratori
- COLLECTION_PERIOD_DAYS (int, default 30): durata fase “In Raccolta”
- ANNOTATION_PERIOD_DAYS (int, default 30): durata fase “In Correzione”
- COLLECTION_GRACE_DAYS (int, default 3): giorni di tolleranza dopo la Scadenza raccolta prima di abilitare “Estrai dati”
- MAX_CORRECTION_CYCLES (int, default 3): numero massimo di cicli correttivi
- AUTOSAVE_SECONDS (int, default 15): intervallo autosalvataggio nei form raccolta dati
- MAX_UPLOAD_MB (int, default 20): limite upload (pdf, immagini, loghi)
- IMAGE_MIN_DPI (int, default 300): dimensioni/qualità immagini
- IMAGE_ALLOWED_FORMATS (json, default ["jpg","jpeg","png","tif","tiff"]): formati consentiti

---

#### 4.1.10 Logging

**Descrizione**: Il Sistema registra tutti i cambi di Stato e gli eventi significativi in una tabella unica di eventi, interrogabile per costruire la timeline dell’Ordine e la timeline della singola Bozza (Versione).
Gli eventi possono essere generati da azione manuale dell'Utente o dal Sistema.

**Eventi tracciati**:

- Cambio stato Ordine (orders.status)
- Cambio stato Bozza (drafts.status) per una specifica versione
- Evento “scadenza modificata”, “invito inviato”, “invito scaduto”, “correzioni inviate” e simili.
- Creazione ed ultima modifica di un'Entità

**Visualizzazione**:

- Nel dettaglio Ordine: timeline unica che mostra sia eventi Ordine sia eventi Bozze dalla creazione all'ultima modifica (con indicazione “Bozza vN” quando draft_id è valorizzato) con indicazione dei passaggi di Stato
- Nel dettaglio Bozza: timeline filtrata per draft_id, dalla creazione all'ultima modifica, con indicazione passaggi di Stato
- Nel dettaglio di tutte le altre Entità: timeline dalla creazione all'ultima modifica, con indicazione passaggi di Stato

---

### 4.2 TIPOLOGIE PAGINE E TEMPLATE

#### 4.2.1 Gestione Tipologie di Pagina

**Descrizione**: Definizione dei moduli/blocchi dati raccoglibili all'interno dei Templates.

- **Identità Scuola**
Descrizione: Nome, Ordine/Grado, Sedi, Contatti/Indirizzi Sedi, Logo
Dati: Form con campi di input, numero sedi dinamico, caricamento Logo in PNG/SVG

- **Anagrafica Studente**
Descrizione: dati anagrafici studente, area per firme dei genitori
Dati: Form con campi di input, caricamento immagine da usare come area firme genitori
Note: le firme saranno eseguite a mano dai genitori sul Diario stampato

- **Calendario Scolastico**
Descrizione: date di inizio e fine scuola, festività e vacanze
Dati: tabella modificabile con righe predefinite (tipologia e descrizione fissa) da compilare e possibilità di aggiungere righe; ogni riga ha un periodo di date/ore, una tipologia ed una descrizione
Note: in futuro questi periodi andranno indicati sulle pagine corrispondenti ai giorni interessati

- **Orari Uffici/Presidenza**
Descrizione: date e orari per i ricevimenti di studenti, genitori, DSGA
Dati: tabella modificabile le cui righe possono avere un giorno della settimana oppure una data specifica, un orario dal/al, un campo Note
Note: se è indicato un giorno della settimana, la data si intende come ripetibile (es: ogni martedì, dalle ore alle ore)
Rappresentazione grafica aggiuntiva: sotto l'area di inserimento dati, mostrare le date ripetibili sotto forma di tabella settimanale

- **Orario Lezioni**
Descrizione: orario delle lezioni dal lunedì al sabato
Dati: checklist fissa i cui item sono i giorni della settimana e contengono una sotto-lista con 9 "ore" numerate ordinalmente, da riempire con orario di inizio, orario di fine, materia e docente
Note: tutti gli orari indicati sono da considerarsi come ripetuti ogni settimana; 2 pagine previste, 1 per l'orario provvisorio ed 1 per quello definitivo
Rappresentazione grafica aggiuntiva: sotto la checklist, mostrare gli item sotto forma di tabella settimanale

- **Colloqui Docenti**
Descrizione: date e orari per i ricevimenti di studenti e genitori
Dati: tabella modificabile le cui righe hanno un docente, una materia, data, orario da/a, modalità prenotazione

- **Libri di Testo**
Descrizione: libri che gli studenti dovrebbero procurarsi per le lezioni
Dati: tabella modificabile le cui righe hanno titolo, autore, editore, prezzo, ISBN

- **Materiale Occorrente**
Descrizione: tutto ciò che lo studente deve portare in classe, in base alle lezioni del giorno
Dati: tabella modificabile le cui righe hanno oggetto, descrizione, quantità, materie

- **Compagni di Classe**
Descrizione: elenco dei compagni di classe dello studente
Dati: tabella modificabile le cui righe hanno nome, cognome, indirizzo, email, telefono

- **PTOF / Presentazione Scuola**
Descrizione: prefazione al Diario, in cui la Scuola si presenta
Dati: grande textarea con TinyMCE o libreria equivalente

- **Regolamento d'Istituto**
Descrizione: tutto il regolamento d'istituto, diviso in articoli
Dati: tabella modificabile i cui campi sono il titolo ed il corpo di ciascun articolo, con textarea TinyMCE per il corpo + proprietà distinte per l'impaginazione degli Articoli
Note: dev'essere possibile disporre 1-10 articoli per pagina del Diario, ma questo limite verrà ignorato se quel numero di articoli non ci sta in una singola pagina, andnado a disporli su più pagine
Rappresentazione grafica aggiuntiva: sotto la tabella mostrare le pagine stilizzate che verranno generate con questa tabella in modo che sia chiara l'effettiva disposizione degli articoli nelle pagine

- **Patto Educativo Di Corresponsabilità**
Descrizione: i tre impegni, da parte della Scuola, della famiglia e dello studente, con area firme sottostante
Dati: 3 textarea con TinyMCE, precedute dai titoli predefiniti "La Scuola", "La Famiglia", "Lo Studente" + 1 caricamento immagine per l'area firme
Note: le firme saranno eseguite a mano sul Diario stampato

- **Valutazioni & Interrogazioni**
Descrizione: tabelle da compilare con date ed esito delle valutazioni
Dati: form con campi per specificare il numero di tabelle da stampare e per ciascuna il numero di pagine che deve coprire, il titolo e le intestazioni delle colonne

- **Pagine Generiche**
Descrizione: titolo in grande, testo libero, gallery immagini... design libero a cura dell'Utente
Dati: tabella dove ogni riga ha una textarea con TinyMCE, una select con delle posizioni predefinite ed un campo data
Note: il campo data serve ad indicare la posizione della pagina all'interno del diario quando nella select è indicato "Prima del giorno:"

- **Pagine Statiche**
Descrizione: contenuto statico curato dagli Utenti Interni
Dati: tabella dove ogni riga ha una textarea con TinyMCE, una select con delle posizioni predefinite ed un campo data
Note: come la Pagine Generiche, ma gli Utenti Esterni vedono il contenuto della textarea come se fosse un'immagine Statica

**Gestione Tipologie**:

- Tabella tipologie con: Nome, Descrizione, Spazio Previsto, Struttura, Icona, Stato, Data Creazione, Data Ultima Modifica
- Azioni: Crea Nuova, Modifica, Duplica, Disattiva, Anteprima
- Contatori per gli Utenti nell'interfaccia grafica: mostrano il numero di righe e caratteri rimanenti quando l'Utente digita in un input di testo
- Form Tipologia Pagina:
  - Nome
  - Descrizione
  - Spazio previsto (0.25, 0.5, 0.75, 1, 2+ pagine)
  - Struttura input con regole validazione (min/max caratteri, righe, file size... per ogni campo della struttura di input)
  - Icona/immagine tipologia
  - Stato (attiva/inattiva)

**Database**:

```schema
pageTypes
- id (PK)
- description (text)
- space (decimal) -- es. 0.5, 1, 1.5, 2
- structure (JSON) -- struttura complessa, da compilare, di una singola Pagina di questa Tipologia
- icon (varchar, URL)
- status (enum: "active", "deleted")
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)
```

---

#### 4.2.2 Gestione Template

**Descrizione**: creazione e gestione dei Templates, associati a codici Articoli aziendali e Ordini effettuati su ETB.

**Logica Template**:

- Ogni **Template** = combinazione di tipologie pagine selezionabili dall'Utente Esterno
- Ogni Template è legato a 1 codice Articolo (via codiceEsterno, es: DIARIO-2026-ELEM-A5) e 1 Ordine (via idEsterno)
- Template definisce:
  - Numero e tipologia di Pagine personalizzabili e non
  - Ordinamento proposto Tipologie Pagine
  - Tipologie obbligatorie vs facoltative
  - Per ogni campo, numero massimo di righe e di caratteri inseribili
  - Layout/tema grafico in InDesign, ma non nella nostra Applicazione

**Interfaccia Gestione Template**:

1. **Tabella Template**:
   - Colonne: ID, ID Ordine di acquisto, ID Ordine Campagna, Codice Articolo, Nome, Numero Pagine Max, Stato, Data Creazione, Data Ultima Modifica
   - Azioni: Visualizza, Modifica, Clona, Anteprima, Disattiva

2. **Form Creazione/Modifica Template**:
   - **Sezione 1 - Informazioni Base**:
     - Codice Articolo ETB (associazione vincolo UNICO)
     - Nome Template
     - Descrizione

   - **Sezione 2 - Tipologie Pagine**:
     - Tabella Pagine personalizzabili: ogni riga corrisponde ad una Tipologia di Pagina personalizzabile, con numero massimo occorrenze
     - Tabella Pagine statiche: ogni riga corrisponde ad una Pagina statica curata dagli Utenti Interni, con numero occorrenze
     - Per ogni Tipologia:
       - Flag: Obbligatoria / Facoltativa
       - Se ripetibile: Max istanze (es. "Max 2 istanze di pagina nomeTipologia")
       - Preview spazio occupato in tempo reale

   - **Sezione 3 - Qualità Immagini**:
     - Min risoluzione: Default 300 DPI per stampa
     - Max file size: Default 20 MB, compressione altrimenti
     - Formati supportati: JPG, PNG, TIFF
     - Tooltip: "Alert se immagini compresse/bassa qualità"

3. **Anteprima Template**:
   - Visualizzazione mockup Diario (in futuro, non sviluppare ora)
   - Numero Pagine, Tipologie di ciascuna, caratteri/righe utilizzati...

**Database**:

```schema
templates
- id (PK)
- external_id (varchar) -- l'id del Template in ETB
- order_id (FK) -- se null, il Template è un modello, altrimenti è l'istanza di quel modello presso un Ordine
- campaign_id (FK, nullable) -- se null, il Template è un modello utilizzabile in tutte le Campagne, altrimenti avrà lo stesso campaign_id dell'Ordine a cui è associato
- code (varchar, UNIQUE) -- es. "DIARIO-2026-ELEM-A5", da ETB
- description (text)
- max_pages (int)
- structure (json) -- le Strutture concatenate di tutte le Tipologie Pagina che questo Template include: questo definisce la Struttura del Template. E' sempre spoglia di dati.
- status (enum: "active", "deleted")
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)

templates_pageTypes -- mette in relazione i Templates con le pageTypes
- template_id (FK)
- pageTypeId (FK)
- isMandatory (bool)
- maxOccurrences (int, null)
- created_at (datetime)
- updated_at (datetime)
- created_by (user)
- updated_by (user)
```

---

### 4.3 AREA RACCOLTA DATI

#### 4.3.1 Dashboard Utente

**Descrizione**: Panoramica Ordini e stato raccolta dati per Utenti Esterni.

**Componenti**:

- **Riepilogo Rapido**:
  - Numero Ordini acquistati
  - Numero Ordini in raccolta dati
  - Numero Ordini in correzione
  - Scadenze prossime (conteggio giorni)

- **Elenco Ordini**:
  - Colonne: Codice Template, Scuola, % Compilazione, Stato attuale
  - Filtri: Per Stato, per Scuola, per Scadenza
  - Ordinamento: Per Scadenza, per % completamento, per ciclo correttivo

- **Stato Ordine Visivo - Timeline**:
  - Timeline: storico degli Stati dell'Ordine con date per passaggi di stato
  - Evidenzia fase attuale

- **Alert**:
  - Scadenze imminenti (giallo)
  - Scadenze scadute (rosso)
  - Scadenze completate con successo (blu)
  - Nessuna Scadenza in attesa (verde)

---

#### 4.3.2 Processo Raccolta Dati - Interfaccia Principale

**Descrizione**: Form modulare per compilazione dati Pagine con selezione ed ordinamento pagine.

**Layout PowerPoint-style (dati di esempio)**:

```esempio
____________________________________________________________________
| DIARIO SCOLASTICO - Scuola Primaria "XYZ"                        |
| Scadenza raccolta dati: 15 Gennaio 2026 (15 giorni rimasti)      |
|__________________________________________________________________|
| SELEZIONE PAGINE       | PAGINE SELEZIONATE                      |
| (Tipo + Descrizione)   | (Con stato compilazione)                |
|________________________|_________________________________________|
|  Identità Scuola       |  1. Identità Scuola                     |
|  (0.5 pagine)          |     [Preview thumbnail]                 |
|                        |                                         |
|  Anagrafica Studente   |  2. Anagrafica Studente                 |
|  (0.5 pagine)          |     [Preview thumbnail]                 |
|                        |                                         |
|  Calendario Scolastico |  3. Calendario Scolastico               |
|  (1 pagina)            |     [Preview thumbnail] (50% compilato) |
|                        |                                         |
|  Orari Uffici          |  4. Orari Uffici (Vuoto)                |
|  (1 pagina)            |     [Preview thumbnail]                 |
|                        |                                         |
|  Colloqui Docenti      |  5. Colloqui Docenti (Vuoto)            |
|  (1 pagina)            |     [Preview thumbnail]                 |
|                        |                                         |
|  Compagni di Classe    |  6. Compagni di Classe (Vuoto)          |
|  (2 pagine)            |     [Preview thumbnail]                 |
|                                                                  |
| ... (scroll)                                                     |
|__________________________________________________________________|
| Pagine Utilizzate: 3.5 / 20 pagine (17%)                         |
| [Progress bar]                                                   |
|__________________________________________________________________|

[Drag su un oggetto per riordinarlo + bottone per eliminare + bottone per vedere il Dettaglio e modificare l'oggetto]


FORM COMPILAZIONE PAGINA SELEZIONATA
_____________________________________________________
| [Input fields dipendenti dalla Tipologia Pagina]  |
| Es. con "Identità Scuola":                       |
|   - Nome Scuola                                   |
|   - Grado/Ordine Scuola (select)                  |
|   - Sedi/Succursali (ripetibile)                  |
|      - Contatti (e-mail, tel, sito)               |
|   - Upload Logo (PNG/SVG)                         |
|___________________________________________________|
|                                                   |
| [Barre progresso compilazione]                    |
| Immagini: OK                                      |
| Caratteri rimanenti: 250 / 500                    |
|___________________________________________________|

```

**Funzionalità  Colonna Sinistra (Selezione Pagine)**:

1. **Elenco Tipologie Pagina**:
   - Mostra le Tipologie disponibili per il Template
   - Sotto: indicatore spazio previsto
   - A sinistra: checkbox seleziona/deseleziona
   - A destra: bottone "Aggiungi": aggiunge una Pagina di questa Tipologia alle Pagine da compilare
   - Drag per ordinare le Tipologie

2. **Selezione Multipla**:
   - Tipologie ripetibili: Utente può aggiungere più volte. Al raggiungimento numero massimo, bottone "Aggiungi" disabilitato.
   - Sistema mostra (esempio): "Hai aggiunto 2/3 istanze consentite"

3. **Indicatore Spazio**:
   - Live update: "Pagine Utilizzate: X / Y pagine"
   - Progress bar visuale

**Funzionalità Colonna Destra (Tipologia Pagina Selezionata)**:

1. **Lista Pagine Ordinate**:
   - In alto: icona Tipologia e numerazione 1, 2, 3, ... per selezione Pagina
   - Nome Pagina e Posizione
   - Corpo della Pagina modificabile
   - In basso: stato compilazione in % e timestamp ultimo salvataggio

2. **Interazioni Pagina**:
   - **Modifica campi"**: possibilità di modificare i campi della Pagina secondo la Struttura della sua Tipologia.
   - **Bottoni a freccia**: Riordinamento tra Pagine della stessa Tipologia
   - **Posizione**: indicazione di dove si vuole posizionare la Pagina all'interno del Diario, se disponibile. (0 --> prima del 01/01, 1 --> prima del 01/01, -5 --> 5 pagine prima del 01/01, et cetera)
   - **Tasto "Salva"**: Salva contenuto attuale Pagina nel DB
   - **Tasto "Rimuovi"**: Elimina Pagina, aggiorna conteggi

---

#### 4.3.3 Form Compilazione Dinamico

**Descrizione**: Form generato dinamicamente in base alla tipologia pagina selezionata.

**Logica**:

1. Utente clicca su numerazione nella colonna destra
2. Sistema carica form per quella Tipologia
3. Form mostra:
   - Input fields specifici per la Tipologia
   - Validazione in tempo reale
   - Contatori caratteri/righe
   - Upload file con validazione
   - et cetera, in base alla Struttura della Tipologia a cui la Pagina appartiene

**Esempi Form per Tipologia**:

**A. Identità Scuola**:

```esempio
Nome Scuola: [text input, max 100 caratteri]
Ordine di Scuola: [select: Infanzia / Primaria / Secondaria I° / Secondaria II°]
Sedi/Succursali:
  [+ Aggiungi sede]
  Sede 1: Via XX, Cap, Città
    - Contatti Sede 1:
      - Email: [e-mail]
      - Telefono: [tel]
      - Sito web: [URL]
  Sede 2: Via YY, Cap, Città
    - Contatti Sede 1:
      - Email: [e-mail]
      - Telefono: [tel]
      - Sito web: [URL]

Logo Scolastico:
  [Drop area per PNG/SVG - max 20MB]
```

**B. Calendario Scolastico**:

```esempio
Inizio e fine lezioni:
- Data inizio lezioni: 10/09
- Data fine lezioni: 10/06

Festività:
- Festività: 01/11 (Ognissanti)
- Festività: 08/12 (Immacolata Concezione)
  ...altre Festività...
  [+ Aggiungi Festività]

Sospensioni/Vacanze:
- Vacanze di Natale: dal 20/12 al 07/01
- Vacanze estive: dal 11/06 al 09/09
   ... altre Vacanze...
  [+ Aggiungi Vacanza o Sospensione lezioni]

Altro: [date picker] Descrizione: [text]
```

---

#### 4.3.4 Salvataggio dati Pagina

**Descrizione**: Logica di salvataggio automatico e manuale. Ogni Correzione viene memorizzata separatamente in `annotations` in modo da essere tracciata e modificabile.

**Salvataggio Automatico**:

- Intervallo: Ogni X secondi (contatore impostabile da Pannello Impostazioni)
- Trigger: Qualsiasi cambio nei form fa partire il contatore; quando il contatore termina, il Sistema salva e lo fa ripartire se ci sono stati altri cambiamenti nel frattempo
- Comportamento: Background, senza bloccare interfaccia
- Feedback: Timestamp "Ultimo salvataggio: HH:MM" sotto alla Pagina
- Esito: Mostra toast con esito

**Salvataggio Manuale**:

- Pulsante "Salva" in basso pagina (sempre visibile)
- Comportamento: Background, senza bloccare interfaccia
- Feedback: Timestamp "Ultimo salvataggio: HH:MM" sotto alla Pagina
- Esito: Mostra toast con esito

**Meccanismo**:

- Nel campo dataJson della Versione corrente della Bozza viene mantenuta la struttura ed il contenuto attuali del Template compilato. Nota bene che dopo l'estrazione dei dati e la generazione della Bozza v1.0 nè la struttura nè il contenuto nè il campo dataJson sono in alcun modo modificabili
- Nella tabella `annotations` vengono salvate e mantenute le Correzioni. E' possibile modificare/correggere una Correzione già presente, senza crearne per forza una nuova.
- Quando la Versione corrente della Bozza è pronta per essere rigenerata (fine delle Correzioni), l'Utente preme "Invia Correzioni", il Sistema pone la Bozza in "Rifiutata" (rejected)
- Subito dopo il Sistema crea una nuova riga in `drafts` per la nuova Versione in Stato Vuota (empty) ed avvisa gli Utenti Interni perchè generino una nuova Versione della Bozza.

---

### 4.4 INTEGRAZIONE GESTIONALE ORDINI

#### 4.4.1 Sincronizzazione Ordini

**Descrizione**: Importazione Ordini dal gestionale aziendale ETB e sincronizzazione continua.

**Logica**:

1. **Import Iniziale**:
   - API ETB: GET /schools
   - Creazione Scuole in Applicazione in stato "Attiva": tabella `schools`
   - Creazione di un Utente Esterno con Ruolo Referente per ciascuna Scuola: tabella `users`
   - API ETB: GET /orders (filtro: data >= oggi - 30 giorni, Stato "Attivo")
   - Creazione Ordini in Applicazione in stato "Nuovo": tabella `orders`
   - API ETB: GET /templatesDiari
   - Creazione Templates in Applicazione: tabella `templates`
   - Se un Ordine comprende un Template (se esiste codiceArticolo in Ordine ETB), assegnazione Template all'Ordine/Campagna.

2. **Sincronizzazione Continua**:
   - Cron job: Ogni ora (impostabile in Pannello Impostazioni)
   - Controlla Entità nuove, modificate e cancellate da ETB
   - Se un'Entità è cancellata (deleted), aggiorna Stato e Data Ultima Modifica, poi notifica Utenti interessati

**Endpoint API ETB da Implementare**:

```json
GET /api/orders?status=active&date_from=YYYY-MM-DD
Response: {
  "success": true,
  "data": [
    {
      "order_id": "ETB-2025-001234",
      "article_code": "DIARIO-2026-ELEM-A5",
      "customer_name": "Scuola Primaria XYZ",
      "customer_email": "info@scuolaxyz.it",
      "order_date": "2025-12-20",
      "quantity": 500,
      "status": "confirmed"
    },
    ...
  ]
}
```

---

#### 4.4.2 Export Dati per InDesign (implementazione futura)

**Descrizione**: Generazione file XML/CSV compatibili con EasyCatalog e plug-in InDesign.

**Formati Export**:

**A. XML per EasyCatalog**:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Orders>
  <Order>
    <OrderID>ETB-2025-001234</OrderID>
    <SchoolName>Scuola Primaria XYZ</SchoolName>
    <SchoolType>primaria</SchoolType>
    <Pages>
      <Page id="1">
        <PageType>Identità Scuola</PageType>
        <Fields>
          <SchoolName>Scuola Primaria XYZ</SchoolName>
          <SchoolOrder>primaria</SchoolOrder>
          <Seats>
            <Seat>Via XX, 123 - Città</Seat>
          </Seats>
          <Email>info@scuolaxyz.it</Email>
          <Phone>+39 055 123456</Phone>
          <Website>www.scuolaxyz.it</Website>
          <Logo>/storage/logos/scuolaxyz.png</Logo>
        </Fields>
      </Page>
      <Page id="2">
        ...
      </Page>
    </Pages>
  </Order>
</Orders>
```

**B. CSV per Data Merge (formato tabulare)**:

```CSV
OrderID | SchoolName | SchoolType | Page1_Type | Page1_SchoolName | ... | Page1_Logo
ETB-2025-001234 | Scuola Primaria XYZ | primaria | Identità Scuola | Scuola Primaria XYZ | ... | /logos/scuolaxyz.png
```

**Plug-in InDesign Consigliati**:

- **EasyCatalog**: Lettura XML, aggiornamento live layout
- **Em Software DocsFlow**: Se export DOCX (per testi lunghi)
- **InData**: Script per impaginazione condizionale

---

## 5. ARCHITETTURA TECNICA

### 5.1 Stack Tecnologico

| Layer               | Tecnologia                   | Versione  | Note                                               |
|---------------------|------------------------------|-----------|----------------------------------------------------|
| **Backend**         | Laravel                      | 12.x      | Framework web MVC                                  |
| **Admin Panel**     | Filament                     | 5.1+      | Area amministrativa (panel)                        |
| **Frontend**        | Blade + Filament             | Nativa    | Rendering server-side; UI del panel                |
| **Asset Bundler**   | Vite                         | 7.x       | Build asset (JS/CSS)                               |
| **Styling**         | Tailwind CSS                 | 4.x       | CSS utility framework                              |
| **Database**        | MySQL                        | 8.0+      | Storage dati relazionali                           |
| **Storage**         | NFS                          | -         | PDF, immagini, loghi                               |
| **Email Service**   | Laravel Mail (SMTP)          | Nativa    | Notifiche, inviti                                  |
| **API REST**        | Laravel Routing              | Nativa    | RESTful API endpoints                              |
| **HTTP Client**     | Laravel HTTP Client          | Nativa    | Integrazione API ETB                               |
| **Deploy**          | Rocky Linux                  | -         | WS in bilanciamento                                |
| **Version Control** | Git                          | -         | Repository remota (backup/sicurezza)               |

---

## 6. TIMELINE E FASI IMPLEMENTAZIONE

### Fase 1 - MVP (Sprint 1-3: 6-8 settimane)

**Sprint 1 (Settimana 1-2): Setup + Core Infrastructure**:

- Configurazione progetto Laravel + Filament
- Setup asset pipeline (Vite + Tailwind)
- Database schema e migrations
- API skeleton (routes + controllers base)
- Autenticazione (login admin, login user, registrazione esterna)

**Sprint 2 (Settimana 3-4): Area Amministrativa - Ordini**:

- Dashboard admin (grafici, KPI)
- Lista ordini + filtri avanzati
- Dettaglio Ordine
- Invito utenti (e-mail + token)
- Sincronizzazione API ETB

**Sprint 3 (Settimana 5-6): Area Utente - Raccolta Dati**:

- Dashboard utente
- Interfaccia selezione pagine (PowerPoint-style)
- Form compilazione dinamico
- Salvataggio bozza (auto + manuale)
- Validazione form

**Sprint 4 (Settimana 7-8): Bozze e Correzioni**:

- Generazione bozza PDF
- Visualizzatore PDF con annotazioni
- Flusso correzioni
- Cicli revisione limitati
- Testing e bug fixes

**Deliverable Fase 1**:

- Applicazione web funzionante (dev environment)
- API REST completa
- Database strutturato
- Documentazione tecnica
- Test coverage 60%+

---

## 7. SETUP PROGETTO E COMANDI LANCIATI

### 7.1 Creazione cartella progetto

- Creazione della cartella di lavoro `CMSDiari`.
- Inizializzazione del progetto all'interno della cartella.

```bash
mkdir CMSDiari
cd CMSDiari
```

### 7.2 Installazione Laravel

- Installazione di Laravel tramite Composer.
- Creazione e configurazione iniziale dell’ambiente `.env` e generazione della chiave applicativa.

```bash
composer create-project laravel/laravel .
php artisan key:generate
```

### 7.3 Installazione Filament

- Installazione di Filament tramite Composer. Filament è un template per Applicazioni Laravel.
- Configurazione del panel e delle risorse iniziali.

```bash
composer require filament/filament
```

### 7.4 Setup rapido (script)

```bash
composer run setup
```

### 7.5 Installazione e build asset

- Installazione dipendenze frontend (Vite/Tailwind).
- Build degli asset per produzione.

```bash
npm install
npm run build
```

### 7.6 Deploy iniziale su server (FTP)

- Primo push del progetto sul server remoto tramite **FTP semplice** (non SFTP).
- Upload dei file tramite client FTP (es. FileZilla) verso la cartella di pubblicazione prevista sul server.
- `vendor/` e `node_modules/` non vengono versionati nel repository; in produzione si ricostruiscono con Composer/NPM quando previsto.

### 7.7 Repository Git (backup/sicurezza)

- Creazione repository Git locale.
- Push su repository remota come “copia di sicurezza”.

```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin <URL_REPOSITORY>
git push -u origin main
```

---

## 8. CREAZIONE DATABASE

```sql
-- USERS
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NULL, -- è NULL quando company != 'Scuola'
  first_name VARCHAR(255) NULL,
  last_name VARCHAR(255) NULL,
  born_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin|admin', 'internal|redattore', 'internal|grafico', 'external|referente', 'external|collaboratore') NOT NULL,
  company ENUM('Spaggiari','La Fabbrica','Scuola'),
  status ENUM('active','blocked','deleted') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uq_users_email (email),
  KEY idx_users_school_id (school_id),
  KEY idx_users_role (role),
  KEY idx_users_status (status),

  CONSTRAINT fk_users_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_users_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CAMPAIGNS
CREATE TABLE campaigns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description TEXT NULL,
  year VARCHAR(4) NOT NULL, -- es: "2026"
  status ENUM('planned','started','completed','deleted') NOT NULL DEFAULT 'planned',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  KEY idx_campaigns_year (year),
  KEY idx_campaigns_status (status),

  CONSTRAINT fk_campaigns_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_campaigns_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SCHOOLS
CREATE TABLE schools (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  external_id VARCHAR(64) NULL,  -- id scuola su ETB
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uq_schools_external_id (external_id),

  CONSTRAINT fk_schools_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_schools_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TEMPLATES
CREATE TABLE templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  external_id VARCHAR(64) NULL,           -- id template su ETB
  order_id INT UNSIGNED NULL,
  campaign_id BIGINT UNSIGNED NULL,       -- null = riutilizzabile; oppure legato a Campagna
  code VARCHAR(128) NOT NULL,             -- codice articolo ETB (DIARIO-2026-...)
  description TEXT NOT NULL,
  max_pages INT UNSIGNED NULL,
  structure JSON,
  status ENUM('active','deleted') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uq_templates_code (code),
  UNIQUE KEY uq_templates_external_id (external_id),
  KEY idx_templates_order_id (order_id),
  KEY idx_templates_campaign_id (campaign_id),

  CONSTRAINT fk_templates_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_templates_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_templates_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAGE TYPES
CREATE TABLE pageTypes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description TEXT NULL,
  space DECIMAL(5,2) NOT NULL, -- es 0.50, 1.00, 2.00
  structure JSON,
  icon VARCHAR(512) NULL,
  status ENUM('active','deleted') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  KEY idx_pageTypes_status (status),

  CONSTRAINT fk_pageTypes_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_pageTypes_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TEMPLATE <-> PAGE TYPES
CREATE TABLE template_pageTypes (
  template_id BIGINT UNSIGNED NOT NULL,
  page_type_id BIGINT UNSIGNED NOT NULL,
  is_mandatory BOOLEAN NOT NULL DEFAULT false,
  max_occurrences INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  PRIMARY KEY (template_id, page_type_id),
  KEY idx_tpt_template_id (template_id),
  KEY idx_tpt_page_type_id (page_type_id),

  CONSTRAINT fk_tpt_template
    FOREIGN KEY (template_id) REFERENCES templates(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_tpt_page_type
    FOREIGN KEY (page_type_id) REFERENCES pageTypes(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_tpt_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tpt_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ORDERS
CREATE TABLE orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  external_id VARCHAR(64) NULL,                 -- id ordine su ETB
  campaign_id BIGINT UNSIGNED NOT NULL,
  school_id BIGINT UNSIGNED NOT NULL,
  template_id BIGINT UNSIGNED NULL,             -- valorizzato quando avviene l'associazione con il Template
  quantity INT UNSIGNED NOT NULL DEFAULT 1,     -- numero Diari da stampare
  deadline_collection DATETIME NULL,
  deadline_annotation DATETIME NULL,
  status ENUM('new','collection','draft','annotation','approved','production','completed','deleted') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uq_orders_external_id (external_id),
  KEY idx_orders_campaign_id (campaign_id),
  KEY idx_orders_school_id (school_id),
  KEY idx_orders_template_id (template_id),
  KEY idx_orders_status (status),
  KEY idx_orders_deadline_collection (deadline_collection),
  KEY idx_orders_deadline_annotation (deadline_annotation),

  CONSTRAINT fk_orders_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_orders_school
    FOREIGN KEY (school_id) REFERENCES schools(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_orders_template
    FOREIGN KEY (template_id) REFERENCES templates(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_orders_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_orders_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INVITATIONS
CREATE TABLE invitations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL, -- è null finché l'Utente non si registra
  email VARCHAR(128),
  subject TEXT NULL,
  message TEXT NULL,
  token VARCHAR(128) NOT NULL,
  role ENUM('admin|admin', 'internal|redattore', 'internal|grafico', 'external|referente', 'external|collaboratore') NOT NULL,
  status ENUM('ready', 'invited','received','expired','registered','active','deleted') NOT NULL DEFAULT 'ready',
  sent_at DATETIME NULL,
  expires_at DATETIME NULL,
  registered_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uq_invitations_token (token),
  KEY idx_invitations_order_id (order_id),
  KEY idx_invitations_user_id (user_id),
  KEY idx_invitations_status (status),

  CONSTRAINT fk_invitations_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_invitations_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_invitations_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_invitations_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DRAFTS
CREATE TABLE drafts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL,
  file_path VARCHAR(1024) NULL,
  data_json JSON NULL,              -- Struttura del Template, compilata
  data_xml LONGTEXT NULL,
  data_csv LONGTEXT NULL,
  status ENUM('empty','collecting','collected','published','annotating','approved','rejected') NOT NULL DEFAULT 'empty',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uq_drafts_order_version (order_id, version),
  KEY idx_drafts_order_id (order_id),
  KEY idx_drafts_version (version),
  KEY idx_drafts_status (status),

  CONSTRAINT fk_drafts_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_drafts_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_drafts_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ANNOTATIONS
CREATE TABLE annotations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  draft_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  page_number INT UNSIGNED NOT NULL,
  type ENUM('text','highlight','drawing') NOT NULL,
  content JSON NOT NULL,
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  status ENUM('pending','fixed','deleted') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  KEY idx_annotations_draft_id (draft_id),
  KEY idx_annotations_user_id (user_id),
  KEY idx_annotations_priority (priority),
  KEY idx_annotations_status (status),

  CONSTRAINT fk_annotations_draft
    FOREIGN KEY (draft_id) REFERENCES drafts(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_annotations_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_annotations_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_annotations_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- APP SETTINGS (Pannello Impostazioni)
CREATE TABLE app_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(128) NOT NULL,
  value TEXT NOT NULL,
  type ENUM('int','float','bool','string','json') NOT NULL DEFAULT 'string',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uq_app_settings_description (description),

  CONSTRAINT fk_app_settings_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_app_settings_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- LOG
CREATE TABLE logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id     BIGINT UNSIGNED NOT NULL,
  draft_id     BIGINT UNSIGNED NULL, -- valorizzato solo se l'evento riguarda una specifica bozza/versione
  entity_id    BIGINT UNSIGNED NULL,
  entity      VARCHAR(32) NOT NULL,
  from_status VARCHAR(32) NULL,
  to_status   VARCHAR(32) NULL,      -- NULL per eventi "informativi" (es. change deadline)
  source ENUM('manual','system','etb') NOT NULL DEFAULT 'manual',
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,        -- NULL = sistema/cron

  KEY idx_logs_entity (entity),
  KEY idx_logs_entity_id (entity_id),
  KEY idx_logs_draft_id (draft_id),
  KEY idx_logs_order_id (order_id),

  CONSTRAINT fk_logs_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_logs_draft
    FOREIGN KEY (draft_id) REFERENCES drafts(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_logs_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_logs_entity
    CHECK (
      (entity = 'order' AND draft_id IS NULL)
      OR
      (entity = 'draft' AND draft_id IS NOT NULL)
      OR
      (entity NOT IN ('order','draft'))
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
