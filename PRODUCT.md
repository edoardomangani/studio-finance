# Product

## Register

product

## Users

Professionisti italiani in regime forfettario, principalmente architetti iscritti a Inarcassa (estendibile ad altre categorie con coefficiente di redditività diverso). L'utente tipo è una persona singola, non un team: gestisce in autonomia la rendicontazione fiscale dello studio, parla con un commercialista ma non vuole dipendere da lui per sapere "quanto deve mettere da parte ogni mese".

Contesto d'uso: sessioni rapide e mirate dal proprio studio, di solito da desktop a fine giornata o nel weekend, con tre obiettivi ricorrenti — registrare i pagamenti appena ricevuto un F24, controllare le scadenze in arrivo, capire a colpo d'occhio quanto accantonare. Inserimento veloce occasionale anche da mobile (es. al volo dopo un bonifico). Volumi piccoli (decine di righe l'anno), quindi non serve velocità di sistema ma chiarezza di lettura.

Il lavoro è denso e numerico ma non quotidiano: il sistema deve essere immediatamente leggibile per chi non ci entra ogni giorno e non perdona ambiguità sui calcoli.

## Product Purpose

Studiofinance risponde a tre domande ricorrenti del professionista: quanto pagherà di tasse e contributi quest'anno, quanto mettere da parte ogni mese per arrivare coperto alle scadenze, quanto ha già pagato e quanto resta. È esplicitamente uno strumento di **lettura e accantonamento**, non un sistema di fatturazione (le fatture le emette altrove) e non sostituisce il commercialista. Il successo si misura sulla fiducia che l'utente ha nei numeri che vede: deve poter aprire la dashboard a fine mese e prendere una decisione di cassa in trenta secondi, senza dover incrociare fonti diverse.

## Brand Personality

**Lucida, calma, esperta.** Voce in seconda persona, asciutta, mai paternalistica. Niente esclamativi, niente metafore finanziarie ("il tuo tesoretto"), niente onboarding finto-amico. I numeri parlano per primi; il testo li accompagna senza coprirli. Quando serve un'etichetta lunga (es. "Contributo Soggettivo Inarcassa") la scriviamo per intero — il pubblico sa cosa significa e l'abbreviazione fa più danno che spazio salvato.

Estetica analoga a uno studio di architettura ben tenuto: superfici sobrie, materiali precisi, niente decorazione. La sensazione che ogni cosa abbia una posizione studiata e nessuna sia lì per riempire.

## Anti-references

- **Gestionali italiani classici** (TeamSystem, Aruba Fattura, FattureinCloud "pieni"). Da rifiutare in modo specifico: tab annidate, KPI a tile con icone tonde colorate, blu navy aziendale + arancione, density confusa, sidebar a 200 voci, banner promo dentro l'app.
- **Banking app blu-su-blu**. Tutto navy + accent dorato/giallo, card grandi a finta-sicurezza, gradient text, hero-metric template.
- **AI-default Linear / Notion copia**. Zinc neutro + accent indaco/violetto, card uguali a griglia, hero metric con freccia su/giù.
- Ogni elemento decorativo che non legge come "informazione". Nessuna illustrazione SVG generica negli empty state, nessun gradient di sfondo, nessuna shadow drammatica.

## Design Principles

1. **I numeri sono il contenuto.** Tutto il resto (label, chrome, decorazione) si sottomette. Tipografia mono per gli importi, tabular figures, mai un numero che sgrana o si schiaccia per far spazio a un'icona.
2. **Densità come rispetto.** Il professionista vuole vedere il mese, l'anno e le scadenze nella stessa schermata senza scrollare. Spacing stretto, righe basse, niente padding "generoso". La compattezza dimostra che ci si fida dell'utente.
3. **Trasparenza dei calcoli.** Ogni valore derivato ha la sua formula leggibile a un tap. Mai "trust the magic": il sistema lavora per un professionista che dovrà giustificare quei numeri al commercialista.
4. **Chiarezza prima della consistency.** Le viste di lettura (dashboard, anno) sono pensate per consultazione su desktop, i flussi di inserimento (registra pagamento, nuova fattura) per il mobile. Lo stesso schermo non deve fare entrambe le cose.
5. **Niente stato decorativo.** Il colore appare solo come segnale (scaduto, pagato, in arrivo, credito). Mai per "vivacizzare". Una pagina senza problemi è una pagina quasi monocromatica.

## Accessibility & Inclusion

- **Standard di riferimento**: WCAG AA come minimo, con asticella alta sui contrasti dove ci sono numeri (4.5:1 anche su testi grandi quando si tratta di importi).
- **Navigazione da tastiera completa e obbligatoria.** Ogni azione del sistema deve essere raggiungibile e attivabile con tastiera, incluse le azioni "secondarie" su scadenza/pagamento (reversibilità, archivia). Tab order coerente con la lettura visiva.
- **Focus state ridisegnato.** I focus ring shadcn di default (doppio ring offset) sono espliciti ma rumorosi. Sostituiti con uno stato di focus che lavora con i bordi esistenti del componente (bordo che diventa accent + outline 1px sottile esterno), così la densità non viene rotta da anelli che "saltano fuori" sulla pagina.
- **Reduced motion** rispettato. Tutte le transizioni hanno fallback istantaneo.
- **Tabular figures** attivi globalmente per i numeri, in modo che le cifre si allineino verticalmente nelle tabelle.
- **Nessuna informazione veicolata dal solo colore.** Stati ("pagato", "scaduto") hanno sempre etichetta testuale o glifo accanto al chip colorato.
