# Studiofinance — Specifiche di implementazione

> Documento derivato da `studiofinance-brief.md`. Il brief resta la fonte canonica per il funzionale (entità, formule fiscali, viste). Questo documento aggiunge le decisioni di implementazione raffinate con l'utente: flussi UX puntuali, validazioni, edge case, soft delete, reversibilità, fasi.

## Contesto

Sistema di rendicontazione fiscale e finanziaria per professionisti in regime forfettario. Lo scheletro Laravel 13 + Inertia Vue 3 + Tailwind v4 esiste (auth Fortify completa, 2FA, passkeys, profilo, settings, layout shadcn-vue). Il dominio Studiofinance è da costruire interamente da zero: nessun modello, controller, pagina o componente di business esiste oggi.

La feature è grande: per ridurre il rischio si implementa in fasi sequenziali, ciascuna testabile in isolamento.

---

## Flusso utente

### F1. Registrazione e primo onboarding

1. Visitatore arriva su `/register` (registrazione aperta a chiunque, già esistente da Fortify).
2. Conferma email (flusso Fortify esistente).
3. Al primo login, il sistema rileva profilo Studiofinance incompleto e redirect forzato su `/onboarding` (bloccante: nessun altro link è raggiungibile finché non si completa).
4. La pagina `/onboarding` chiede in un singolo form a step unico:
   - Nome del professionista (string, required)
   - Coefficiente di redditività (decimal, default `78`, range 0–100, indicato in percentuale)
   - Anno di inizio attività (integer, default = anno corrente, range 1990–anno corrente)
5. Submit: il sistema crea il profilo professionista, esegue il seeding di **voci di spesa standard** e **scadenze tipo standard** per quell'utente (vedi F2), e redirect su `/` (dashboard).
6. La dashboard si carica in stato "empty: nessun anno aperto" (vedi F8).

### F2. Seeding iniziale per nuovo utente (lato server, atomico col profilo)

All'atto della creazione del profilo onboarding il sistema crea per l'utente, in una singola transazione:

- 8 voci di spesa template (Imposta sostitutiva, Inarcassa Soggettivo, Integrativo, Maternità, Bolli, Commercialista, Assicurazione, OATO) con i valori di default 2025 più recenti che il sistema conosce (vedi `database/seeders` — il sistema porta un set di seed di base; gli aggiornamenti annuali futuri restano a carico dell'utente, che modifica i template prima di aprire l'anno successivo).
- Le scadenze tipo standard collegate (rate/saldo IS, acconti/saldo Inarcassa, dichiarazione redditi, comunicazione reddituale Inarcassa, 4 rate trimestrali bolli, assicurazione 31/03, OATO 31/03, commercialista 31/12 con flag "anno di riferimento = successivo").

L'utente può modificare ed estendere entrambi i cataloghi prima di aprire un anno.

### F3. Anagrafica clienti

1. L'utente entra nella vista `/clienti`. Lista paginata con search per denominazione/P.IVA/CF.
2. Pulsante "Nuovo cliente" apre form con campi: denominazione (required), P.IVA (opzionale), Codice Fiscale (opzionale), flag "soggetto a ritenuta bancaria 8%", note libere. **Almeno uno tra P.IVA e CF deve essere compilato**.
3. Click su una riga apre `/clienti/{id}`: dettaglio + storico fatturato pluriennale + lista fatture verso il cliente.
4. Edit / archiviazione tramite menu kebab in lista o pulsanti in dettaglio.

### F4. Inserimento fatture — manuale

1. Vista `/fatture` con pulsanti "Nuova fattura" e "Importa XML".
2. "Nuova fattura" apre form con campi: numero, data emissione, cliente (autocomplete su anagrafica con CTA inline "Crea nuovo cliente" se non trovato), imponibile, cassa Inarcassa 4% (precompilata = imponibile × 4%, sovrascrivibile), bollo (default €2 se imponibile > €77,47, ma editabile sempre; il sistema non blocca), spese anticipate art.15, totale fattura (calcolato, read-only: imponibile + cassa + bollo + art.15), flag "soggetta a ritenuta bancaria 8%" (precompilato dal flag cliente, sovrascrivibile sulla singola fattura).
3. Submit: validazioni (unicità numero per utente+anno, importi ≥ 0, data valida).

### F5. Inserimento fatture — import XML multiplo

1. Pulsante "Importa XML" apre dialog di upload con drag&drop e selezione multipla (`accept=".xml"`).
2. Il sistema parsa ogni file, mostrando una **lista di anteprime**: per ogni file una card editabile in linea con:
   - Tutti i campi della fattura precompilati (numero, data, imponibile, cassa, bollo, art.15, totale, ritenuta) — tutti editabili.
   - Cliente: auto-matchato su P.IVA o CF estratti dall'XML.
     - Se trovato: dropdown precompilato con il cliente esistente, sostituibile.
     - Se non trovato: il sistema mostra una sezione "Nuovo cliente da XML" precompilata con denominazione/P.IVA/CF/indirizzo letti dall'XML, editabile inline. Switch "in alternativa, collega a cliente esistente" che apre il dropdown.
   - Stato per riga: `da confermare` / `pronto` / `scartato`. Toggle "scarta" per saltare il file.
3. Pulsante "Conferma e importa tutto" in fondo: in una singola transazione vengono creati i clienti nuovi e le fatture pronte. I file scartati vengono ignorati.
4. Risultato: toast con riepilogo ("N fatture importate, M clienti creati, K scartati") e redirect su `/fatture`.

### F6. Apertura nuovo anno (wizard)

L'apertura è sempre un'azione manuale. Da `/anni` (vista pluriennale) pulsante "Apri nuovo anno".

