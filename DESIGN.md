---
name: Studiofinance
description: Strumento di lettura e accantonamento fiscale per professionisti italiani in regime forfettario.
colors:
  canvas-tracing: "oklch(98.5% 0.001 286)"
  panel-tracing: "oklch(96.7% 0.002 286)"
  surface-card: "oklch(98.5% 0.001 286)"
  line-tracing: "oklch(91.9% 0.004 286)"
  line-tracing-soft: "oklch(94% 0.003 286)"
  ink-graphite: "oklch(21% 0.006 286)"
  ink-secondary: "oklch(55.2% 0.014 286)"
  ink-placeholder: "oklch(70.4% 0.012 286)"
  petrol-ink-vivid: "oklch(52% 0.105 210)"
  petrol-ink-strong: "oklch(32% 0.06 210)"
  petrol-ink-soft: "oklch(32% 0.06 210 / 10%)"
  petrol-ink-line: "oklch(32% 0.06 210 / 28%)"
  signal-success: "oklch(54% 0.085 175)"
  signal-warning: "oklch(68% 0.115 75)"
  signal-destructive: "oklch(54% 0.165 25)"
typography:
  display:
    fontFamily: "Switzer, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.375rem"
    fontWeight: 500
    lineHeight: 1.2
    letterSpacing: "-0.018em"
  body:
    fontFamily: "Switzer, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 400
    lineHeight: "1.125rem"
    letterSpacing: "0"
    fontFeature: "'ss01', 'ss02', 'cv01', 'cv11'"
  label:
    fontFamily: "Switzer, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.6875rem"
    fontWeight: 500
    lineHeight: "1rem"
    letterSpacing: "0.06em"
  mono:
    fontFamily: "Google Sans Code, ui-monospace, 'SF Mono', Menlo, monospace"
    fontSize: "0.8125rem"
    fontFeature: "'tnum', 'zero', 'ss02'"
rounded:
  sm: "3px"
  md: "4px"
  lg: "6px"
  xl: "8px"
spacing:
  field-h: "36px"
  table-row-h: "36px"
  topbar-h: "48px"
  subbar-h: "44px"
  sidebar-header-h: "56px"
  sidebar-expanded: "208px"
  sidebar-rail: "48px"
  card-padding: "20px"
components:
  button-primary:
    backgroundColor: "{colors.petrol-ink-strong}"
    textColor: "{colors.canvas-tracing}"
    rounded: "{rounded.lg}"
    height: "36px"
    padding: "0 16px"
    typography: "{typography.body}"
  button-outline:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink-graphite}"
    rounded: "{rounded.lg}"
    height: "36px"
    padding: "0 16px"
    typography: "{typography.body}"
  button-secondary:
    backgroundColor: "{colors.petrol-ink-soft}"
    textColor: "{colors.petrol-ink-strong}"
    rounded: "{rounded.lg}"
    height: "36px"
    padding: "0 16px"
    typography: "{typography.body}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.ink-secondary}"
    rounded: "{rounded.lg}"
    height: "36px"
    padding: "0 16px"
    typography: "{typography.body}"
  input:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink-graphite}"
    rounded: "{rounded.lg}"
    height: "36px"
    padding: "0 12px"
    typography: "{typography.body}"
  card:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink-graphite}"
    rounded: "{rounded.xl}"
    padding: "{spacing.card-padding}"
    typography: "{typography.body}"
  table-row:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink-graphite}"
    height: "{spacing.table-row-h}"
    typography: "{typography.body}"
  table-row-hover:
    backgroundColor: "{colors.panel-tracing}"
    textColor: "{colors.ink-graphite}"
    height: "{spacing.table-row-h}"
    typography: "{typography.body}"
  table-row-selected:
    backgroundColor: "oklch(52% 0.105 210 / 2%)"
    textColor: "{colors.ink-graphite}"
    height: "{spacing.table-row-h}"
    typography: "{typography.body}"
  sidebar-item:
    backgroundColor: "transparent"
    textColor: "{colors.ink-graphite}"
    rounded: "{rounded.md}"
    padding: "7px 16px"
    typography: "{typography.body}"
  sidebar-item-active:
    backgroundColor: "transparent"
    textColor: "{colors.ink-graphite}"
    rounded: "{rounded.md}"
    padding: "7px 16px"
    typography: "{typography.body}"
  pill-success:
    backgroundColor: "{colors.signal-success}"
    textColor: "{colors.signal-success}"
    rounded: "{rounded.md}"
    height: "20px"
    padding: "0 8px"
    typography: "{typography.label}"
  pill-warning:
    backgroundColor: "{colors.signal-warning}"
    textColor: "{colors.signal-warning}"
    rounded: "{rounded.md}"
    height: "20px"
    padding: "0 8px"
    typography: "{typography.label}"
  pill-info:
    backgroundColor: "{colors.petrol-ink-soft}"
    textColor: "{colors.petrol-ink-strong}"
    rounded: "{rounded.md}"
    height: "20px"
    padding: "0 8px"
    typography: "{typography.label}"
  pill-neutral:
    backgroundColor: "transparent"
    textColor: "{colors.ink-secondary}"
    rounded: "{rounded.md}"
    height: "20px"
    padding: "0 8px"
    typography: "{typography.label}"
  dialog-content:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink-graphite}"
    rounded: "{rounded.xl}"
    padding: "0"
