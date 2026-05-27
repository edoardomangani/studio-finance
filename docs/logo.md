# Brief: marchio Studiofinance

> Brief di shape (UX/UI) per la creazione del marchio Studiofinance. Da passare alla sessione di design che produrrà il vettoriale finale. Non è documentazione del codice esistente: è la specifica del segno ancora da disegnare.

## 1. Sintesi

Un singolo marchio simbolico, **senza testo**, da usare come favicon (16/32px) e nello slot logo della sidebar (collassata 24px, espansa 28px). Non è una icona di interfaccia: è l'unico oggetto del sistema con un'identità grafica autonoma — tutto il resto è Phosphor regular su zinc. Il marchio deve resistere accanto a quelle icone senza essere scambiato per una di esse, e deve incarnare ciò che il prodotto fa: **trattenere, accantonare, dimensionare**.

## 2. Azione primaria

Il marchio non chiede un'azione: chiede riconoscimento immediato in due contesti specifici.

- Tab del browser tra dieci altri tab di gestionali/banking — deve leggere come "Studiofinance" anche cieco.
- Angolo alto della sidebar a fianco del wordmark "Studiofinance" e sopra il menu (Dashboard / Anno corrente / Fatture / Scadenze).

Deve dare l'impressione che chi ha disegnato l'app sia la stessa persona che ha disegnato il logo, non un'agenzia esterna.

## 3. Direzione visiva