1. **Step 1 — Scelta anno**: input numerico (default = anno corrente, ma è ammesso qualsiasi anno solare). Il sistema verifica che l'anno non sia già aperto.
2. **Step 2 — Voci di spesa**: tabella delle voci di spesa attive del template; per ogni riga sono modificabili aliquota / minimale / massimale / quota. Defaults ereditati dal template. È possibile escludere singole voci da questo anno (toggle attivo/inattivo per istanza).
3. **Step 3 — Scadenze**: anteprima della lista di scadenze tipo che verranno generate per quell'anno con date calcolate. Modifiche delle date sull'anno corrente sono possibili inline.
4. **Cross-year check**: se ci sono scadenze tipo con flag "anno di riferimento = successivo" (es. Commercialista) e l'anno N+1 **non esiste**, il sistema mostra un alert nello Step 3: "Le seguenti scadenze referenziano l'anno {N+1} che non esiste ancora. Procedendo verrà creato un Anno {N+1} 'pre-aperto' con solo la spesa annuale necessaria; potrai aprirlo formalmente in seguito con tutto il resto." L'utente deve cliccare "Conferma" per procedere.
5. **Submit**: transazione unica che crea: l'Anno, le Spese annuali (istanze), le Scadenze (istanze) con le date calcolate, i Pagamenti `pianificato` collegati alle scadenze di tipo Pagamento, e — se serve — l'Anno N+1 pre-aperto con la sola spesa "Commercialista N+1".
6. Redirect su `/anni/{YYYY}` (vista anno).

### F7. Registrazione pagamento

1. Dalla vista `/scadenze` (cronologica), l'utente vede le scadenze `aperte`.
2. Click su una scadenza di tipo `Pagamento` apre un side-sheet con i dati della scadenza e del pagamento pianificato collegato.
3. Pulsante "Registra pagamento": form precompilato con descrizione (= nome scadenza), spesa annuale (read-only), data effettiva (default = oggi), importo (vuoto). L'utente compila importo da F24 / avviso.
4. Submit: il pagamento passa da `pianificato` → `pagato`, la scadenza passa da `aperta` → `completata`.
5. Variante "non dovuto": pulsante "Marca come non dovuto" sulla scadenza apre dialog di conferma; al submit la scadenza diventa `non dovuta` e il pagamento collegato diventa `non dovuto`.

### F8. Pagamento manuale extra-scadenza

Pulsante "Nuovo pagamento" disponibile da: vista `/pagamenti`, dettaglio Spesa annuale, vista `/scadenze`.

1. Form: spesa annuale di riferimento (autocomplete tra le spese degli anni esistenti, required), descrizione, importo, data effettiva, note.
2. Submit: pagamento creato direttamente in stato `pagato`, scadenza collegata = NULL.

### F9. Reversibilità stati (con conferma)

- Scadenza `completata` → menu "Annulla completamento" → torna `aperta`, pagamento collegato torna `pianificato` (importo e data svuotati). Dialog: "Annullare il completamento? L'importo e la data del pagamento verranno azzerati."
- Scadenza `non dovuta` → menu "Riapri" → torna `aperta`, pagamento collegato torna `pianificato`.
- Pagamento `pagato` (non collegato a scadenza, manuale) → menu "Annulla pagamento" → cancellazione (soft delete) con conferma.
- Pagamento `pagato` (collegato a scadenza) → annullamento avviene tramite reversibilità della scadenza (vedi sopra).

### F10. Empty state dashboard

Se l'anno corrente non è ancora stato aperto, la dashboard mostra:
- Saluto "Ciao {nome}"
- Card centrale: "Non hai ancora aperto l'anno {anno_corrente}" + CTA "Apri anno corrente" che porta al wizard F6 con anno preselezionato.
- KPI grigi/azzerati. Sezioni "Ultime fatture", "Ultimi pagamenti", "Scadenze imminenti" funzionano comunque se ci sono dati di anni precedenti o non legati a un anno.

### F11. Modifica profilo professionale

Da `/settings/profile` (estendere la pagina Fortify esistente o creare nuova rotta `/settings/professionale`):
- Edit nome, coefficiente di redditività, anno di inizio attività.
- Se l'utente cambia coefficiente o anno di inizio e **esistono anni già aperti**, il submit apre dialog: "Hai modificato il coefficiente di redditività. Vuoi propagare il nuovo valore agli anni già aperti?" con una checklist (un checkbox per anno esistente, tutti unchecked di default). L'utente seleziona gli anni e conferma. La propagazione aggiorna il coefficiente sull'entità Anno (non sui template di spesa). Per l'anno di inizio attività, la propagazione aggiorna l'aliquota della Spesa annuale "Imposta sostitutiva" (5% o 15%).

---

## Regole di business

### RB1. Profilo utente
- Esattamente un profilo per utente. Onboarding obbligatorio prima di qualsiasi altra azione.
- Modifiche al profilo richiedono conferma esplicita per la propagazione agli anni esistenti.

### RB2. Clienti
- Almeno uno tra P.IVA e Codice Fiscale è obbligatorio.
- Validazione formato: P.IVA 11 cifre numeriche, CF 16 caratteri alfanumerici (validatore Italia standard, stesso pattern che si trova nei pacchetti `italia-php/codice-fiscale` o regex inline).
- Un cliente non può essere cancellato se ha fatture collegate (anche soft-deletate). Si può solo archiviare/disattivare (soft delete del cliente: viene nascosto dalle liste, ma resta accessibile ai calcoli storici).
- Matching all'import: lookup per P.IVA prima, poi per CF. Match esatto.