---

# Design System: Studiofinance

## 1. Overview

**Creative North Star: "Lo Studio del Professionista"**

L'interfaccia si comporta come un tavolo da disegno tecnico ben tenuto. Superfici sobrie, materiali precisi, nulla che riempia. Quando l'utente apre la pagina a fine mese deve avere la sensazione di trovare il proprio quaderno di cassa già aperto sulla riga giusta: nessuna festa, nessun benvenuto, niente che chieda attenzione. I numeri sono il contenuto. Il chrome (label, navigazione, decorazione) si sottomette.

Il sistema sceglie esplicitamente di rifiutare tre estetiche. La prima è il **gestionale italiano classico** (TeamSystem, FattureinCloud "pieno", Aruba Fattura): tab annidate, KPI tile con icone tonde colorate, navy aziendale con accenti arancione, sidebar a 200 voci, banner promo dentro l'app. La seconda è la **banking app blu-su-blu**: navy + dorato, card grandi a finta-sicurezza, gradient text, template hero-metric. La terza è il **clone Linear/Notion AI-default**: zinc neutro + accento indaco-violetto, card identiche a griglia, freccia su/giù sopra ogni KPI. Studiofinance vive accanto a tutti questi rifiuti senza assomigliare a nessuno.

La densità è la forma del rispetto. Il professionista vuole vedere il mese, l'anno e le scadenze nella stessa schermata senza scrollare; spacing stretto, righe basse, niente padding "generoso". La compattezza dimostra che il sistema si fida dell'utente. Il colore appare solo come segnale: una pagina senza problemi è quasi monocromatica.

**Key Characteristics:**
- Densità editoriale (body 13px, table row 36px, sidebar item circa 28px).
- Restrained color: zinc neutro per il 90% della superficie, accent petrol ≤5%.
- Zero ombre su card e bottoni; profondità da bordo e tono.
- Numeri in mono tabulare; tipografia che si schiera al servizio dei dati.
- Niente stato decorativo: il colore arriva solo per dire qualcosa.

## 2. Colors: La Tavolozza Petrol Ink + Neutral Tracing

Sistema **Restrained**. Neutri freddi zinc 286° tengono il 90% della superficie. Un solo accent, petrol 210°, appare in due intensità (vivid per decoro testuale, strong per UI funzionale) e mai oltre il 5% della pagina. I quattro colori di stato sono semaforici, mai decorativi: vivono dentro pill testuali e mai come riempimento di card.

### Primary
- **Petrol Ink Strong** (`oklch(32% 0.06 210)`): Bottone primario, item sidebar attivo (barretta), focus border di input/textarea/select. Petrol scuro e spento, sceglie il segnale alla vivacità. È il colore "del prodotto", quello che firma le decisioni dell'utente.
- **Petrol Ink Vivid** (`oklch(52% 0.105 210)`): Wordmark "FIN", link inline dentro corpo testuale, emphasis tipografica. Più luminoso del primary, mai sui bottoni: lì il vivid inflaziona il segnale.
- **Petrol Ink Soft** (`oklch(32% 0.06 210 / 10%)`): Fill morbido per pill `info` e selezioni intense. Sulle righe tabella selezionate viene usato ulteriormente diluito (2%) per non scurire la riga.

