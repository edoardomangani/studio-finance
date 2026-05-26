# Design

> Seed iniziale. Il codice di Studiofinance è ancora da scrivere: questo documento fissa le decisioni visive prima dell'implementazione, mutuando struttura e densità da proto-studio-os (sidebar-first, h-9, text-13, font Switzer, radius 4px) ma con palette propria e identità distinta. Rigenerare con `/impeccable document` una volta che esiste codice reale.

## Mood e direzione

Lucido, calmo, esperto. Lettura editoriale ma compressa: la pagina è densa come un articolo di Monocle, non come una dashboard di gestionale. Il colore non vivacizza, segnala — un mese senza problemi è quasi monocromatico. La pagina deve poter restare per dieci minuti aperta sulla scrivania senza affaticare e senza chiedere attenzione.

Scene sentence: professionista a fine mese, monitor desktop, stanza con luce calda, sessione di mezz'ora di lettura+registrazione → tema **light per default**, dark profondo disponibile.

## Color strategy

**Restrained.** Neutri tinted slate (240°) per il 90% della superficie; un solo accent in `ink-cobalt` per ≤5% (link, focus, riga selezionata, chip stato "pagato"). I 4 status color (success/warning/danger/info) sono usati solo come segnale, con saturation bassa e mai come riempimento di card.

### Palette — light

```css
@theme {
  /* Neutrali (slate-tinted, 240°) */
  --color-bg:           oklch(99%   0.004 240);   /* canvas */
  --color-surface:      oklch(97%   0.006 240);   /* card, sidebar */
  --color-surface-2:    oklch(95%   0.008 240);   /* hover row, alt zebra */
  --color-border:       oklch(91%   0.010 240);   /* hairline standard */
  --color-border-soft:  oklch(93.5% 0.008 240);   /* divider interni */
  --color-muted:        oklch(56%   0.012 240);   /* label, kicker, placeholder */
  --color-ink:          oklch(22%   0.018 240);   /* testo principale */
  --color-ink-soft:     oklch(38%   0.016 240);   /* testo secondario */

  /* Accent — ink-cobalt (250°, scelto per stare lontano da:
     - navy banking (più scuro, meno saturo)
     - indaco Linear (più viola)
     - sage proto-studio-os (verde)) */
  --color-accent:       oklch(46%   0.135 250);
  --color-accent-hover: oklch(42%   0.135 250);
  --color-accent-soft:  oklch(46%   0.135 250 / 10%);  /* fill chip / row selected */
  --color-accent-line:  oklch(46%   0.135 250 / 28%);  /* border focus */

  /* Status (semantici, mai decorativi) */
  --color-success:      oklch(54%   0.085 175);        /* verde-acqua sobrio */
  --color-success-soft: oklch(54%   0.085 175 / 12%);
  --color-warning:      oklch(68%   0.115 75);         /* ambra desaturata */
  --color-warning-soft: oklch(68%   0.115 75 / 14%);
  --color-danger:       oklch(54%   0.165 25);         /* rosso bruciato, non rosso bandiera */
  --color-danger-soft:  oklch(54%   0.165 25 / 12%);
  --color-info:         var(--color-accent);           /* = accent */
  --color-info-soft:    var(--color-accent-soft);
}
```

### Palette — dark

```css
.dark {
  --color-bg:           oklch(15%   0.012 240);
  --color-surface:      oklch(18%   0.014 240);
  --color-surface-2:    oklch(21%   0.014 240);
  --color-border:       oklch(28%   0.014 240);
  --color-border-soft:  oklch(24%   0.012 240);
  --color-muted:        oklch(60%   0.012 240);
  --color-ink:          oklch(94%   0.008 240);
  --color-ink-soft:     oklch(75%   0.010 240);

  --color-accent:       oklch(70%   0.150 250);   /* più chiaro/saturo in dark per leggibilità */
  --color-accent-hover: oklch(76%   0.150 250);
  --color-accent-soft:  oklch(70%   0.150 250 / 18%);
  --color-accent-line:  oklch(70%   0.150 250 / 40%);

  --color-success:      oklch(72%   0.105 175);
  --color-success-soft: oklch(72%   0.105 175 / 18%);
  --color-warning:      oklch(78%   0.130 75);
  --color-warning-soft: oklch(78%   0.130 75 / 18%);
  --color-danger:       oklch(70%   0.175 25);
  --color-danger-soft:  oklch(70%   0.175 25 / 18%);
}
```

### Mapping shadcn-vue

Il `components.json` di shadcn-vue parla in `background`/`foreground`/`primary`/`muted`/`accent`/`destructive`/`border`/`ring`. Mappiamo i nostri token così:

