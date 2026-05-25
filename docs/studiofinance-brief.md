# Studiofinance — Brief funzionale

## 1. A cosa serve

Studiofinance è un sistema di rendicontazione fiscale e finanziaria per un professionista in regime forfettario. Non emette fatture (l'emissione avviene su un sistema esterno di fatturazione elettronica, oggi Flextax) e non sostituisce il commercialista. Risponde a tre famiglie di domande:

- **Quanto tasse e contributi devo pagare per quest'anno?**
- **Quanto devo mettere da parte ogni mese per essere coperto sulle scadenze?**
- **Quanto ho effettivamente pagato e quanto resta da pagare?**

E, a corredo, fornisce visibilità su fatturato, redditi (reale e fiscale), concentrazione clienti e scadenze.

Il sistema parte dalle fatture emesse e dai pagamenti effettuati come dati di input, e calcola tutto il resto come viste derivate. La precisione e la coerenza dei calcoli sono prioritari rispetto a qualsiasi ottimizzazione di performance, dato il volume modesto di dati gestiti (decine di righe per anno).

Il sistema è progettato per supportare più professionisti come istanze indipendenti (multi-utente): ogni utente ha i propri dati separati. Le viste sono pensate per essere fruibili sia da desktop sia da mobile.

---

## 2. Onboarding e impostazioni utente

Alla prima apertura, il sistema chiede tre informazioni di base:

- **Nome del professionista**
- **Coefficiente di redditività** (default 78% per architetto in regime forfettario; modificabile se necessario)
- **Anno di inizio attività** (rilevante per il calcolo automatico dell'aliquota imposta sostitutiva: 5% per i primi 5 anni, 15% successivamente)

Da queste tre informazioni il sistema pre-popola:

- Il **profilo utente**
- L'**anagrafica delle voci di spesa standard** (imposta sostitutiva, contributi Inarcassa Soggettivo / Integrativo / Maternità, bolli, commercialista, assicurazione, OATO)
- L'**anagrafica delle scadenze tipo standard** (rate e saldi fiscali, scadenze contributive, dichiarazioni)

Il sistema **non crea automaticamente** alcun anno: la creazione degli anni è un'azione manuale dell'utente, che decide quando e come popolare gli esercizi (anche pregressi).

---

## 3. Entità del sistema

### 3.1 Fatture

La fattura è l'unità base del sistema. Rappresenta una prestazione fatturata a un cliente ed è il dato di partenza da cui derivano tutti i calcoli fiscali e previdenziali.

Ogni fattura contiene: numero, data di emissione, cliente intestatario, imponibile, cassa previdenziale Inarcassa (rivalsa 4% dell'imponibile), bollo, spese anticipate fuori campo IVA (art. 15), totale fattura, ed eventuale ritenuta bancaria 8% applicata quando il cliente paga tramite cessione del credito da bonus edilizi.

Il bollo è un campo manuale della fattura (valore tipicamente €2, ma il sistema non applica automaticamente regole di soglia: registra quanto inserito dall'utente o quanto presente nell'XML).

Quando una fattura è flaggata come soggetta a ritenuta bancaria, il sistema calcola in automatico l'importo trattenuto dalla banca (8% del totale fattura). Questo valore viene poi scalato dall'imposta sostitutiva dovuta a fine anno, per evitare doppia tassazione.

Le spese anticipate fuori campo IVA rappresentano rimborsi al professionista per importi anticipati per conto del cliente: sono escluse sia dalla base imponibile fiscale sia dalla base contributiva.

Le fatture possono essere inserite in due modi:

- **Import da XML** della fattura elettronica, con schermata di anteprima per verificare e correggere i dati prima della conferma. Il matching con il cliente in anagrafica avviene automaticamente sulla base di P.IVA o Codice Fiscale.
- **Inserimento manuale**, per casi residuali o storici.

La data di emissione è la data che conta ai fini dei calcoli, dato che il pagamento è sempre contestuale all'emissione.

### 3.2 Clienti

Entità anagrafica minima per identificare l'intestatario delle fatture. Nel sistema studiofinance il cliente non viene gestito in senso commerciale (contatti, indirizzi, comunicazioni) perché l'emissione delle fatture avviene altrove; serve solo a rendere possibili associazioni stabili tra fatture e controparte, per il calcolo del fatturato per cliente e l'auto-riconoscimento in fase di import.

Ogni cliente contiene: denominazione, identificativo fiscale (P.IVA per soggetti con partita IVA, Codice Fiscale per i privati — usato per il matching automatico all'import XML), flag "soggetto a ritenuta bancaria 8%" (precompila il corrispondente flag sulle nuove fatture, sempre sovrascrivibile), note libere.

Dal cliente sono derivate, come viste, le metriche di concentrazione e storicizzazione del fatturato: numero di fatture, totale fatturato per anno, ticket medio, ultima fattura, percentuale sul fatturato annuale.

### 3.3 Voci di spesa (template)

Sono il catalogo delle spese che il professionista sostiene ricorrentemente. Non sono legate a un anno specifico: definiscono cosa esiste e come si calcola.

Per ogni voce sono specificati:

- **Nome**
- **Tipo di calcolo**: percentuale sul reddito IRPEF, percentuale sul volume d'affari IVA, importo fisso annuale, somma derivata dai bolli sulle fatture
- **Aliquota o quota di default**
- **Minimale e massimale di default** (per le voci percentuali con soglie)
- **Stato attivo / non attivo**

Il sistema parte con un set predefinito di voci standard per il regime forfettario, tutte modificabili dall'utente:

- Imposta sostitutiva
- Inarcassa Contributo Soggettivo
- Inarcassa Contributo Integrativo
- Inarcassa Contributo Maternità
- Bolli
- Commercialista
- Assicurazione professionale
- OATO (contributo all'Ordine)

### 3.4 Spese annuali (istanze)

Sono la materializzazione di ogni voce di spesa per un singolo anno. Alla creazione di un nuovo anno, vengono generate in blocco tutte le istanze delle voci attive al momento, ereditandone aliquota, minimale, massimale e quota. Da quel momento ogni istanza vive indipendentemente: modificare il template non altera gli anni già aperti.

Sull'istanza vivono come dati di input solo:

- La **voce di spesa di riferimento** (link al template, oppure NULL se è una spesa una tantum)
- L'**anno**
- L'**aliquota / minimale / massimale / quota** di quell'anno (modificabili rispetto al default ereditato)
- L'**importo effettivo** (workaround eccezionale, vedi sotto)
- Eventuali **note**

Tutti gli altri valori sono derivati al volo:

- L'**importo previsto**, calcolato automaticamente dal sistema dove possibile (per le voci percentuali, per i bolli, per Maternità) o inserito dall'utente come stima a inizio anno (per i costi fissi)
- L'**importo definitivo**, che è l'effettivo se presente, altrimenti il previsto: è il valore usato per tutti i calcoli a valle
- L'**importo pagato**, derivato dalla somma dei pagamenti collegati in stato `pagato`
- Il **da pagare**, differenza tra definitivo e pagato
- Il **da pagare ad oggi**, che per le spese a quota fissa proporziona l'importo sui mesi trascorsi (definitivo / 12 × mese corrente); per le voci percentuali coincide col definitivo, dato che la base di calcolo è già "ad oggi" per costruzione. Per gli anni passati, il "da pagare ad oggi" coincide con l'intero importo definitivo.

#### Importo effettivo (workaround eccezionale)

Il sistema è progettato in modo che l'importo previsto sia il più accurato possibile e coincida normalmente con quello effettivamente dovuto. Il campo "importo effettivo" esiste come workaround di sicurezza per i casi in cui il valore reale comunicato da fonti esterne (F24, bollettini Inarcassa) differisca dal calcolo del sistema per motivi non riconducibili a dati mancanti (arrotondamenti, dettagli normativi non modellati, errori di terzi). In questi casi l'utente può sovrascrivere il previsto inserendo l'effettivo, che prenderà il sopravvento nei calcoli a valle.

L'uso di questo campo dovrebbe essere raro: se l'effettivo è sistematicamente diverso dal previsto, significa che manca un input nel sistema e va corretto il modello, non il singolo dato.

#### Apertura di un nuovo anno

L'apertura di un nuovo anno è sempre un'azione manuale dell'utente: il sistema non crea automaticamente l'anno successivo. Questo permette di:

- Rivedere e aggiornare voci e aliquote prima di propagarle (es. delibere Inarcassa pubblicate annualmente)
- Generare anche anni **pregressi**, settando per ognuno aliquote, quote e altri parametri specifici di quell'esercizio

#### Gestione delle eccezioni

- **Nuove voci aggiunte in corso d'anno**: quando l'utente crea una nuova voce nel template mentre un anno è già aperto, il sistema chiede esplicitamente se attivarla anche sull'anno corrente o solo dal successivo. Sugli anni già chiusi una voce nuova non viene mai aggiunta retroattivamente.
- **Spese annuali una tantum**: una spesa annuale può esistere anche senza un template di riferimento, per coprire costi eccezionali. In questo caso il tipo di calcolo, l'aliquota o la quota vengono definiti direttamente sull'istanza.

### 3.5 Scadenze tipo (template)

Sono il catalogo delle scadenze ricorrenti. Per ognuna:

- **Nome**
- **Giorno e mese di scadenza**
- **Tipo**: Pagamento / Adempimento
- **Voce di spesa collegata** (solo per i Pagamenti)
- **Anno di riferimento della spesa**: corrente / successivo — utile per casi come il commercialista, che si paga a dicembre dell'anno N ma copre servizi dell'anno N+1

Il sistema parte con un set predefinito delle scadenze fiscali e previdenziali standard (rate e saldo imposta sostitutiva, acconti e saldo Inarcassa, dichiarazione redditi, comunicazione reddituale Inarcassa, rate trimestrali bolli, scadenza assicurazione, ecc.), modificabili dall'utente.

### 3.6 Scadenze (istanze)

Sono la materializzazione di ogni scadenza tipo per un singolo anno. Vengono generate in blocco all'apertura dell'anno, con date calcolate correttamente per quell'anno.

La scadenza funziona come **hub di lettura temporale**: dalla vista scadenze l'utente vede tutto ciò che lo aspetta — pagamenti da fare, adempimenti da eseguire — senza dover incrociare entità diverse.

Ogni scadenza contiene: data prevista, tipo (Pagamento/Adempimento), nome, spesa annuale di riferimento (solo se Pagamento), stato (Aperta/Completata/Non dovuta), note.

Per le scadenze di tipo Pagamento, il sistema crea automaticamente anche il **Pagamento collegato** in stato `pianificato`: questa coppia scadenza-pagamento è linkata uno a uno e si muove sincronizzata.

#### Ciclo di vita delle scadenze di Pagamento

- Generate `aperte` all'apertura dell'anno, con un pagamento collegato in stato `pianificato` (data prevista impostata, importo e data effettiva vuoti)
- Quando l'utente registra il pagamento dalla vista scadenze: compila importo e data effettiva → la scadenza passa a `completata`, il pagamento passa a `pagato`
- Se l'utente sa che non dovrà pagare (deroga, credito sufficiente): marca la scadenza come `non dovuta` → il pagamento collegato passa a stato `non dovuto` e non concorre ai totali

#### Ciclo di vita delle scadenze di Adempimento

- Generate `aperte` all'apertura dell'anno
- L'utente le marca manualmente come `completate` quando l'adempimento è eseguito
- Non hanno pagamenti collegati

#### Scadenze fuori template

È sempre possibile creare scadenze manuali per eventi una tantum (sanzioni in arrivo, comunicazioni straordinarie, ecc.). Le scadenze di Pagamento create manualmente generano comunque un pagamento `pianificato` collegato.

### 3.7 Pagamenti

Il pagamento rappresenta un'uscita (potenziale o reale) legata a una spesa annuale. Diversamente da un'entità di mera registrazione contabile, il pagamento nel sistema studiofinance esiste anche **prima** di essere effettuato, come placeholder generato dalle scadenze di tipo Pagamento.

Ogni pagamento contiene: descrizione, spesa annuale di riferimento, scadenza collegata (può essere NULL per pagamenti manuali extra-scadenza), importo, data effettiva del versamento, stato.

#### Stati del pagamento

- **Pianificato**: generato all'apertura dell'anno da una scadenza tipo. Importo vuoto, data effettiva vuota. Non concorre ai totali "pagato" della spesa annuale; serve come promemoria.
- **Pagato**: l'utente ha registrato l'importo effettivo e la data del versamento. Concorre ai totali e ai calcoli a valle.
- **Non dovuto**: l'utente ha esplicitato che il pagamento non sarà effettuato. Resta tracciato per memoria storica.

#### Sempre un pagamento per spesa

Ogni pagamento è collegato a **una sola** spesa annuale. Pagamenti che nella realtà coprono più voci insieme (es. F24 con imposta sostitutiva + addizionali, avviso Inarcassa con i tre contributi) si inseriscono come pagamenti distinti, ciascuno con il proprio importo netto. Questa scelta mantiene le quote nette su ogni spesa e semplifica la riconciliazione con gli avvisi ufficiali.

#### Due "anni" rilevanti

Un pagamento ha due "anni" che possono essere diversi e che servono a fini distinti:

- L'**anno della spesa annuale di riferimento** è l'anno fiscale/contributivo cui il pagamento è imputato. Una spesa annuale 2023 si considera completamente saldata quando la somma dei pagamenti `pagato` collegati raggiunge il suo importo definitivo.
- L'**anno della data effettiva di pagamento** è l'anno in cui l'uscita avviene di fatto. Serve per la quadratura di cassa e per il calcolo del reddito IRPEF (i contributi previdenziali pagati nell'anno X sono deducibili dal reddito X, indipendentemente dall'anno cui si riferiscono).

### 3.8 Anni

L'anno è la dimensione di aggregazione principale del sistema. Quasi tutti i dati che caratterizzano un anno sono derivati dalle entità sottostanti — fatture, spese annuali, pagamenti — e non vengono salvati come campi a sé.

Restano salvati sull'entità Anno solo:

- L'**anno** stesso, come identificativo
- Il **coefficiente di redditività** applicato in quell'anno (default ereditato dal profilo utente, modificabile sull'istanza)
- Eventuali **note** libere

Non esiste un concetto di "chiusura" dell'anno né come stato esplicito né come stato derivato: i pagamenti per anni passati sono sempre ammessi, le fatture dell'anno sono delimitate naturalmente dal calendario, e tutti gli aggregati restano coerenti per costruzione.

---

## 4. Calcoli fiscali

### 4.1 Imposta sostitutiva

L'imposta sostitutiva è il tributo principale del regime forfettario, sostitutiva di IRPEF, addizionali e IRAP.

#### Formula di calcolo

```
Imposta sostitutiva = (Imponibile fatturato × coefficiente di redditività − Contributi previdenziali pagati nell'anno) × aliquota
```

Dove:

- **Imponibile fatturato** è la somma degli imponibili di tutte le fatture dell'anno (esclusi bolli, cassa, spese anticipate fuori campo IVA, ritenute)
- **Coefficiente di redditività** dipende dal codice ATECO ed è 78% per architetti. Vive sul profilo utente, replicato sull'anno
- **Contributi previdenziali pagati nell'anno** è la somma dei pagamenti la cui data effettiva ricade nell'anno solare e che sono collegati a spese annuali di tipo "contributo previdenziale" (i tre contributi Inarcassa)
- **Aliquota** è 5% per i primi 5 anni di attività dal regime start-up, 15% successivamente. Calcolata automaticamente dal sistema in base all'anno di inizio attività configurato nel profilo, ma modificabile sulla singola spesa annuale per gestire casi particolari

#### Calcoli derivati attorno all'imposta sostitutiva

```
Imposta netta da versare = Imposta lorda − Ritenute applicate dai clienti − Credito da anno precedente

Da pagare ancora = Imposta netta − Pagamenti già effettuati (acconti + saldo)
```

- **Ritenute applicate dai clienti**: alcuni clienti (banche per i bonus edilizi, consorzi, altri) trattengono ritenute alla fonte. Il sistema le somma automaticamente per anno (dalle fatture flaggate "soggette a ritenuta")
- **Credito da anno precedente**: campo inseribile dall'utente sulla spesa annuale; il sistema propone il valore calcolato dall'anno precedente come default e segnala con un avviso le modifiche manuali

Se i pagamenti superano l'imposta netta, si genera un credito a fine anno che si trasferisce all'anno successivo.

#### Quote mensili durante l'anno

```
Quota mensile = imponibile del mese × coefficiente di redditività × aliquota
```

**Ignorando i contributi previdenziali** pagati nell'anno. Durante l'anno il professionista non conosce con certezza il totale dei contributi che verseranno entro il 31/12.

La somma delle quote mensili è quindi leggermente **superiore** all'imposta sostitutiva effettivamente dovuta a fine anno. È una sovrastima volontaria e prudente.

#### Acconti e saldo

- 1° acconto entro fine giugno
- 2° acconto entro fine novembre
- Saldo entro fine giugno dell'anno successivo

Gli importi sono inseriti dall'utente dall'F24, le scadenze sono precaricate dal sistema.

### 4.2 Contributi Inarcassa

I tre contributi Inarcassa concorrono come deduzione dal reddito IRPEF: i contributi versati nell'anno solare riducono la base imponibile dell'imposta sostitutiva dello stesso anno.

#### Contributo Soggettivo

Contributo previdenziale principale. Si calcola come percentuale sul reddito IRPEF (lo stesso valore usato per l'imposta sostitutiva), con un minimale annuale obbligatorio e un massimale di reddito oltre il quale non si applica.

Aliquote, minimali e massimale sono editabili per anno sull'istanza, in modo da gestire le delibere annuali e i casi particolari (riduzione giovani per i primi 5 anni → aliquota e minimale dimezzati; deroga al minimo → minimale a 0).

```
Previsto annuale = MAX(MIN(Reddito IRPEF, Massimale) × Aliquota, Minimale)
```

#### Contributo Integrativo

Contributo che il professionista addebita in fattura come rivalsa al cliente (la "cassa Inarcassa" del 4%) e poi versa a Inarcassa. Si calcola come percentuale sul volume d'affari IVA (imponibile + bolli delle fatture dell'anno), con minimale e massimale.

Nota: se il calcolo percentuale è inferiore al minimale, la differenza resta a carico del professionista — il cliente ha pagato la rivalsa del 4%, ma il professionista deve versare il minimo che è di più. Questa differenza è informativa nel sistema.

```
Previsto annuale = MAX(MIN(Volume d'affari IVA, Massimale) × Aliquota, Minimale)
```

#### Contributo Maternità/Paternità

Importo forfettario annuale uguale per tutti gli iscritti, aggiornato annualmente da Inarcassa.

```
Previsto annuale = importo fisso configurato per anno
```

#### Quote mensili durante l'anno

Le quote mensili dei tre contributi sono calcolate come percentuale sulla base maturata nel mese (reddito IRPEF mensile per il Soggettivo, volume d'affari mensile per l'Integrativo, 1/12 della quota annuale per la Maternità), **ignorando i minimali annuali**. Il calcolo mese per mese applicando i minimali sovrastimerebbe sistematicamente l'accantonamento per la maggior parte degli anni.

Il sistema può segnalare con un alert i casi in cui il reddito cumulato, in prossimità della chiusura dell'anno, suggerisce che il minimale entrerà effettivamente in gioco.

#### Scadenze e pagamenti Inarcassa

- **1° acconto** entro giugno dell'anno di competenza
- **2° acconto** entro settembre dello stesso anno
- **Saldo (conguaglio)** entro dicembre dell'anno successivo

Ogni pagamento è collegato a una sola spesa annuale: quando l'utente riceve l'avviso bimestrale Inarcassa con il dettaglio dei tre contributi, inserisce tre pagamenti distinti (Soggettivo, Integrativo, Maternità) con i rispettivi importi.

### 4.3 Bolli

L'imposta di bollo è un campo manuale della fattura (tipicamente €2). Il sistema non applica automaticamente regole di soglia: registra quanto inserito dall'utente o presente nell'XML.

#### Aggregazione e calcolo

La spesa annuale "Bolli" è una voce particolare: il suo importo non deriva da un'aliquota o da una quota fissa, ma è la **somma dei bolli applicati alle fatture dell'anno**. È quindi sempre interamente derivata dai dati di fatturazione, mai inserita.

```
Bolli annuali = SOMMA(bolli sulle fatture con data nell'anno)
```

Per natura, il bollo accumulato è già perfettamente proporzionato al fatturato emesso.

#### Versamenti trimestrali

I bolli si versano in **4 rate trimestrali** durante l'anno, secondo il calendario fiscale standard:

- I trimestre (gennaio-marzo) → versamento entro 31 maggio
- II trimestre (aprile-giugno) → versamento entro 30 settembre
- III trimestre (luglio-settembre) → versamento entro 30 novembre
- IV trimestre (ottobre-dicembre) → versamento entro 28 febbraio dell'anno successivo

Il sistema genera all'apertura dell'anno 4 scadenze di tipo Pagamento, ciascuna con il proprio pagamento pianificato collegato alla spesa annuale "Bolli". I bolli vengono sempre versati per ogni singolo trimestre, senza applicare cumuli.

### 4.4 Costi fissi (Commercialista, Assicurazione, OATO)

Spese a importo fisso annuale, stimate dall'utente a inizio anno e inserite manualmente.

- **Assicurazione professionale**: scadenza 31 marzo
- **OATO** (contributo all'Ordine): scadenza 31 marzo
- **Commercialista**: scadenza 31 dicembre, **con riferimento alla spesa dell'anno successivo** (cioè il pagamento di dicembre 2025 è collegato alla spesa "Commercialista 2026", perché copre i servizi dell'anno 2026)

Per il commercialista, la scadenza tipo è configurata con "anno di riferimento spesa = anno successivo". All'apertura di un anno, viene generata la scadenza di dicembre per quel commercialista, puntata però alla spesa annuale dell'anno N+1 (che deve esistere; se non esiste, viene creata automaticamente come spesa pre-aperta dell'anno successivo).

---

## 5. Viste

Il sistema espone le sue informazioni attraverso un insieme limitato di viste, ognuna calibrata su una domanda specifica del professionista. Le viste sono fruibili sia da desktop sia da mobile.

### 5.1 Dashboard — il mese in corso

È il punto di ingresso del sistema, centrata sul mese in corso. Contiene:

**KPI principali del mese**

- **Fatturato del mese**: somma totale fatture emesse questo mese
- **Spese del mese**: somma delle quote mensili di tutte le spese annuali, attribuite a questo mese
- **Stipendio del mese**: fatturato − spese del mese (netto disponibile al professionista)

**Indicatori a contorno**

- Fatturato cumulato sull'anno con progress bar (es. "siamo a 5/12")
- Confronto col mese stesso dell'anno precedente

**Sezioni sotto i KPI**

- Scadenze imminenti (prossimi 30-60 giorni)
- Ultime fatture (5 più recenti)
- Ultimi pagamenti (5 più recenti)

**Scorciatoia all'anno**: link evidente alla vista anno corrente per il quadro più ampio.

### 5.2 Vista Anno

La pagina dell'anno è il cuore della rendicontazione. Espone tutti gli aggregati annuali calcolati al volo:

- **Aggregati di fatturato**: totale imponibile, totale fatture, numero fatture, ticket medio, ripartizione per cliente
- **Aggregati di spesa**: per ciascuna spesa annuale dell'anno, valori previsto / effettivo / definitivo / pagato / da pagare
- **Vista mensile**: tabella dei 12 mesi con i loro KPI (imponibile, bolli, volume d'affari, totale fatture, totale quote spese, netto del mese)
- **Contributi pagati nell'anno**: somma dei pagamenti la cui data ricade nell'anno e che sono collegati a spese annuali di tipo "contributo previdenziale". Usata come deduzione nel calcolo del reddito IRPEF.
- **Reddito IRPEF (vista fiscale)**: imponibile × coefficiente di redditività − contributi previdenziali pagati nell'anno. È il reddito dichiarato all'Agenzia delle Entrate, utilizzato anche come riferimento per documentazione richiesta da banche e enti terzi.
- **Reddito netto effettivo (vista di cassa)**: totale fatturato dell'anno − tutte le spese definitive di quell'anno. Rappresenta quanto resta effettivamente al professionista.

Le due viste di reddito sono complementari e rispondono a domande diverse — "quanto guadagno davvero" (effettivo) e "quanto guadagno secondo il fisco" (IRPEF).

### 5.3 Vista Scadenze

Una vista cronologica dedicata a tutte le scadenze, sia di pagamento sia di adempimento. Filtrabile per stato (aperte / completate / non dovute), tipo (pagamenti / adempimenti), e anno.

È il punto di partenza per la registrazione dei pagamenti: dalla scadenza aperta, un'azione "registra pagamento" crea il pagamento collegato con i dati precompilati (descrizione, spesa annuale, data attuale), e l'utente inserisce solo l'importo dall'F24 o dall'avviso.

### 5.4 Vista Anni (tabella pluriennale)

Una tabella semplice con tutti gli anni tracciati nel sistema e per ognuno i KPI principali:

- Totale fatturato
- Reddito IRPEF (vista fiscale)
- Reddito netto effettivo (vista di cassa)
- Totale spese pagate
- Totale spese da pagare
- Contributi pagati nell'anno
- Imposta sostitutiva dell'anno

Utile per confronti pluriennali a colpo d'occhio.

### 5.5 Viste di dettaglio

Ogni entità ha una propria pagina di dettaglio:

- **Dettaglio Spesa annuale**: campi, pagamenti collegati, formula di calcolo applicata (per trasparenza: "Imposta sostitutiva = reddito IRPEF × 5%")
- **Dettaglio Fattura**: campi, link all'XML originale se importato, link al cliente
- **Dettaglio Cliente**: anagrafica + storico fatturato pluriennale + lista di tutte le fatture verso di lui
- **Dettaglio Pagamento**: campi, link alla spesa annuale e alla scadenza collegate
- **Dettaglio Scadenza**: campi, link al pagamento collegato (se Pagamento)

### 5.6 Viste catalogo trasversali

Liste complete e filtrabili di:

- Tutte le fatture
- Tutti i pagamenti
- Tutti i clienti
- Tutte le scadenze
- Tutti gli anni

### 5.7 Impostazioni

- **Profilo professionale**: nome, coefficiente di redditività, anno di inizio attività
- **Anagrafica voci di spesa** (template)
- **Anagrafica scadenze tipo** (template)

---

## 6. Quote mensili (nota tecnica)

Le quote mensili delle spese annuali non sono un'entità a sé: sono una proiezione calcolata al volo a partire dalla spesa annuale, dal mese di riferimento e dalla base di calcolo applicabile (reddito IRPEF del mese, volume d'affari del mese, bolli applicati nel mese, oppure semplice divisione per 12 nel caso di importi fissi).

Servono per rispondere alla domanda "quanto devo accantonare questo mese?" e alimentano la vista mensile e gli aggregati di periodo. Non vengono salvate a database: poiché derivano interamente da dati già presenti (fatture, spese annuali, aliquote), salvarle introdurrebbe solo rischio di disallineamento senza alcun vantaggio.

---

## 7. Principi trasversali

### 7.1 Dati di input vs viste derivate

Il sistema adotta una regola architetturale netta: a database vivono solo i dati di input, ossia quelli che un essere umano (o un sistema esterno come l'import XML) ha inserito. Tutto il resto è una vista calcolata al volo a ogni accesso.

**Vivono a DB (dati di input):**

- Profilo utente e impostazioni
- Clienti
- Fatture
- Voci di spesa (template)
- Spese annuali (istanze) — solo i campi di input: aliquota/minimale/massimale/quota, importo effettivo, note
- Scadenze tipo (template)
- Scadenze (istanze) — con stato e link al pagamento
- Pagamenti — con stato pianificato/pagato/non dovuto

**Sono viste calcolate (mai a DB):**

- Tutti gli aggregati mensili
- Tutti gli aggregati annuali sull'entità Anno (ricavi, redditi, pagato, da pagare, contributi pagati nell'anno)
- Tutti i campi derivati delle spese annuali (previsto, definitivo, pagato, da pagare, da pagare ad oggi)
- Le quote mensili delle spese annuali
- I KPI delle viste (dashboard, vista anno, vista cliente)

Questa scelta è motivata dal volume di dati gestiti: per un singolo professionista parliamo di decine di fatture all'anno e poche migliaia di righe complessive sull'intero storico, dimensioni che un database moderno gestisce senza alcuna difficoltà. Materializzare aggregati introdurrebbe solo rischio di disallineamento senza alcun beneficio percepibile in termini di performance.

### 7.2 Arrotondamenti

Il sistema calcola internamente tutti i valori con precisione al centesimo, ma la visualizzazione e l'output verso documenti ufficiali seguono regole diverse a seconda della voce:

- **Imposta sostitutiva e valori del modello Unico**: arrotondati all'unità (in euro, senza decimali), coerentemente con la presentazione del modello ministeriale
- **Contributi Inarcassa**: mantenuti con i centesimi, come da prassi degli avvisi di pagamento Inarcassa
- **Altri valori (fatturato, redditi, spese fisse)**: mantenuti con i centesimi

Le regole di arrotondamento sono quelle commerciali standard (≥0,50 → sopra, <0,50 → sotto).

### 7.3 Multi-utente

Il sistema supporta più professionisti come istanze indipendenti: ogni entità nel modello dati ha un riferimento al professionista che la possiede. I dati di un utente non sono mai visibili o accessibili a un altro utente.

---

## 8. Riepilogo delle entità e relazioni

```
PROFILO UTENTE
  │
  ├─ ANNI
  │   ├─ SPESE ANNUALI ─────► VOCI DI SPESA (template)
  │   │   ├─ PAGAMENTI ─────► SCADENZE (istanze) ◄── SCADENZE TIPO (template)
  │   │   └─ ...
  │   └─ ...
  │
  ├─ CLIENTI
  │   └─ ...
  │
  └─ FATTURE ───────────────► CLIENTI
      └─ ...
```

Le frecce indicano le associazioni principali tra entità. Le fatture sono legate all'anno tramite la data di emissione (non hanno un foreign key esplicito). I pagamenti sono legati alla spesa annuale (foreign key) e opzionalmente alla scadenza che li ha generati (foreign key opzionale).

---

## 9. Cose esplicitamente fuori scope (per ora)

- **Export dei dati** in formati esterni (XLSX, CSV, PDF): non previsto in prima versione
- **Allegati documentali** su fatture, pagamenti, spese: non previsti in prima versione
- **Integrazione diretta** con Flextax o altri software di fatturazione: si lavora solo via import XML
- **Gestione di incassi** dai clienti, solleciti, scadenzario cliente: tutto il pagamento dai clienti è considerato contestuale all'emissione fattura
- **Calcolo di acconti** per imposta sostitutiva (non viene calcolato automaticamente: l'utente lo inserisce dall'F24)
- **Multi-regime**: il sistema è pensato per il regime forfettario; non gestisce regime ordinario o semplificata
- **Multi-cassa previdenziale**: solo Inarcassa, non Gestione Separata INPS o altre casse