### Neutral
- **Canvas Tracing** (`oklch(98.5% 0.001 286)`): Bg pagina, bg di card, popover. È il colore della carta da lucido: la depth della card arriva dal border, non da uno sfondo diverso.
- **Panel Tracing** (`oklch(96.7% 0.002 286)`): Sidebar bg, muted bg generico, hover row tabella. Un gradino più scuro del canvas per dare separazione panel/content senza ricorrere a shadow.
- **Line Tracing** (`oklch(91.9% 0.004 286)`): Border standard di card, input, table cell.
- **Line Tracing Soft** (`oklch(94% 0.003 286)`): Divider interni, separator tra righe tabella, hairline sotto header.
- **Ink Graphite** (`oklch(21% 0.006 286)`): Testo primario, importi non-mono, titoli sezione.
- **Ink Secondary** (`oklch(55.2% 0.014 286)`): Testo secondario, kicker label muted, meta information.
- **Ink Placeholder** (`oklch(70.4% 0.012 286)`): Placeholder input, valori disabled, ghost states.

### Status (solo come segnale)
- **Signal Success** (`oklch(54% 0.085 175)`): Pill "Pagato", "Completato". Verde-acqua sobrio, bassa saturazione.
- **Signal Warning** (`oklch(68% 0.115 75)`): Pill "In arrivo". Ambra desaturata, mai giallo cantiere.
- **Signal Destructive** (`oklch(54% 0.165 25)`): Pill "Scaduto", bottone destructive. Rosso bruciato cool, non rosso bandiera.

### Named Rules

**The One Voice Rule.** Petrol vive su ≤5% di ogni schermo. La sua rarità è il segnale. Se il petrol comincia a coprire più del 5% di un layout, qualcosa è andato storto: il colore sta vivacizzando invece di significare.

**The Zinc-First Rule.** I neutri non sono tintati verso il petrol. Sono grigi praticamente puri (chroma ≤0.014). Aggiungere chroma ai neutri per "armonizzare" col petrol fa scivolare il sistema verso una mood navy-corporate. La distanza fra hue neutro (286°) e accent (210°) fa parte dell'identità.

**The No-Decorative-Color Rule.** Una pagina senza problemi è una pagina monocromatica. Lo status arriva quando c'è qualcosa da dire (Scaduto, In arrivo, Pagato), mai per "vivacizzare". Vietato usare success/warning/destructive come fill di card o background di sezione.

## 3. Typography

**Display & Body Font:** Switzer (con `ui-sans-serif, system-ui, sans-serif` fallback)
**Mono Font:** Google Sans Code (con `ui-monospace, "SF Mono", Menlo, monospace` fallback)

**Character:** Switzer è una geometrica con stati intermedi morbidi e feature settings editoriali (`ss01`, `ss02`, `cv01`, `cv11` attive sempre). Google Sans Code è una mono con `tnum` e `zero` per allineamento numerico verticale. Le due famiglie convivono come pianta e quote in una tavola tecnica: Switzer scrive le label, Google Sans Code allinea i numeri sotto la virgola.

### Hierarchy
- **Display** (Switzer 500, 22px / 1.2, letter-spacing −0.018em): h1 pagina con `text-wrap: balance`.
- **Headline** (Switzer 500, 18px / 1.4): h2 sezione.
- **Title** (Switzer 500, 14px / 1.4): titolo card, label di gruppo form.
- **Body** (Switzer 400, 13px / 18px): default del sistema. Form input, table cell, sidebar item, breadcrumb, body prose. Line length massima 75ch.
- **Label** (Switzer 500, 11px / 16px, letter-spacing 0.06em, uppercase): kicker `.kicker`, table head, group label sidebar, micro-meta.
- **Mono** (Google Sans Code 400, 13px / 18px, `font-variant-numeric: tabular-nums`): importi tabella, codici fattura (`FT 2026-04`), codici F24, kbd hint.

### Named Rules

**The Switzer x-Height Rule.** Su body è sempre attivo `font-size-adjust: 0.58`. Switzer ha x-height più bassa di Inter/Geist; senza adjust, a parità di font-size il testo appare più piccolo. La regola assicura altezze ottiche allineate e Switzer pesa come dovrebbe.

**The Mono-For-Numbers-Where-They-Align Rule.** Mono entra dove i numeri si leggono in colonna (tabelle, KPI, code-pill) o sono token che imitano codice (`FT 2026-04`, `F24-06-2026`, `⌘S`). In body prose ("hai 12 fatture quest'anno") il numero resta in Switzer con `tabular-nums` per non spezzare la lettura.

**The Uppercase-For-Kicker-Only Rule.** L'uppercase serve a marcare le label di sezione (kicker, breadcrumb del current page, table head, code-pill). Mai per body, mai per CTA, mai per heading.

## 4. Elevation