| shadcn | Studiofinance |
|---|---|
| `background` | `--color-bg` |
| `foreground` | `--color-ink` |
| `card` | `--color-surface` |
| `card-foreground` | `--color-ink` |
| `popover` | `--color-surface` |
| `primary` | `--color-ink` (sì, ink: il bottone "principale" è nero, non blu) |
| `primary-foreground` | `--color-bg` |
| `secondary` | `--color-surface-2` |
| `accent` | `--color-accent-soft` (per hover/selected morbidi) |
| `accent-foreground` | `--color-accent` |
| `muted` | `--color-surface-2` |
| `muted-foreground` | `--color-muted` |
| `destructive` | `--color-danger` |
| `border` | `--color-border` |
| `input` | `--color-border` |
| `ring` | `--color-accent-line` |

> Nota: il bottone "primary" è **ink** (quasi nero), non accent. L'accent compra attenzione solo quando un dato lo merita (link, focus, stato "info"); usarlo come fill di un CTA inflazionerebbe il segnale.

## Typography

### Famiglie

- **Sans**: **Switzer** (la stessa di proto-studio-os). Famiglia geometrica con buoni stati intermedi (medium-leaning), feature settings `ss01, ss02, cv01, cv11` per le varianti più editoriali.
- **Mono**: **Geist Mono** per tutti gli importi, codici fattura, F24, date in tabella.

```css
@theme {
  --font-sans: "Switzer", ui-sans-serif, system-ui, sans-serif;
  --font-mono: "Geist Mono", ui-monospace, "SF Mono", Menlo, monospace;
  --default-font-feature-settings: "ss01", "ss02", "cv01", "cv11";
}

/* Numerical contexts */
.tabular { font-variant-numeric: tabular-nums; }
.numeric { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }
```

### Scala (custom, non Tailwind default)

```css
@theme {
  --text-2xs:  0.6875rem;   /* 11 — kicker, uppercase label */
  --text-xs:   0.75rem;     /* 12 — micro */
  --text-13:   0.8125rem;   /* 13 — body form, table cell, sidebar item (default) */
  --text-sm:   0.875rem;    /* 14 — body lunghi, helper testo */
  --text-base: 0.9375rem;   /* 15 — solo per copy long-form (impostazioni, onboarding) */
  --text-lg:   1.125rem;    /* 18 — section heading */
  --text-xl:   1.375rem;    /* 22 — page heading */
  --text-2xl:  1.75rem;     /* 28 — dashboard KPI principali */
  --text-3xl:  2.25rem;     /* 36 — solo per il numero "stipendio del mese" */
}
```

Body default: 13px. Salti minimo 1.25× tra step (rispetta il law shared). Heading peso `500`, letter-spacing `-0.018em`, `text-wrap: balance`.

### Numeri

I numeri **non** si usano in Switzer dentro tabelle e KPI. Sempre mono + tabular, perché la lettura verticale ("4.523,00 / 3.890,12 / 12.450,30") allineata sotto-virgola batte qualsiasi proporzionalità. Eccezione: in body prose ("hai 12 fatture quest'anno") il numero resta in sans per leggibilità inline.

## Layout & Shell

**Sidebar-first**, identico al pattern proto-studio-os.

- **AppShell**: `SidebarProvider` + `SidebarInset`.
- **Sidebar** larghezza `260px` desktop, collassabile a `56px` (rail con sole icone). Header con logo Studiofinance + search bar `h-8`. Content con gruppi: **Lavoro** (Dashboard, Anno corrente, Fatture, Clienti, Scadenze, Pagamenti, Anni), **Sistema** (Impostazioni). Footer con menu utente.
- **Sub-topbar** alta `48px` (3rem) attaccata al top del contenuto: ospita **breadcrumb** a sinistra e **azioni di pagina** a destra (es. "Nuova fattura", "Importa XML"). Background = `--color-bg` (stesso del contenuto), border-bottom hairline.
- **Page content** sotto: padding `px-6 py-5` desktop, `px-4 py-4` mobile.
- **Breadcrumb**: Studiofinance / Fatture / 2026 / FT 2026-04. Separatore `/` sottile (text-muted), ultima voce non linkata in `--color-ink`. Mai più di 4 livelli.

### Spacing system

Base 4px (Tailwind default). Densità "compatta":

```
xs   4px   (gap dentro chip)
sm   8px   (gap label↔input, padding chip)
md   12px  (gap form fields, table cell horizontal)
lg   16px  (gap section vertical, card padding)
xl   24px  (gap tra sezioni di pagina)
2xl  40px  (gap header→content, raro)
```

Componenti chiave:
- Button / Input / Select: `h-9` (36px), `text-13`, `px-3` / `px-4`
- Table row: `h-9`, cell padding `px-3 py-2`, border-bottom `--color-border-soft`
- Sidebar item: `h-8`, `text-13`, padding `px-3 py-1.5`, gap icona-label `gap-2.5`
- Card: padding `p-5`, `rounded-md`, border `1px --color-border`, **no shadow** (la profondità arriva dal border, non da elevazioni drammatiche)
- Sheet (per registra pagamento, dettaglio scadenza): width `420px` desktop, full mobile, header `h-12` con border-bottom