### RB3. Fatture
- Numero univoco per `(user_id, anno_di_emissione)`. Tra anni diversi può ripetersi.
- Importi `>= 0`. Cassa Inarcassa precompilata = imponibile × 4%, sovrascrivibile.
- Bollo: campo libero, no regole automatiche.
- `art_15` (spese anticipate fuori campo IVA) escluso dalla base imponibile fiscale e contributiva.
- Se flag "soggetta a ritenuta bancaria" è attivo: ritenuta = totale fattura × 8%, calcolata al volo (vista derivata), scalata dall'imposta sostitutiva annua.
- La data di emissione è il riferimento per gli aggregati annuali/mensili (no FK esplicita su anno).

### RB4. Voci di spesa (template)
- Catalogo per utente. Set iniziale via seeder al primo onboarding.
- Tipo di calcolo: `perc_reddito_irpef`, `perc_volume_affari_iva`, `fissa_annuale`, `somma_bolli`.
- Disattivare una voce template = soft delete: non appare più nelle liste e nel wizard di apertura nuovi anni. Le istanze esistenti restano intatte.

### RB5. Spese annuali (istanze)
- Generate al wizard di apertura anno (F6) dal template attivo, copia indipendente dei valori.
- Una volta create, sono indipendenti dal template: aggiornare il template non altera le istanze esistenti.
- Per voci percentuali: importo previsto = `MAX(MIN(base, massimale) × aliquota, minimale)`, calcolato sui dati dell'anno.
- Per voce `Bolli`: importo previsto = somma bolli sulle fatture dell'anno (vista derivata, sempre).
- Per voci fisse: importo previsto = quota inserita dall'utente.
- Campo `importo_effettivo` (override eccezionale): se non NULL, prende il sopravvento sul previsto.
- `importo_definitivo` = `coalesce(importo_effettivo, importo_previsto)`.
- `importo_pagato` = somma pagamenti `pagato` collegati.
- `importo_da_pagare` = `importo_definitivo − importo_pagato`.
- `importo_da_pagare_ad_oggi`:
  - Voci fisse: `(importo_definitivo / 12) × mese_corrente` se anno corrente, altrimenti `importo_definitivo`.
  - Voci percentuali: `importo_definitivo` (la base è già "ad oggi" per costruzione).
- **Imposta sostitutiva** ha campo extra `credito_anno_precedente` (decimale, modificabile).
  - Default precompilato dal sistema calcolando `MAX(0, totale_pagato_IS_anno_precedente − imposta_netta_anno_precedente)`.
  - Se l'utente sovrascrive, mostrare alert inline: "Valore calcolato: {X}. Stai usando {Y}."

### RB6. Aggiunta voce template ad anno in corso
- Quando l'utente crea una nuova voce nel template mentre uno o più anni sono già aperti, il form chiede esplicitamente con checkbox: "Attiva su anni già aperti: [anno corrente] [anni passati]". Default: tutti unchecked. Sugli anni selezionati viene generata l'istanza con i valori del template.
- Spese una tantum: creazione diretta dall'interno di un anno (dettaglio anno → "Aggiungi spesa una tantum"), `voce_spesa_id = NULL`, tipo di calcolo e quota definiti sull'istanza.

### RB7. Scadenze tipo (template)
- Catalogo per utente. Set iniziale via seeder.
- Per scadenze di tipo `Pagamento`: collegamento a una voce di spesa template + flag `anno_riferimento_spesa` (`corrente` / `successivo`).
- Per scadenze di tipo `Adempimento`: nessun collegamento.
- Disattivare una scadenza tipo = soft delete: niente più generazione automatica per nuovi anni.

### RB8. Scadenze (istanze)
- Generate al wizard di apertura anno (F6).
- Una scadenza di tipo `Pagamento` ha esattamente un pagamento collegato (1:1).
- Stati: `aperta` (default), `completata`, `non dovuta`. Reversibili tutti con conferma.
- Cross-year: per scadenze con `anno_riferimento_spesa = successivo`, il pagamento punta alla spesa annuale dell'anno N+1; se l'anno N+1 non esiste, viene creato un Anno "pre-aperto" con la sola spesa necessaria.

### RB9. Pagamenti
- Stati: `pianificato`, `pagato`, `non dovuto`.
- Solo i pagamenti `pagato` concorrono ai totali `importo_pagato` della spesa.
- Sempre collegati a una sola spesa annuale (mai a multiple).
- Possono avere `scadenza_id = NULL` per i pagamenti manuali extra-scadenza (F8).
- Due "anni" rilevanti: l'anno della spesa di riferimento (imputazione) e l'anno della data effettiva (cassa). I contributi previdenziali sono deducibili nell'anno della data effettiva, indipendentemente dall'anno della spesa.

### RB10. Anni
- Manualmente aperti dall'utente.
- Campo `coefficiente_redditivita` (copia indipendente dal profilo al momento dell'apertura).
- Non hanno stato esplicito: nessun "chiuso" / "aperto".
- L'anno "pre-aperto" (creato implicitamente per coprire una scadenza cross-year) è un anno normale ma con solo le istanze strettamente necessarie. Quando l'utente lo apre formalmente, il wizard parte normalmente e completa le istanze mancanti, riusando quella già esistente.

### RB11. Quote mensili e accantonamenti
- Quote mensili: derivate al volo. Non a DB.
- Imposta sostitutiva mensile: `imponibile_del_mese × coefficiente × aliquota` (ignorando contributi pagati).
- Contributi mensili Inarcassa: applicare l'aliquota sulla base maturata nel mese (reddito IRPEF mensile per Soggettivo, volume d'affari IVA mensile per Integrativo), 1/12 per Maternità. **Ignorare i minimali annuali**.
- Alert "minimale in arrivo": opzionale, se a fine anno il cumulato risulta sotto al minimale, mostrare un badge informativo nella vista anno (Fase 4).