**Flat by default.** Il sistema rifiuta le ombre come strumento di profondità. La depth è costruita da due cose: il bordo `1px line-tracing` che separa card dal canvas, e il tonal layering del sidebar (panel-tracing un gradino più scuro del canvas-tracing). Una card non sta "sopra" la pagina, sta "dentro" la pagina.

### Shadow Vocabulary

Una sola ombra in tutto il sistema, riservata a situazioni in cui un elemento deve dichiararsi galleggiante perché si trova fuori dal flow strutturale: nessuna in v0.

### Named Rules

**The No-Shadow Rule.** Card, bottoni, popover, dialog, toast: tutti `box-shadow: none`. Se ti viene voglia di aggiungere `shadow-xs` per "staccare" qualcosa, il problema è che quel qualcosa non è ben strutturato.

**The Tonal-Layering Rule.** La separazione panel/content si fa con i toni della scala neutra, non con shadow. Sidebar = `panel-tracing` (zinc-100), canvas = `canvas-tracing` (zinc-50). Card dentro al canvas = canvas-tracing + border. La row hover in tabella usa lo stesso meccanismo: passa a `panel-tracing` per dichiararsi, non aggiunge ombra né elevation.

## 5. Components

### Buttons

Calmi, precisi, non decorativi. I bottoni hanno una sola forma (radius 6px), una sola altezza default (36px), font weight 500 sul testo. La gerarchia variant non passa per la dimensione ma per il colore di fondo.

- **Shape:** rounded medium (6px). Niente pill, niente squared.
- **Primary** (`variant="default"`): bg petrol-ink-strong, text canvas-tracing. È il colore del segnale "azione importante succede qui". Hover: petrol leggermente più scuro.
- **Outline** (`variant="outline"`): bg canvas-tracing, border line-tracing, text ink-graphite. Per annulla, azioni di contesto neutre. Hover: bg petrol-ink-soft, border petrol-ink-line, text petrol-ink-strong (toggle "on" condivide lo stesso look del hover via `aria-pressed`).
- **Secondary** (`variant="secondary"`): bg petrol-ink-soft (10%), text petrol-ink-strong. Per azioni contestuali soft (Apri dettaglio, Riapri). Hover: petrol-ink-soft a 15%.
- **Ghost** (`variant="ghost"`): bg trasparente, text ink-secondary. Solo per icon button in tabella e item menu. Hover: bg panel-tracing.
- **Destructive** (`variant="destructive"`): bg signal-destructive, text canvas-tracing. Solo dentro Dialog di conferma esplicita; mai come azione primaria di pagina.
- **Focus:** border passa a petrol-ink-strong, outline 1px petrol-ink-line all'esterno. **Niente double-ring shadcn**.

### Inputs / Form Fields

Densi e onesti. Una sola altezza (36px), una sola forma (radius 6px), font 13px. Niente icone interne decorative; le icone funzionali (search, currency) usano `InputGroup`.

- **Default:** bg canvas-tracing, border line-tracing, text ink-graphite, placeholder ink-placeholder.
- **Focus:** border petrol-ink-strong, outline 1px petrol-ink-line, niente offset. Il componente "sa di essere selezionato" senza farsi notare.
- **Error:** border signal-destructive, helper text destructive sotto il campo via `aria-invalid`.
- **Numerici:** classe `.numeric` (mono e tabular-nums), text-right, prefisso/suffisso (€, %) come slot interno muted.
- **FormField:** wrapper canonico (label, control, helper, error) basato su shadcn `Field`. Per ogni form usare FormField, mai assemblare a mano label, input e helper text.

### Cards / Containers

Sobrie e ferme. Una sola forma (radius 8px), una sola altezza di parete (border 1px line-tracing), nessuna ombra. Padding 20px (5 della scala). Mai card dentro card.

### Tables

Da quaderno tecnico. Header con kicker uppercase, body riga 36px, divisori orizzontali soft, niente zebra. Hover row sussurra, selected row dichiara.

- **Boxed mode:** Tabella dentro un box border, radius 6px, header `panel-tracing` dentro al box, body con divisori `line-tracing-soft`. Pagination vive **fuori** dal box.
- **Header:** `kicker` (text-2xs uppercase tracking-wider), text ink-secondary, h-9.
- **Body row:** h-9.
- **Hover:** bg `panel-tracing` (zinc-100). Stessa famiglia tonale della sidebar; la riga si dichiara senza chiamare l'accent.
- **Selected:** bg `petrol-ink-vivid` al 2% di opacità. Marker leggerissimo: chi è abituato a "tutto bianco" lo nota; chi non guarda non viene distratto. Il segnale primario di selezione è la checkbox spuntata, non lo sfondo.
- **Numeriche:** classe `.numeric`, text-right.
- **Sticky columns:** opt-in per colonna con prop `sticky="left"|"right"`, `stickyOffset`. Quando ci sono sticky, checkbox iniziale e cella actions finale diventano automaticamente sticky.
- **Zebra:** off. La densità basta; le zebre aggiungono rumore.

