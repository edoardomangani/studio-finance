# KPI Dashboard anno e dashboard generale

## Dashboard anno:

### Anno in corso:

- totale fatturato
- totale spese ad oggi
- totale netto
- pagato
- totale spese da pagare ad oggi (scalando quanto è già pagato)
Meno importanti:
- totale spese anno
- totale spese anno da pagare (scalando quanto è già pagato)

### Anno chiuso:

- totale fatturato
- totale spese
- totale netto
- pagato
- totale spese da pagare (scalando quanto è già pagato)

## Dashboard generale:

- Focus sul mese in corso:
    - Fatturato del mese
    - Spese del mese
    - Stipendio del mese
- fatturato cumulato anno in corso
- totale spese da pagare ad oggi (scalando quanto è già pagato) trasversali a più anni
- totale scadenze da pagare trasversali a più anni

Capire se il da pagare ad oggi ha senso che usi i minimi o prenda il valore effettivo anche se minore del minimo dato che l'anno è parziale.

---

## Modello di calcolo (nota tecnica)

Decisioni convergenti su *come* calcolare i KPI qui sopra. Principio guida: **la quota mensile che accantoni è congelata** — calcolata una volta dai dati fino a quel mese, mai ritoccata da eventi successivi. Sono soldi che sposti davvero, devi sapere quanto hai spostato.

### Tre livelli, tre regole

| Livello | Minimo | Massimale | Note |
| --- | --- | --- | --- |
| Quota mensile (accantonamento, "stipendio") | **no** | sì, incrementale | income-based puro, congelata |
| Spese a oggi / netto in corso | **no** | sì | = Σ delle quote mensili |
| Spese anno (definitivo) / da pagare anno | **sì** | sì | l'obbligo reale, col pavimento |

- **Minimo solo nell'annuo.** Mensile e a-oggi sono income-based puri: l'accantonamento segue i ricavi, non il calendario (puoi non fatturare per mesi e poi fatturare tutto in uno). Spalmare il minimo nel tempo sarebbe sbagliato. Conseguenza accettata: l'a-oggi *sottostima*; il pavimento ricompare in "spese anno" e nella lente cassa, quindi non resta nascosto.
- **Massimale ovunque, ma incrementale-cumulativo.** Quota del mese M = `(min(redditoCum_M, massimale) − min(redditoCum_M-1, massimale)) × aliquota`. Raggiunto il tetto, **i mesi successivi sono 0**. Mai ridividere il capato per 12: ritoccherebbe i mesi passati (rompe il "congelato") ed è concettualmente errato. Il cap dipende solo dai redditi fino a quel mese, quindi resta congelato.
- **Netto in corso** = fatturato a oggi − spese a oggi (entrambi maturati). A consuntivo il netto usa il definitivo (col minimo): è la correzione finale, il netto-in-corso era ottimista del solo gap-minimo.

### Reciproci attesi

- `Σ(quota mensile) = spese a oggi` (entrambi income-based, capati, senza minimo).
- `spese anno − spese a oggi` a fine anno = top-up del minimo non coperto dai ricavi.
- Le due "da pagare" trasversali sono **due lenti**: *competenza* (da accantonare, income-based, "quanto ho di vivo da parte") vs *cassa / F24* (scadenze, con acconti). Divergono per costruzione → label esplicite, mai affiancate nude.

### Stato del codice (giugno 2026)

- `ExpenseCalculator::amountToDate` per le voci percentuali restituisce oggi il **definitivo (col minimo)** → va portato a income-based **senza minimo** (tenendo il cap), così "spese a oggi" / netto-in-corso riconciliano col mensile.
- L'accantonamento mensile (`applyLimits = false`) oggi **ignora anche il massimale**: oltre il tetto continuerebbe a piena aliquota e `Σ mensile` supererebbe il definitivo. Va applicato il cap incrementale (mesi post-tetto = 0).
- **Nota pratica:** per i forfettari (ricavi ≤ 85k, reddito IRPEF ancora più basso per il coefficiente) il massimale Inarcassa non si raggiunge mai → il punto cap è oggi teorico, ma il modello sopra lo rende corretto e a prova di futuro.