### RB12. Arrotondamenti
- Calcoli interni: precisione al centesimo (BCMath o decimal Eloquent cast).
- Visualizzazione: imposta sostitutiva e valori modello Unico arrotondati all'unità; Inarcassa con centesimi; resto con centesimi.
- Regola: commerciale standard (≥ 0,50 → sopra; < 0,50 → sotto).

### RB13. Soft delete trasversale
- **Tutte le entità di dominio** usano `SoftDeletes` (clienti, fatture, voci di spesa template, spese annuali, scadenze tipo, scadenze, pagamenti, anni).
- "Cancellare" da UI = soft delete. Le entità soft-deletate sono escluse dalle liste e dai calcoli aggregati di default.
- Una vista "Archivio" (Fase 5) permette di consultare/ripristinare entità archiviate.
- Vincoli di cancellazione applicati a soft delete (vedi RB14).

### RB14. Cancellazione con vincoli
Bloccare il soft delete se ci sono dipendenze attive (non soft-deletate):
- **Cliente** con fatture attive → blocco con messaggio "Questo cliente ha N fatture. Archivia o sposta prima le fatture."
- **Voce di spesa template** con istanze attive → blocco con messaggio "Questa voce è usata in N anni. Disattivala (resta come template ma non viene proposta) o rimuovi prima le istanze."
- **Spesa annuale** con pagamenti `pagato` → blocco con messaggio "Ci sono N pagamenti registrati su questa spesa. Annullali prima o non puoi cancellare."
- **Scadenza** con pagamento `pagato` → blocco con messaggio analogo.
- **Anno** con qualsiasi fattura, spesa annuale o pagamento attivi → blocco con messaggio dettagliato del contenuto.
- **Pagamento `pianificato`** o `non dovuto` collegato a scadenza → non cancellabile direttamente (gestito tramite reversibilità scadenza).
- **Pagamento `pagato` collegato a scadenza** → annullabile via reversibilità scadenza (F9), non via delete diretto.
- **Pagamento `pagato` manuale extra-scadenza** → cancellabile direttamente con conferma.
- **Fattura** → sempre cancellabile con conferma (i calcoli si aggiornano automaticamente).

### RB15. Editing
- Fatture e pagamenti sono sempre modificabili. Modifiche aggiornano automaticamente i calcoli derivati (sono viste, non materializzati).
- Editing di una fattura non rispetta vincoli storici di "anno chiuso" (concetto inesistente nel sistema).

### RB16. Multi-utente
- Ogni entità di dominio ha FK `user_id`.
- Global scope di tenancy: trait/scope su tutti i modelli che filtra per `auth()->id()`. Nessun cross-user leak possibile.
- Implementazione consigliata: trait `BelongsToUser` con boot di global scope, applicato a tutti i modelli di dominio.

### RB17. Lingua
- Tutto in italiano. Nessun i18n in prima versione.

---

## Dati e relazioni

### Tabelle nuove da creare

> Note generali: tutte le tabelle hanno `id` (bigint auto), `user_id` (FK su users, cascade), `created_at`, `updated_at`, `deleted_at` (soft delete). Tutti gli importi monetari sono `decimal(12,2)`.