- **Concetto** — **UN SINGOLO oggetto scultoreo**, simbolico, astratto. Non una composizione di elementi, non un diagramma, non un'illustrazione. Il marchio è una forma con silhouette propria — la stessa energia formale di Linear, Claude, ChatGPT, Vercel: un oggetto solo, fatto benissimo. Nessuna lettera leggibile come tale.
- **Linguaggio formale** — Geometria architettonica classica e organica: voussoir (chiavi di volta), vesica piscis, archi come massa solida, volute, lenti, pietre sagomate, corolle radiali. Curve di compasso e bezier disciplinate, mai wave amorfe o scarabocchi a mano libera. Un occhio architetto deve poter riconoscere la figura geometrica *madre* (es. *"è una vesica con d/r = 0.8"*, *"è una voluta logaritmica"*, *"è il voussoir centrale di un'arco a tutto sesto"*) senza che la costruzione venga mostrata sul marchio finale.
- **Color strategy** — Restrained. Il marchio è **monocromatico, una sola tinta**, di default **solid fill** (stroke solo quando l'oggetto è inerentemente lineare, es. volute). Mai accent petrol nel marchio. Nessun gradient, nessuna ombra, nessun bevel.
- **Scene sentence** — *Un architetto guarda la tab del browser di Studiofinance per la dodicesima volta in tre giorni; il marchio non gli ha mai chiesto attenzione, ma quando lo cerca lo trova al primo colpo.* Sobrietà con un oggetto memorabile come ancora visiva — non come decoro.
- **Anchor references** (specifici, non aggettivi):
  1. **Linear** — un singolo oggetto geometrico (chevron L) elevato a marchio. Una forma sola, perfetta nei rapporti.
  2. **ChatGPT** — rosone esadecagonale di petali a curve morbide. Singolo oggetto a simmetria radiale, solid fill.
  3. **Claude** — corolla asimmetrica. Singolo oggetto con curve organiche e silhouette distintiva.
  4. **Vercel** — triangolo solido nero. Per il livello di compattezza/economia mentale che un marchio può raggiungere.
  5. **Architectural Association (Pentagram)** — per la prova che un marchio in ambito architettura può essere semplice senza essere generico.

## 4. Scope

- **Fidelity** — production-ready (asset finale).
- **Breadth** — un solo marchio, declinato in due polarità (positivo/negativo) e tre size target (16, 24, 32). Niente famiglia, niente fratello-minore per le size micro: calibrazione manuale a 16px.
- **Interattività** — statico. Nessun cambio di stato hover/active sul logo. Quando l'utente passa sopra la voce sidebar che contiene il logo, **il logo non si anima**.
- **Time intent** — fino a shippabile.

## 5. Strategia di composizione

- **Un solo oggetto.** Il marchio è una silhouette, non una scena. Niente "due elementi in relazione", niente composizione di parti, niente diagrammi. Una forma sola, riempita o tracciata, con identità propria.
- **Solid fill di default.** Il marchio è una massa piena monocromatica (Vercel / ChatGPT / Linear pattern). Stroke ammessa solo se l'oggetto è inerentemente lineare (volute, spirali) — in quel caso peso unico e generoso (≥ 2px @24, ≥ 1.25px @16).
- **Bounding box quadrato** ottico, safe area interna del 12,5% per lato.
- **Provenienza geometrica esplicita.** Il marchio deve essere derivato da una figura geometrica classica nota (vesica piscis, voussoir, arco a tutto sesto, voluta, rosone, lente, sezione aurea) — non da una scelta arbitraria di curve. Anche se le linee di costruzione non sono mostrate sul marchio shippato, devono esistere nella sorgente Figma e devono giustificare ogni rapporto. Il marchio deve avere un "perché era così" che resiste a critica.
- **Curve organiche bienvenute ma disciplinate.** Archi di compasso, bezier simmetrici, curve generate da intersezioni di cerchi. **Banditi:** wave amorfe, scarabocchi a mano libera, splines casuali, curve "drawn by feel".
- **Calibrazione 16px manuale.** Anche con solid fill, le proporzioni vanno verificate occhio-su-pixel a 16. L'auto-scale di Figma non basta — può comparire un mezzo-pixel anti-aliased che spegne il segno.

## 6. Sidebar: composizione marchio + wordmark

Il marchio convive con un wordmark "Studiofinance" composto in Switzer.

```
[marchio 28×28] [gap 10px] [Studiofinance — Switzer 14/500]
```

- Allineamento ottico: baseline del wordmark allineata all'altezza-x della parola, non al cap height — il marchio deve leggere come parte della stessa parola, non come oggetto separato accostato.
- Il wordmark è **sempre SVG**, mai testo live, per controllo kerning manuale.
- **Rail collassata (56px):** scompare il wordmark, resta il solo marchio centrato in slot 24×24.
- Tracking del wordmark: leggermente stretto, `letter-spacing: -0.014em`, coerente con la scala typography di DESIGN.md.

## 7. Stati e declinazioni

| Slot | Size | Polarità | File |
|---|---|---|---|
| Favicon retina | 32×32 | ink su bg | `favicon.svg` + `favicon-32.png` |
| Favicon legacy | 16×16 | ink su bg | `favicon-16.png` (ottimizzato a mano, no auto-export) |
| Sidebar light, rail collassata | 24×24 | ink su bg | `mark.svg` |
| Sidebar light, espansa | 28×28 | ink su bg | `mark.svg` + `wordmark.svg` |
| Sidebar dark, qualunque | 24–28 | bg su ink | `mark-dark.svg` (+ `wordmark-dark.svg` quando espansa) |
| Apple touch icon | 180×180 | ink su bg con padding 24px | `apple-touch-icon.png` |
| OG image (futura) | 1200×630, marchio centrato | ink su bg | fuori scope di questa consegna ma da prevedere |

**Solo polarità, mai cambio di forma tra light e dark.** L'occhio deve riconoscere lo stesso oggetto in negativo. Niente stato hover/active che modifica il marchio.

Token di colore già definiti in [DESIGN.md](../DESIGN.md):

- `ink` light = `oklch(21% 0.006 286)` / dark = `oklch(98.5% 0.001 286)`
- `bg` light = `oklch(98.5% 0.001 286)` / dark = `oklch(14.5% 0.003 286)`

## 8. Modello di interazione

Statico. Il logo nella sidebar non è cliccabile come elemento separato (il link "torna alla home" è gestito altrove). In favicon non c'è interazione. **Nessuna animazione di idle**, nessun pulse, nessun morph.

## 9. Requisiti di consegna

- **Sorgente Figma o vettoriale editabile** con la figura geometrica generatrice documentata su un layer separato (es. i due cerchi della vesica, l'arco a tutto sesto da cui è ricavato il voussoir, la spirale logaritmica). Il marchio shippato non mostra queste guide; la sorgente sì.
- **SVG ottimizzato** per `mark.svg` e `mark-dark.svg`: nessun `<style>`, colore tramite `currentColor` dove possibile, viewBox `0 0 24 24` (e `0 0 32 32` per la versione 32). **Solid fill di default**; stroke solo per famiglie volute/spirali, con peso unico documentato.
- **PNG 16×16 ottimizzato pixel-per-pixel a mano**: l'auto-export Figma a 16px è insufficiente per il favicon legacy.
- **`wordmark.svg`** — testo "Studiofinance" in Switzer medium 14px, kerning ottimizzato manualmente, viewBox stretto al box ottico delle lettere.
- **Spec sheet a una pagina** che mostri: la figura geometrica madre, il marchio derivato, rapporti chiave in unità griglia, spaziature minime, accostamento marchio + wordmark con misure, varianti polarità, test di leggibilità a 16/24/32, do/don't (almeno 4 don't espliciti).

## 10. Anti-list (cosa il marchio non sarà, mai)

Banditi categoricamente — non sono "da evitare", sono motivo di scarto:

- **Marchi diagrammatici.** Sezioni stilizzate con elementi multipli (parete + retino + datum, due quote sovrapposte, formwork con ganci, vasca + livello + simbolo). Il marchio è un oggetto, non un disegno tecnico. Il lessico drafting appartiene alle icone UI, non al brand mark.
- **Linee di costruzione mostrate sul marchio finale.** Le tieni nella sorgente Figma su un layer separato; il marchio shippato è la silhouette pulita.
- Lettere S, F, SF in qualunque forma leggibile (font, custom, ligatura, stencil). Eccezione: una S inscritta nel negativo di una forma che si legge prima come oggetto, poi come lettera.
- Monete, banconote, simboli €/$/%, sparkline, grafici di qualunque tipo
- Salvadanaio, portafoglio, cassaforte con manopola, lucchetto, chiave, scudo
- Casetta, palazzo, skyline, mappa, bussola — referenze all'architettura troppo letterali
- Freccia in alto / razzo / sparkle / glow — slang fintech-AI
- Cerchi concentrici, target, radar, esagono regolare, cristallo
- Gradient di qualunque tipo, ombre, bevel, estrusione 3D
- Glassmorphism, blur, iridescenza
- Color accent del prodotto (petrol) nel marchio
- Forma astratta indifferenziata (il "logo blu quadrato" che potrebbe essere qualunque app)
- Doppio stato outline/fill: il marchio è un oggetto, non un'icona Phosphor con varianti

## 11. Riferimenti tecnici esterni

- Norme ISO 128 (disegno tecnico) come dizionario formale di partenza per quotature, sezioni, tratteggi.
- [DESIGN.md](../DESIGN.md) — token di colore, tipografia, radius del sistema in cui il marchio dovrà vivere.
- [PRODUCT.md](../PRODUCT.md) — tono, anti-references, pubblico (architetti forfettari) e voce del prodotto.

## 12. Integrazione nel codice

Lo slot del marchio in app è il componente [resources/js/components/AppLogo.vue](../resources/js/components/AppLogo.vue), già montato nella sidebar. La consegna di design deve produrre asset che possano essere droppati lì senza riscrittura della struttura del componente.