### Sidebar Navigation

L'unico chrome personalizzato del sistema. Larghezza 208px expanded, 48px rail. Header h-56 con logo. Footer con avatar utente. Bottone collapse pillola radius 4px a cavallo del bordo destro.

- **Item idle:** text ink-graphite/75, icona Phosphor `weight="regular"`. Nessun background.
- **Item hover:** crossfade icona regular → fill, barretta ghost `ink-secondary/40` a sinistra (`w-[2px] h-5`) fade-in. Nessun background.
- **Item active:** barretta accent `petrol-ink-vivid` a sinistra (`w-[2px] h-5`) sempre visibile, icona `weight="fill"` sempre, text `ink-graphite`, `font-medium`. Nessun background.
- **Group label:** classe `.kicker`. In rail mode il testo diventa `transparent` e una hairline 20px centrata fa da divider tra gruppi.
- **NavUser:** in rail mode l'avatar passa da size-6 a size-5 centrato dentro un button size-8 senza padding.

### Dialog

Riservato a flussi che richiedono focus totale. **Sheet è bandita** (vedi The No-Sheet Rule). 4 size: `mini` (~460px, confirm), `default` (~580px, form rapido), `wide` (~780px, edit complesso), `fullscreen` (viewer/wizard).

- **Header:** title sentence case, description opzionale, separator border-bottom line-tracing-soft. Mai code-pill `M.CRE` o simili: il code-pill è riservato a token user-facing (`FT 2026-04`, `F24-06-2026`).
- **Body:** padding 20px verticale, 24px orizzontale.
- **Footer:** DialogStandardFooter con Cancel ghost a sinistra, primary a destra.
- **Confirm:** wrapper `ConfirmDialog` su Mini Dialog. Per delete, archive, azioni distruttive.
- **Wizard:** `WizardStepper` mini-pill accent nel `#trailing` del header, description dinamica ("Step 1 di 4 · Cliente").

### Pill Statuses

Vocabolario fisso per stati di record. Una sola forma (radius 4px), una sola altezza (20px), 6 modificatori. Mai inventarne di nuovi.

- **`.pill--success`** (Pagato, Completato): fill success-foreground, text success.
- **`.pill--warning`** (In arrivo, Scadenza imminente): fill warning-foreground, text warning.
- **`.pill--danger`** (Scaduto, Errore): fill destructive-soft, text destructive.
- **`.pill--info`** (Pianificato): fill petrol-ink-soft, text petrol-ink-strong.
- **`.pill--neutral`** (Aperto, Bozza): bg transparent, **border 1px dashed** line-tracing, text ink-secondary. Il dashed lo distingue da `.pill--info`.
- **`.pill--muted`** (Non dovuto, Archiviato): fill panel-tracing, text ink-secondary.

### Topbar

Due fasce orizzontali. Top sempre visibile (h-48), subbar opzionale (h-44).