#### `professional_profiles`
Profilo professionale (uno per utente, popolato all'onboarding).

| campo | tipo | note |
|---|---|---|
| user_id | FK unique | one-to-one con users |
| nome | string | required |
| coefficiente_redditivita | decimal(5,2) | percentuale, es. 78.00 |
| anno_inizio_attivita | year | per calcolo aliquota IS 5%/15% |

Onboarding completato sse esiste record `professional_profile` per l'utente. Middleware `EnsureOnboarded` redirect su `/onboarding` altrimenti.

#### `clienti`
| campo | tipo | note |
|---|---|---|
| denominazione | string | required |
| partita_iva | string(11) | nullable, indice non-unique |
| codice_fiscale | string(16) | nullable, indice non-unique |
| soggetto_ritenuta_bancaria | boolean | default false |
| note | text | nullable |

Vincolo applicativo: `partita_iva OR codice_fiscale NOT NULL`.

#### `fatture`
| campo | tipo | note |
|---|---|---|
| cliente_id | FK clienti | required |
| numero | string | required, unique per `(user_id, year(data_emissione))` |
| data_emissione | date | required |
| imponibile | decimal(12,2) | default 0 |
| cassa_inarcassa | decimal(12,2) | default 0 |
| bollo | decimal(12,2) | default 0 |
| art_15_spese_anticipate | decimal(12,2) | default 0 |
| totale | decimal(12,2) | calcolato in app: imponibile + cassa + bollo + art15 |
| soggetta_ritenuta_bancaria | boolean | default false |
| xml_path | string | nullable, path al file XML originale se importato |

Indice composito `(user_id, data_emissione)` per query annuali/mensili.

#### `voci_spesa` (template)
| campo | tipo | note |
|---|---|---|
| nome | string | required |
| tipo_calcolo | enum | `perc_reddito_irpef`, `perc_volume_affari_iva`, `fissa_annuale`, `somma_bolli` |
| aliquota_default | decimal(5,2) | nullable |
| minimale_default | decimal(12,2) | nullable |
| massimale_default | decimal(12,2) | nullable |
| quota_default | decimal(12,2) | nullable |
| attiva | boolean | default true |
| ordine | integer | per ordinamento UI |

#### `anni`
| campo | tipo | note |
|---|---|---|
| anno | year | required, unique per user_id |
| coefficiente_redditivita | decimal(5,2) | copia da profilo all'apertura |
| note | text | nullable |

#### `spese_annuali`
| campo | tipo | note |
|---|---|---|
| anno_id | FK anni | required |
| voce_spesa_id | FK voci_spesa | nullable (per una tantum) |
| nome | string | copia dal template o libero per una tantum |
| tipo_calcolo | enum | copia |
| aliquota | decimal(5,2) | nullable |
| minimale | decimal(12,2) | nullable |
| massimale | decimal(12,2) | nullable |
| quota | decimal(12,2) | nullable |
| importo_effettivo | decimal(12,2) | nullable, override |
| credito_anno_precedente | decimal(12,2) | nullable, solo per IS |
| note | text | nullable |

Indice `(user_id, anno_id)`.

#### `scadenze_tipo` (template)
| campo | tipo | note |
|---|---|---|
| nome | string | required |
| giorno | tinyint | 1-31 |
| mese | tinyint | 1-12 |
| tipo | enum | `pagamento`, `adempimento` |
| voce_spesa_id | FK voci_spesa | nullable, solo per `pagamento` |
| anno_riferimento_spesa | enum | `corrente`, `successivo` |
| attiva | boolean | default true |

#### `scadenze` (istanze)
| campo | tipo | note |
|---|---|---|
| anno_id | FK anni | required |
| scadenza_tipo_id | FK scadenze_tipo | nullable (per scadenze manuali) |
| nome | string | copia o libero |
| data_prevista | date | required |
| tipo | enum | `pagamento`, `adempimento` |
| spesa_annuale_id | FK spese_annuali | nullable, solo per `pagamento` |
| stato | enum | `aperta`, `completata`, `non_dovuta` |
| note | text | nullable |

Indice `(user_id, data_prevista)`.

#### `pagamenti`
| campo | tipo | note |
|---|---|---|
| spesa_annuale_id | FK spese_annuali | required |
| scadenza_id | FK scadenze | nullable |
| descrizione | string | nullable |
| importo | decimal(12,2) | nullable (vuoto se pianificato) |
| data_effettiva | date | nullable (vuoto se pianificato) |
| stato | enum | `pianificato`, `pagato`, `non_dovuto` |
| note | text | nullable |

Indice `(user_id, data_effettiva)` per la query "contributi pagati nell'anno".

### Relazioni

- `User 1—1 ProfessionalProfile`
- `User 1—n Cliente, Fattura, VoceSpesa, Anno, ScadenzaTipo` (e a cascata tutto il dominio)
- `Cliente 1—n Fattura`
- `Anno 1—n SpesaAnnuale, Scadenza, Fattura` (via data_emissione)
- `VoceSpesa 1—n SpesaAnnuale` (nullable per una tantum)
- `SpesaAnnuale 1—n Pagamento`
- `Scadenza 1—0..1 Pagamento` (1:1 strict per scadenze di tipo `pagamento`)
- `ScadenzaTipo 1—n Scadenza`

### Viste derivate (mai a DB)

Implementate come metodi sui modelli o servizi:
- `Fattura::ritenutaBancariaCalcolata()` — totale × 0.08 se flag attivo.
- `SpesaAnnuale::importoPrevisto()`, `importoDefinitivo()`, `importoPagato()`, `importoDaPagare()`, `importoDaPagareAdOggi()`.
- `Anno::redditoIrpef()` — Σ imponibile × coefficiente − contributi pagati nell'anno (data_effettiva).
- `Anno::redditoNettoEffettivo()` — Σ fatturato − Σ spese definitive.
- `Anno::contributiPagatiNellAnno()` — somma pagamenti con data_effettiva nell'anno collegati a spese contributi previdenziali.
- `Anno::impostaSostitutivaNetta()` — lorda − ritenute − credito anno precedente.
- `Anno::creditoFineAnno()` — `MAX(0, pagamenti_IS − netta)`.
- `Mese::quotaSpesaAnnuale(SpesaAnnuale)` — per la vista mensile.

---

## Permessi e ruoli

- **Solo utenti autenticati e verificati** (middleware Fortify già in uso: `auth`, `verified`).
- **Tenancy stretta**: ogni utente vede solo i propri dati. Global scope su tutti i modelli di dominio.
- **Nessun ruolo aggiuntivo**: nessun admin, nessuna gerarchia. Ogni utente è un'isola.
- **Middleware `EnsureOnboarded`** (nuovo): redirect su `/onboarding` se `professional_profile` mancante. Da applicare a tutte le rotte di dominio.

---

## Corner case e gestione errori

### Onboarding
- Se l'utente tenta di accedere a una rotta di dominio senza profilo → redirect a `/onboarding`.
- Se l'utente apre `/onboarding` quando già onboarded → redirect a `/`.

### Clienti
- Tentativo di salvare cliente senza P.IVA né CF → errore validazione inline.
- Formato P.IVA/CF non valido → errore inline (non blocca, ma warning visibile + flag "verifica formato").
- Cancellazione cliente con fatture → blocco con messaggio (RB14).

### Fatture
- Numero duplicato per stesso anno → errore inline.
- Importazione XML con file corrotto o non valido → quella card di anteprima mostra errore di parsing, l'utente può saltarla ("scarta").
- Importazione XML con totale ≠ somma campi (controllo di sanità) → warning sulla card, ma non blocco.
- Cliente non matchato in XML e nessuna scelta esplicita prima del submit → il sistema lo crea (è il default).
- Fattura con data fuori dagli anni aperti → permessa (gli aggregati derivati la "scopriranno" quando l'utente apre quell'anno).
- Cancellazione fattura → conferma testuale "Sei sicuro? La fattura verrà archiviata."

### Voci di spesa template
- Cambio di tipo di calcolo su un template già usato in anni esistenti → modifica solo il template, non propaga. Per propagare l'utente deve editare manualmente le istanze.
- Aggiunta nuova voce con anni già aperti → checkbox "Attiva su anni: corrente / passati / nessuno". Mai propagata di default.

### Spese annuali
- Tentativo di cancellazione con pagamenti pagati → blocco (RB14).
- Modifica aliquota dopo che esistono pagamenti → ammessa, calcoli si ricalcolano. Mostra warning se l'importo definitivo scende sotto il pagato (potenziale credito).
- Spesa Bolli: tutti i campi calcolo sono read-only nel form (l'importo previsto = sempre somma bolli).

### Scadenze
- Modifica data della scadenza → permessa. Se è già `completata`, la modifica genera warning ma non blocco.
- Apertura anno N quando esiste già anno N "pre-aperto" → il wizard riusa la spesa Commercialista esistente, popola il resto.
- Creazione scadenza manuale con data passata → permessa (utile per ricostruire storico).

### Pagamenti
- Pagamento `pianificato` con importo = NULL → escluso dai totali.
- Pagamento `pagato` con importo > definitivo della spesa → permesso (genera credito), warning informativo.
- Modifica data effettiva tra anni diversi → il pagamento "migra" naturalmente di anno (vista di cassa), il sistema non blocca.
- Cancellazione pagamento pagato da scadenza → bloccato, deve passare per reversibilità scadenza.

### Anni
- Apertura di un anno già esistente → blocco con messaggio "Anno {YYYY} già aperto. Vai a /anni/{YYYY}."
- Anno "pre-aperto" automatico: nessun warning aggiuntivo, è trasparente per l'utente.
- Cancellazione anno con qualsiasi dato → blocco (RB14).

### Modifica profilo
- Propagazione coefficiente: aggiorna `anni.coefficiente_redditivita` per gli anni selezionati. Tutti i calcoli derivati si riallineano automaticamente (sono viste).
- Propagazione anno inizio attività: ricalcola l'aliquota IS (5% vs 15%) per gli anni selezionati e aggiorna `spese_annuali.aliquota` per la voce IS di quegli anni.

### Empty states
- Nessuna fattura → vista `/fatture` con illustrazione e CTA "Nuova fattura" / "Importa XML".
- Nessun cliente → vista `/clienti` con CTA "Nuovo cliente".
- Nessun anno aperto → dashboard come F10, vista `/anni` con CTA "Apri primo anno".
- Anno aperto senza fatture → vista anno con KPI a 0, sezioni vuote pulite.

---

## UX e feedback

### Conferme e dialog
- Tutte le azioni distruttive (delete, reversibilità di stato) richiedono dialog di conferma con descrizione esplicita di cosa verrà modificato.
- Conferma testuale ("digita CONFERMA") solo per cancellazione di un Anno se la dipendenza è stata risolta (caso che probabilmente non si verifica mai con i vincoli RB14, ma utile da prevedere).

### Toast
- Dopo creazione / modifica / cancellazione: toast non bloccante "Fattura creata", "Cliente archiviato", ecc.
- Errori 500 / network: toast distruttivo con messaggio leggibile + invito a riprovare.
- Successo import multiplo: toast con riepilogo numerico ("12 fatture importate, 3 clienti creati").

### Loading states
- Submit form: pulsante in stato `disabled` + spinner inline.
- Tabelle: skeleton rows durante il caricamento (con Inertia, deferred props se serve per la dashboard).

### Errori inline
- Validazione campi: errore in rosso sotto il campo (pattern shadcn-vue esistente).
- Errori di servizio (es. matching XML): warning inline sulla riga interessata, non blocca il batch.

### Responsive
- **Desktop** (≥ 1024px): viste primarie a colonne dense; vista anno con tabella mensile orizzontale; vista scadenze con tabella multi-colonna.
- **Tablet** (≥ 768px): tabelle collassano alcune colonne secondarie; sidebar nascondibile.
- **Mobile** (< 768px): card stack per le liste; vista anno con tabs ("Spese", "Mensile", "Fatturato"); form a tutto schermo per inserimento veloce; vista scadenze ottimizzata per "tap → registra pagamento" (CTA grande).
- **Strategy**: priorità desktop per le viste di consultazione (dashboard, vista anno, vista scadenze, vista anni); priorità mobile per i flussi di inserimento dati (nuova fattura manuale, registra pagamento da scadenza).

### Trasparenza calcoli
- Sul dettaglio Spesa annuale: blocco "Formula" che mostra l'espressione applicata in chiaro (es. "Imposta sostitutiva = (€42.500 × 78% − €4.200 contributi pagati) × 5%").
- Sul credito IS sovrascritto manualmente: chip "valore manuale" con tooltip che mostra il valore calcolato.

### Sidebar / Navigation
Estendere `AppSidebar.vue` con le sezioni di dominio:
- Dashboard
- Anno corrente (link diretto a /anni/{anno_corrente})
- Fatture
- Clienti
- Scadenze
- Pagamenti
- Anni (vista pluriennale)
- Impostazioni → Profilo professionale, Voci di spesa, Scadenze tipo

---

## Fasi di implementazione

La feature è troppo grande per una singola sessione. Divisione in fasi sequenziali, ciascuna testabile.

### Fase 1: Fondazioni — schema, onboarding, anagrafiche template

**Scope**:
- Migrazioni per tutte le tabelle dominio (`professional_profiles`, `clienti`, `fatture`, `voci_spesa`, `anni`, `spese_annuali`, `scadenze_tipo`, `scadenze`, `pagamenti`).
- Modelli Eloquent con relazioni, soft delete, trait `BelongsToUser` (global scope tenancy).
- Middleware `EnsureOnboarded`.
- Pagina `/onboarding` (Inertia) + controller + action di completamento.
- Seeder per voci di spesa standard e scadenze tipo standard (eseguito alla creazione del profilo, non globalmente).
- Pagine di impostazioni: Profilo professionale (estensione settings), Anagrafica voci di spesa (CRUD), Anagrafica scadenze tipo (CRUD).
- Sidebar estesa con le voci di dominio (anche se le pagine target sono ancora "coming soon").

**File coinvolti**:
- `database/migrations/*` (9 nuove migrazioni)
- `app/Models/{ProfessionalProfile,Cliente,Fattura,VoceSpesa,Anno,SpesaAnnuale,ScadenzaTipo,Scadenza,Pagamento}.php`
- `app/Concerns/BelongsToUser.php` (trait)
- `app/Http/Middleware/EnsureOnboarded.php`
- `app/Http/Controllers/OnboardingController.php`
- `app/Actions/Studiofinance/CompleteOnboarding.php`
- `app/Http/Controllers/Settings/VociSpesaController.php`
- `app/Http/Controllers/Settings/ScadenzeTipoController.php`
- `database/seeders/StudiofinanceTemplatesSeeder.php` (richiamato da `CompleteOnboarding`, non da `DatabaseSeeder` globale)
- `resources/js/pages/Onboarding.vue`
- `resources/js/pages/settings/Profile.vue` (estendere)
- `resources/js/pages/settings/VociSpesa/*` (Index, Create, Edit)
- `resources/js/pages/settings/ScadenzeTipo/*` (Index, Create, Edit)
- `resources/js/components/AppSidebar.vue` (estendere)
- `routes/web.php` (registrare gruppi: onboarding, settings dominio)
- Tests: `tests/Feature/OnboardingTest.php`, `tests/Feature/Settings/VociSpesaTest.php`, `tests/Feature/Settings/ScadenzeTipoTest.php`, `tests/Feature/TenancyTest.php`

### Fase 2: Clienti e Fatture (manuali + import XML)

**Scope**:
- CRUD clienti (lista, dettaglio, form). Validazione P.IVA/CF (almeno uno).
- CRUD fatture: form manuale con calcolo totale in tempo reale, validazione unicità numero per anno.
- Import XML multiplo: upload, parser, anteprima editabile per file, matching cliente con P.IVA/CF, sezione "nuovo cliente da XML", switch a cliente esistente, conferma transazionale.
- Vista dettaglio fattura con link XML originale.
- Vista dettaglio cliente con storico fatturato pluriennale (computed in PHP, no caching).
- Vincoli di cancellazione (cliente con fatture → blocco).

**File coinvolti**:
- `app/Http/Controllers/ClienteController.php`
- `app/Http/Controllers/FatturaController.php`
- `app/Http/Controllers/ImportXmlController.php`
- `app/Services/FatturaXmlParser.php` (parsing FatturaPA XML)
- `app/Actions/Studiofinance/ImportFatture.php`
- `resources/js/pages/clienti/*` (Index, Create, Edit, Show)
- `resources/js/pages/fatture/*` (Index, Create, Edit, Show)
- `resources/js/pages/fatture/Import.vue` (upload + anteprima multipla)
- `resources/js/components/fattura/ClientePicker.vue` (autocomplete + crea inline)
- Tests: `tests/Feature/ClienteTest.php`, `tests/Feature/FatturaTest.php`, `tests/Feature/ImportXmlTest.php`

**Dipende da**: Fase 1 (modelli e schema).

### Fase 3: Anni, spese annuali, scadenze e pagamenti

**Scope**:
- Wizard apertura anno (3 step + cross-year check).
- Gestione anno "pre-aperto" (auto-creazione + riuso al wizard formale).
- CRUD spese annuali (incluse una tantum, override `importo_effettivo`, `credito_anno_precedente`).
- Aggiunta voce template ad anni esistenti (con checkbox per anno).
- Ciclo di vita scadenze: registrazione pagamento, "non dovuta", reversibilità con conferma.
- Pagamenti manuali extra-scadenza (form + autocomplete spese).
- Vista `/scadenze` cronologica con filtri (stato, tipo, anno).
- Vista `/pagamenti` con filtri.
- Vista `/anni` (lista) con CTA "Apri nuovo anno".
- Vincoli di cancellazione (RB14) su anni, spese annuali, pagamenti.

**File coinvolti**:
- `app/Http/Controllers/AnnoController.php`
- `app/Http/Controllers/SpesaAnnualeController.php`
- `app/Http/Controllers/ScadenzaController.php`
- `app/Http/Controllers/PagamentoController.php`
- `app/Actions/Studiofinance/ApriAnno.php` (transazione: anno + spese + scadenze + pagamenti pianificati + cross-year)
- `app/Actions/Studiofinance/RegistraPagamento.php`
- `app/Actions/Studiofinance/MarcaScadenzaNonDovuta.php`
- `app/Actions/Studiofinance/AnnullaCompletamentoScadenza.php` (e analoghi)
- `app/Services/AperturaAnnoChecker.php` (cross-year, anni pre-aperti)
- `resources/js/pages/anni/Index.vue`
- `resources/js/pages/anni/ApriWizard.vue` (3 step)
- `resources/js/pages/scadenze/Index.vue`
- `resources/js/pages/scadenze/Show.vue` (side-sheet)
- `resources/js/pages/pagamenti/Index.vue`
- `resources/js/pages/pagamenti/Create.vue`
- `resources/js/components/scadenza/RegistraPagamentoDialog.vue`
- Tests: `tests/Feature/ApriAnnoTest.php` (incluso cross-year), `tests/Feature/ScadenzaCicloVitaTest.php`, `tests/Feature/PagamentoTest.php`

**Dipende da**: Fase 1 (schema). Indipendente da Fase 2 (puoi testare apertura anno senza fatture, anche se i calcoli IS daranno 0 — utile come test).

### Fase 4: Calcoli derivati e viste principali

**Scope**:
- Servizi di calcolo per tutti i derivati (RB5, RB11, viste annuali e mensili).
- Vista `/` Dashboard mese in corso (KPI, scadenze imminenti, ultime fatture, ultimi pagamenti, scorciatoia anno) + empty state se anno non aperto.
- Vista `/anni/{YYYY}` con tutti gli aggregati: fatturato, ripartizione clienti, vista mensile 12 righe, spese annuali tabella, contributi pagati, reddito IRPEF, reddito netto effettivo.
- Vista `/anni` tabella pluriennale con KPI principali.
- Dettagli con blocco "Formula" per Spesa annuale.
- Alert credito IS sovrascritto.
- Alert "minimale Inarcassa in arrivo" (Vista anno).
- Wayfinder routes generati per tutti i nuovi controller.

**File coinvolti**:
- `app/Services/CalcoliAnno.php` (orchestratore: redditoIrpef, redditoNettoEffettivo, contributiPagati, IS netta, credito fine anno).
- `app/Services/QuoteMensili.php` (proiezione mensile per spesa).
- `app/Models/Anno.php` (metodi accessor delegano ai services).
- `app/Models/SpesaAnnuale.php` (importoPrevisto, importoDefinitivo, importoPagato, importoDaPagare, importoDaPagareAdOggi, formulaTesto).
- `app/Http/Controllers/DashboardController.php` (aggiornare l'esistente con KPI Studiofinance).
- `resources/js/pages/Dashboard.vue` (riscrivere)
- `resources/js/pages/anni/Show.vue` (vista anno)
- `resources/js/components/anno/TabellaMensile.vue`
- `resources/js/components/anno/RipartizioneClienti.vue`
- `resources/js/components/spesa/FormulaBlock.vue`
- Tests: `tests/Feature/CalcoliAnnoTest.php` (con scenari realistici da brief: forfettario start-up 5%, regular 15%, con/senza credito), `tests/Feature/DashboardTest.php`

**Dipende da**: Fase 2 (fatture) e Fase 3 (anni/spese/pagamenti) — questa fase fa lavorare insieme tutti i pezzi.

### Fase 5: Propagazione profilo, reversibilità completa, archivio

**Scope**:
- Modifica profilo con dialog di propagazione agli anni esistenti (coefficiente + anno inizio attività).
- Reversibilità completa di tutti gli stati con conferma (rifinitura UX dei flussi già implementati in Fase 3).
- Vista archivio: liste di entità soft-deletate con possibilità di ripristino (entità per entità: clienti archiviati, fatture, voci template disattivate, ecc.).
- Test di tenancy stretti (cross-user leak prevention).
- Test end-to-end pluriennali (apri anno N-2, N-1, N, registra pagamenti, verifica propagazione crediti).

**File coinvolti**:
- `app/Actions/Studiofinance/PropagaCoefficiente.php`
- `app/Actions/Studiofinance/PropagaAnnoInizio.php`
- `app/Http/Controllers/Settings/ProfileController.php` (estendere)
- `resources/js/pages/settings/Profile.vue` (estendere con dialog propagazione)
- `app/Http/Controllers/ArchivioController.php`
- `resources/js/pages/archivio/Index.vue`
- `resources/js/pages/archivio/{Clienti,Fatture,Pagamenti,Anni,...}.vue`
- Tests: `tests/Feature/PropagazioneProfiloTest.php`, `tests/Feature/ArchivioTest.php`, `tests/Feature/TenancyCrossUserTest.php`, `tests/Feature/EndToEndPluriennaleTest.php`

**Dipende da**: tutte le fasi precedenti.

---

## Note di implementazione tecnica

- **Tenancy**: implementare con trait `BelongsToUser` che applica un global scope su tutti i query builder. Eccezione per il modello User stesso. Test esplicito di "altro utente non vede mai i miei dati" in Fase 5.
- **Soft delete + global scope**: combinare `SoftDeletes` e `BelongsToUser`. Le entità soft-deletate restano accessibili per il proprietario via `withTrashed()` (per la vista archivio in Fase 5) ma non per altri utenti.
- **Decimal**: usare cast `'decimal:2'` su tutti i campi monetari per evitare problemi di precisione float. Per i calcoli intermedi (es. quote mensili), accettare `decimal` PHP nativo (PHP 8.4 ne gestisce bene la precisione per importi al centesimo).
- **Parser XML FatturaPA**: usare `SimpleXMLElement` o `DOMDocument` nativi. Non aggiungere dipendenze a meno di scoprire complessità non gestibili. Namespace `http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2`.
- **Date e timezone**: tutto in `Europe/Rome` (già default in `config/app.php`). Le date "anno" sono solo `year`, le date emissione/effettiva sono `date` (no tempo).
- **Validazione P.IVA/CF**: pattern regex base per formato; nessuna validazione di checksum nella prima versione (può essere aggiunta come Rule custom riusabile).
- **Inertia v3**: usare `Inertia::optional()` per i dati pesanti (es. ripartizione clienti in vista anno) e `Inertia::defer()` con skeleton per la dashboard.
- **Wayfinder**: dopo ogni nuovo controller/route, rigenerare con `php artisan wayfinder:generate`. Usare i tipi generati per le chiamate da Vue (no URL hardcoded).
- **Test**: `RefreshDatabase` su SQLite in memoria, factories per ogni modello con stati realistici (es. `FatturaFactory::stateConRitenuta()`).