### Radius

```css
@theme {
  --radius-sm: 3px;
  --radius:    4px;     /* default — input, button, chip */
  --radius-md: 6px;     /* card, sheet, dialog */
  --radius-lg: 8px;     /* solo per empty state container */
  --radius-full: 9999px; /* solo per avatar */
}
```

Niente `rounded-2xl` o `rounded-3xl`. La morbidezza eccessiva legge come "consumer app", non come "studio".

## Components — override e custom

### Componenti shadcn da installare

Da `components.json` (preset "new-york-v4", baseColor "neutral"): Alert, AlertDialog, Avatar, Badge, Breadcrumb, Button, Card, Checkbox, Collapsible, Dialog, DropdownMenu, Input, InputOTP, Label, NavigationMenu, Popover, RadioGroup, Select, Separator, Sheet, Sidebar, Skeleton, Switch, Table, Tabs, Toast (sonner), Tooltip.

### Override mirati

**Button**
- `variant="default"`: bg `--color-ink`, text `--color-bg`, font-weight 500, no shadow. Hover: scurisce ink leggermente (`oklch(18% ...)`).
- `variant="outline"`: bg trasparente, border `--color-border`, hover bg `--color-surface-2`. Per CTA secondari.
- `variant="ghost"`: solo hover bg `--color-surface-2`. Per azioni in tabella e menu kebab.
- `variant="accent"` (nuovo): bg `--color-accent`, text bianco. **Solo per azioni "info"** (es. "Apri anno corrente" sull'empty state della dashboard). Da non confondere con primary.
- Niente `variant="destructive"` come bottone di prima vista: l'azione distruttiva è sempre dietro un dialog con conferma in `--color-danger`.

**Input**
- Focus state: **niente double ring**. Il border passa a `--color-accent`, fuori una outline `1px solid --color-accent-line`. Niente offset. Si vede ma non "salta".
- Errore: border `--color-danger`, helper text sotto in `--color-danger`.
- Numerici: `font-mono`, `text-right`, prefisso/suffisso (`€`, `%`) come slot interno muted.

**Table**
- Header: `text-2xs uppercase tracking-wider` (`kicker`), `--color-muted`, `h-9`.
- Body row: `h-9`, hover `bg-surface-2`, selected `bg-accent-soft` + left-edge inset 2px accent (segnale + accent, mai border laterale come decoro).
- Colonne numeriche: classe `.numeric` (mono + tabular).
- Zebra: **off**. La densità basta; le zebre aggiungono rumore.

**Card**
- Border `1px --color-border`, bg `--color-surface`, `rounded-md`, **no shadow**. Mai card dentro card.
- Padding `p-5`. Per gruppi di KPI dashboard usiamo card a sezione, non card grandi singole.

**Sidebar**
- Item attivo: bg `--color-surface-2`, **inset-left 2px `--color-accent`** (signature visiva del sistema, l'unico uso "deciso" del colore in chrome).
- Icone: **Phosphor** weight `regular` (default) → `fill` quando attive, crossfade 160ms.

**Sheet** (per registrazione pagamento e dettagli)
- Side `right` desktop, `bottom` mobile (full-width). Per Studiofinance preferiamo sheet alle dialog per i form: meno bloccante, più editoriale.

**Dialog**
- Riservato a conferme distruttive (cancellazione, reversibilità stato) e flussi blocco (cross-year check del wizard apertura anno). Mai per inserimento dati.

### Componenti custom da costruire

- **`PageHeader`** (in sub-topbar): breadcrumb + slot azioni. Compatto h-12.
- **`KpiTile`**: label kicker (text-2xs uppercase muted) + valore (mono large) + delta opzionale (mono micro con freccia testuale `↑` / `↓`, mai icone tonde colorate). Border-bottom hairline, no card.
- **`StatusPill`**: chip h-5, text-2xs, bg `*-soft`, text `--color-*`. Stati: `pagato` (success), `pianificato` (muted), `non dovuto` (muted strike), `aperta` (info), `completata` (success), `non_dovuta` (muted), `scaduta` (danger).
- **`FormSection`**: titolo kicker uppercase + space-y-4. Per le pagine impostazioni e form lunghi.
- **`FormulaBlock`** (dettaglio Spesa annuale): box surface con bordo, font-mono, mostra l'espressione di calcolo in chiaro (es. `Imposta sostitutiva = (€42.500 × 78% − €4.200) × 5% = €1.890`).
- **`EmptyState`**: container `rounded-lg border-dashed`, padding generoso (`p-12`), no illustrazioni; solo testo (titolo + body muted) + CTA.
- **`MonthGrid`** (vista anno, tabella 12 mesi): tabella densa con sticky column primo mese, righe per metrica (Imponibile, Bolli, Volume affari, Totale, Quote spese, Netto). Mono per i numeri, sub-cell line-height stretto.

## Iconography

- **Phosphor Icons** (`@phosphor-icons/vue`), weight `regular` (16px) come default. Weight `fill` su stato attivo (sidebar attiva, chip stato). Sempre 16×16, mai grandi: l'icona non è decoro, è un indice di lettura.
- Niente icone tonde colorate (anti-reference). Niente "icon chip" sui KPI.

## Motion

```css
@theme {
  --ease-smooth: cubic-bezier(0.16, 1, 0.3, 1);   /* ease-out-quart */
  --duration-fast: 120ms;
  --duration:      180ms;
  --duration-slow: 280ms;
}
```

- Transizioni su `opacity`, `transform`, `background-color`, `color`, `border-color`. **Mai** su layout (width, height, padding).
- Sidebar collapse: `transform: translateX`, `--duration-slow`, ease-smooth.
- Hover state: `--duration-fast`.
- Page enter: opacity 0→1 + translateY 4px→0, `--duration` ease-smooth.
- `prefers-reduced-motion: reduce` → tutte le transizioni a `0ms`.

## Focus states (richiesta esplicita dell'utente)

I focus ring shadcn standard (doppio ring offset) sono espliciti ma rumorosi nella nostra densità. **Sostituzione globale:**

```css
:root {
  --focus-outline:  1px solid var(--color-accent-line);
  --focus-border:   var(--color-accent);
}

/* Input, button, select: border si fonde con accent + 1px outline fine fuori */
.focus-visible\:focus-style:focus-visible,
button:focus-visible,
[role="button"]:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
  outline: var(--focus-outline);
  outline-offset: 0;
  border-color: var(--focus-border);
}

/* Link e elementi senza bordo: underline che diventa accent */
a:focus-visible {
  outline: var(--focus-outline);
  outline-offset: 2px;
  border-radius: var(--radius-sm);
}
```

Risultato: il focus si vede a tastiera, ma legge come "il componente sa di essere selezionato", non come "anello blu sospeso".

## Utility custom

```css
.kicker {
  font-size: var(--text-2xs);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-muted);
  font-weight: 500;
}

.code-pill {
  display: inline-flex;
  align-items: center;
  height: 18px;
  padding: 0 6px;
  font-family: var(--font-mono);
  font-size: 10.5px;
  background: var(--color-surface-2);
  border-radius: var(--radius-sm);
  color: var(--color-muted);
}

.numeric {
  font-family: var(--font-mono);
  font-variant-numeric: tabular-nums;
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 20px;
  padding: 0 8px;
  border-radius: var(--radius);
  font-size: var(--text-2xs);
  font-weight: 500;
}
.pill--success { background: var(--color-success-soft); color: var(--color-success); }
.pill--warning { background: var(--color-warning-soft); color: var(--color-warning); }
.pill--danger  { background: var(--color-danger-soft);  color: var(--color-danger); }
.pill--info    { background: var(--color-accent-soft);  color: var(--color-accent); }
.pill--muted   { background: var(--color-surface-2);    color: var(--color-muted); }

/* Hairline separator */
.hairline { border-color: var(--color-border-soft); }
```

## Responsive

- **Desktop** ≥ 1024px (target principale di consultazione): sidebar aperta, tabelle dense piene, vista anno e vista scadenze in layout multi-colonna.
- **Tablet** 768–1023px: sidebar collassata a rail (56px), tabelle con scroll orizzontale o colonne secondarie nascoste.
- **Mobile** < 768px (target principale di inserimento): sidebar diventa drawer (sheet da sinistra). KPI da tile a stack. Tabelle si trasformano in card-list verticali. Sheet form da bottom. CTA "Registra pagamento" su scadenza è grande e tap-friendly (h-12).

## Anti-list (cosa non faremo, mai)

- Card dentro card.
- Side-stripe borders su alert / callout (banditi dai shared laws).
- Gradient text per i KPI.
- Glassmorphism / blur decorativo.
- Shadow drammatiche su card o bottoni.
- Icone tonde colorate sui KPI dashboard ("widget" stile gestionale).
- Hero-metric template (numero gigante + label piccola + sparkline arancione).
- Doppio ring di focus shadcn default.
- Verdi "Whatsapp" o rossi "Notification" (i status hanno saturation contenuta).
- Empty state con illustrazioni SVG generiche.
- Toast verde con check su sfondo bianco (success è chip + testo, mai festa).
- Bordi colorati arrotondati attorno alle card "attive". L'attivo si segnala con accent-soft + inset 2px sul lato logico (left per sidebar, top per tabs).