- **Top:** breadcrumb a sinistra (sentence case eccetto l'ultima voce in UPPERCASE per echo dei documenti fiscali), status pill opzionale, `#page-topbar-actions` mount-point Teleport a destra.
- **Subbar:** `v-show="subbar"`, ospita `#page-topbar-search`, `#page-topbar-filters`, `#page-topbar-views`.
- **Breadcrumb:** **niente auto-prepend del nome studio**. Studiofinance è single-tenant: il brand vive nel logo della sidebar, ripeterlo nel breadcrumb è doppio branding.

### Code-Pill (token user-facing)

Mono compatta inline per identificatori che l'utente già riconosce: `FT 2026-04`, `F24-06-2026`, `CF · 78%`. Mai per system-language interna (no `M.CRE`, no `M.EDT`). Se serve un identifier per analytics/debug, usare `data-modal-code` HTML attribute, invisibile.

### Named Rules

**The No-Sheet Rule.** Sheet è bandita. Sheet, Dialog crea un decision-point ambiguo ("questo edit lo apro come Sheet o come Dialog?"). I pannelli laterali vivono come `push inline`: `FilterSidebar` (filtri di lista) e `RightDetailPanel` (dettaglio Show) sono fratelli del `<main>`. Il content principale si comprime; non si nasconde sotto un overlay.

**The Sidebar-Bar-Only Rule.** L'item attivo della sidebar non ha background. La signature visiva è la barretta accent `w-[2px]` a sinistra, l'icona `weight="fill"`, il text medium. Sommare un `bg-accent-soft` raddoppia il segnale e fa scivolare la sidebar verso un look "tab attivo gestionale".

**The Mono-Only-For-Real-Codes Rule.** `.code-pill` è riservato a token che l'utente riconosce e cita (numero fattura, codice F24, codice tributo, coefficiente). Mai per taxonomie interne dei modali (M.CRE/M.EDT/M.DEL): system-language che leak all'utente.

**The Whispering-Selected-Row Rule.** La riga selezionata in tabella usa `petrol-ink-vivid` al 2% di opacità: marker quasi invisibile. La selezione primaria è la checkbox spuntata, non lo sfondo. Sopra il 5% di opacità la riga diventa rumorosa e compete con le altre informazioni della tabella.

## 6. Do's and Don'ts

### Do:
- **Do** usare petrol-ink-strong come unico colore "del prodotto" (focus, item sidebar attivo, button primary, pill info). Mai oltre il 5% della pagina.
- **Do** trattare i numeri come contenuto principale: mono, tabular-nums per importi in tabella e KPI, font-size in scala dedicata, mai sgranare per fare spazio a un'icona.
- **Do** mantenere body 13px e table row h-9 (36px): la densità è il segnale di rispetto verso il professionista.
- **Do** rendere ogni valore derivato espandibile: una formula trasparente vale dieci colori giusti. Il prossimo `FormulaBlock` mostrerà `Imposta sostitutiva = (€42.500 × 78%) × 5% = €1.890`.
- **Do** usare `.kicker` (text-2xs uppercase tracking-wider muted) per qualsiasi label di sezione, table header, group label sidebar.
- **Do** scrivere stato testuale: ogni pill ha un'etichetta ("Pagato", "Scaduto"). Mai veicolare stato col solo colore.
- **Do** usare `panel-tracing` (zinc-100) come bg di hover row in tabella. La sidebar e la tabella condividono la stessa famiglia tonale di "secondo livello".

### Don't:
- **Don't** clonare i **gestionali italiani classici**. Niente tab annidate, niente KPI tile con icone tonde colorate, niente navy + arancione, niente sidebar a 200 voci, niente banner promo dentro l'app.
- **Don't** scivolare nel pattern **banking app blu-su-blu**. Vietato navy + dorato, gradient text, template hero-metric, card grandi a finta-sicurezza.
- **Don't** convergere sul template **Linear/Notion AI-default**. Niente zinc + indigo, niente card identiche a griglia con icona + heading + text ripetute, niente freccia su/giù sopra ogni metrica.
- **Don't** usare `border-left` o `border-right` maggiore di 1px come accento colorato su card, alert, callout. La barretta accent sulla sidebar è inset assoluto (`absolute left-0 w-[2px]`), non border.
- **Don't** usare gradient text (`background-clip: text` su un gradient). Mai. L'enfasi si fa con weight e size.
- **Don't** mettere shadow su card o bottoni. Niente `shadow-xs`, niente `shadow-sm`. La depth viene dal border e dal tonal layering.
- **Don't** scurire la riga selezionata in tabella sopra il 2-3% di opacità: il marker primario è la checkbox, non lo sfondo. Sopra il 5% la riga compete con i dati.
- **Don't** auto-prepend il nome studio nel breadcrumb. Single-tenant. Il brand vive nel logo della sidebar.
- **Don't** esporre system-language come code-pill (`M.CRE`, `M.EDT`, `M.DEL`). Il code-pill è riservato a token user-facing (numero fattura, codice F24).
- **Don't** introdurre il primitivo Sheet. Pannelli laterali = push inline (`FilterSidebar`, `RightDetailPanel`).
- **Don't** mostrare double-ring focus (`outline-2 outline-offset-2`). Il focus vive dentro il bordo del componente (border accent, 1px outline esterno).
- **Don't** usare illustrazioni SVG generiche negli empty state. Empty state = solo testo, CTA.
- **Don't** usare toast verde con check per "Pagato salvato". Il success è chip, testo nella lista, mai festa effimera.
- **Don't** infrangere `prefers-reduced-motion`. Tutte le transizioni hanno fallback istantaneo.
